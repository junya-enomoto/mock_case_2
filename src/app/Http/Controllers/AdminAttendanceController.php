<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $targetDate = Carbon::parse($date);

        $attendances = Attendance::with(['user', 'rests'])
            ->whereDate('work_date', $targetDate)
            ->orderBy('user_id')
            ->get();

        $prevDate = $targetDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $targetDate->copy()->addDay()->format('Y-m-d');

        return view('admin.attendance_list', compact(
            'attendances',
            'targetDate',
            'prevDate',
            'nextDate'
        ));
    }

    public function detail($id)
{
    $attendance = Attendance::with(['user', 'rests'])->find($id);

    if (!$attendance) {
        abort(404);
    }
    return view('admin.attendance_detail', compact('attendance'));
}

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::find($id);

        if (! $attendance) {
            abort(404);
        }

        $attendance->update([
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
        ]);

        if ($request->has('rest_mod')) {
            foreach ($request->rest_mod as $restId => $times) {
                $rest = Rest::find($restId);
                if ($rest) {
                    $rest->update([
                        'start_time' => $times['start'],
                        'end_time' => $times['end'],
                    ]);
                }
            }
        }

        if ($request->has('rest_add')) {
            foreach ($request->rest_add as $newRest) {
                if (!empty($newRest['start']) && !empty($newRest['end'])) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $newRest['start'],
                        'end_time' => $newRest['end'],
                    ]);
                }
            }
        }

        return redirect()->route('admin.attendance.detail', ['id' => $id])
            ->with('status', '勤怠情報を更新しました。');
    }


    public function requestList(Request $request)
    {
        $selectedStatus = $request->input('status', 'pending'); 

        $query = AttendanceCorrectionRequest::with(['attendance.user'])
            ->orderBy('created_at', 'desc');

        if ($selectedStatus === 'pending') {
            $query->where('status', 'pending');
        } elseif ($selectedStatus === 'approved') {
            $query->where('status', 'approved');
        } elseif ($selectedStatus === 'rejected') { 
            $query->where('status', 'rejected');
        }

        $requests = $query->get();
        $pendingCount = AttendanceCorrectionRequest::where('status', 'pending')->count();
        $approvedCount = AttendanceCorrectionRequest::where('status', 'approved')->count();
        $rejectedCount = AttendanceCorrectionRequest::where('status', 'rejected')->count();

        return view('admin.stamp_correction_request_list', compact(
            'requests',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'selectedStatus' 
        ));
    }

    public function showApprovalForm($attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with(['attendance.user', 'attendance.rests'])
            ->find($attendance_correct_request_id);

        if (! $correctionRequest) {
            abort(404);
        }

        return view('admin.stamp_correction_request_approve', compact('correctionRequest'));
    }

    public function processApproval(Request $request, $attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with('attendance')->find($attendance_correct_request_id);

        if (! $correctionRequest || $correctionRequest->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => '処理できない申請です。'], 400);
        }

        $attendance = $correctionRequest->attendance;

        $attendance->clock_in = $correctionRequest->clock_in_new;
        $attendance->clock_out = $correctionRequest->clock_out_new;

        $attendance->save();

        $correctionRequest->status = 'approved';
        $correctionRequest->save();

        return response()->json(['status' => 'success', 'message' => '修正申請を承認しました。'], 200);
    }

    public function staffList(Request $request)
    {
        $staffs = User::orderBy('name')->paginate(20);

        return view('admin.staff_list', compact('staffs'));
    }

    public function staffAttendanceList(Request $request, $id)
    {
        $targetUser = User::find($id);
        if (! $targetUser) {
            abort(404, '指定されたユーザーが見つかりません。');
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $targetMonth = Carbon::createFromDate($year, $month, 1);

        $startDate = $targetMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $targetMonth->copy()->endOfMonth()->format('Y-m-d');

        $attendancesData = Attendance::with('rests')
            ->where('user_id', $id) 
            ->whereBetween('work_date', [$startDate, $endDate])
            ->orderBy('work_date')
            ->get()
            ->keyBy('work_date'); 

        $daysInMonth = CarbonPeriod::create($startDate, $endDate);

        $attendanceList = collect();
        foreach ($daysInMonth as $day) {
            $dateString = $day->format('Y-m-d');
            $attendanceRecord = $attendancesData->get($dateString);

            $attendanceList->push((object) [
                'date' => $day->format('m/d'),
                'dayOfWeek' => ['日', '月', '火', '水', '木', '金', '土'][Carbon::parse($day)->dayOfWeek],
                'clock_in' => $attendanceRecord && $attendanceRecord->clock_in ? Carbon::parse($attendanceRecord->clock_in)->format('H:i') : null,
                'clock_out' => $attendanceRecord && $attendanceRecord->clock_out ? Carbon::parse($attendanceRecord->clock_out)->format('H:i') : null,
                'total_break_time' => $attendanceRecord ? $attendanceRecord->total_break_time : null,
                'total_work_time' => $attendanceRecord ? $attendanceRecord->total_work_time : null,
                'has_detail' => $attendanceRecord ? true : false,
                'attendance_id' => $attendanceRecord ? $attendanceRecord->id : null,
            ]);
        }

        $prevMonth = $targetMonth->copy()->subMonth();
        $nextMonth = $targetMonth->copy()->addMonth();

        return view('admin.staff_attendance_list', compact(
            'targetUser',
            'attendanceList',
            'targetMonth',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function exportCsv(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $targetMonth = Carbon::createFromDate($year, $month, 1);
        $startDate = $targetMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $targetMonth->copy()->endOfMonth()->format('Y-m-d');

        $attendancesData = Attendance::with('rests')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        $daysInMonth = \Carbon\CarbonPeriod::create($startDate, $endDate);

        $response = new StreamedResponse(function () use ($daysInMonth, $attendancesData) {

            $stream = fopen('php://output', 'w');

            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($daysInMonth as $day) {
                $dateString = $day->format('Y-m-d');
                $record = $attendancesData->get($dateString);

                $row = [
                    $day->format('m/d') . '(' . ['日','月','火','水','木','金','土'][$day->dayOfWeek] . ')',
                    $record && $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
                    $record && $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                    $record ? $record->total_break_time : '',
                    $record ? $record->total_work_time : '',
                ];

                fputcsv($stream, $row);
            }
            fclose($stream);
        });

        $fileName = sprintf('%s_%s年%s月_勤怠情報.csv', $targetUser->name, $year, str_pad($month, 2, '0', STR_PAD_LEFT));

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}

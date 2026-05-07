<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CorrectionRest;
use App\Models\Rest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        $status = 0;

        if ($attendance) {
            if (is_null($attendance->clock_out)) {

                $latestRest = Rest::where('attendance_id', $attendance->id)
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();

                if ($latestRest) {
                    $status = 2;
                } else {
                    $status = 1;
                }

            } else {
                $status = 3;
            }
        }

        return view('attendance.attendance', compact('status'));
    }

    public function clockIn()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $exists = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->exists();

        if (! $exists) {
            Attendance::create([
                'user_id' => $userId,
                'work_date' => $today,
                'clock_in' => $now,
            ]);
        }

        return redirect()->back();
    }

    public function clockOut()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        if ($attendance) {
            $attendance->update([
                'clock_out' => $now,
            ]);
        }

        return redirect()->back();
    }

    public function breakStart()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        if ($attendance && is_null($attendance->clock_out)) {
            $isResting = Rest::where('attendance_id', $attendance->id)
                ->whereNull('end_time')
                ->exists();

            if (! $isResting) {
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $now,
                ]);
            }
        }

        return redirect()->back();
    }

    public function breakEnd()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        if ($attendance && is_null($attendance->clock_out)) {
            $latestRest = Rest::where('attendance_id', $attendance->id)
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();

            if ($latestRest) {
                $latestRest->update([
                    'end_time' => $now,
                ]);
            }
        }

        return redirect()->back();
    }

    public function list(Request $request)
    {
        $userId = Auth::id();

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $targetMonth = Carbon::createFromDate($year, $month, 1);

        $startDate = $targetMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $targetMonth->copy()->endOfMonth()->format('Y-m-d');

        $attendancesData = Attendance::with('rests')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($item) {
                                        return \Carbon\Carbon::parse($item->work_date)->format('Y-m-d');
                                    });

        $daysInMonth = CarbonPeriod::create($startDate, $endDate);

        $attendanceList = collect();
        foreach ($daysInMonth as $day) {
            $dateString = $day->format('Y-m-d');
            $attendanceRecord = $attendancesData->get($dateString);

            $attendanceList->push((object) [
                'date' => $day->format('m/d'),
                'dayOfWeek' => ['日', '月', '火', '水', '木', '金', '土'][Carbon::parse($day)->dayOfWeek],
                'clock_in' => $attendanceRecord && $attendanceRecord->clock_in
                        ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
                        : null,

                'clock_out' => $attendanceRecord && $attendanceRecord->clock_out
                        ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                        : null,
                'total_break_time' => $attendanceRecord ? $attendanceRecord->total_break_time : null,
                'total_work_time' => $attendanceRecord ? $attendanceRecord->total_work_time : null,
                'has_detail' => $attendanceRecord ? true : false,
                'attendance_id' => $attendanceRecord ? $attendanceRecord->id : null,
            ]);
        }

        $prevMonth = $targetMonth->copy()->subMonth();
        $nextMonth = $targetMonth->copy()->addMonth();

        return view('attendance.attendance_list', compact(
            'attendanceList',
            'targetMonth',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function detail($id)
{
    $attendance = Attendance::with('user', 'rests')->find($id);

    if (!$attendance || $attendance->user_id !== Auth::id()) {
        abort(404);
    }

    $pendingRequest = $attendance->correctionRequests()
                                 ->with('correctionRests')
                                 ->where('status', 'pending')
                                 ->first();
    $hasPendingRequest = (bool) $pendingRequest;

    // ★★★このブロックが完全に存在し、正確に記述されているか確認★★★
    $allBreaksForDisplay = collect();
    foreach ($attendance->rests as $rest) {
        $allBreaksForDisplay->push((object)[
            'original_rest_id' => $rest->id,
            'start_time' => $rest->start_time,
            'end_time' => $rest->end_time,
            'is_new' => false,
        ]);
    }

    if ($hasPendingRequest) {
        foreach ($pendingRequest->correctionRests as $correctionRest) {
            if ($correctionRest->original_rest_id !== null) {
                $index = $allBreaksForDisplay->search(function($item) use ($correctionRest) {
                    return $item->original_rest_id === $correctionRest->original_rest_id;
                });
                if ($index !== false) {
                    $updatedBreak = clone $allBreaksForDisplay[$index]; // この行が原因でエラーになった可能性があるので、cloneを追加
                    $updatedBreak->start_time = $correctionRest->start_time_new;
                    $updatedBreak->end_time = $correctionRest->end_time_new;
                    $allBreaksForDisplay[$index] = $updatedBreak;
                }
            } else {
                $allBreaksForDisplay->push((object)[
                    'original_rest_id' => null,
                    'start_time' => $correctionRest->start_time_new,
                    'end_time' => $correctionRest->end_time_new,
                    'is_new' => true,
                ]);
            }
        }
    }
    $allBreaksForDisplay = $allBreaksForDisplay->sortBy('original_rest_id')->values();
    $nextRestIndex = $allBreaksForDisplay->count();
    // ★★★このブロックの確認★★★

    // ★★★compact() に allBreaksForDisplay と nextRestIndex が含まれているか確認★★★
    return view('attendance.attendance_detail', compact(
        'attendance',
        'hasPendingRequest',
        'pendingRequest',
        'allBreaksForDisplay',
        'nextRestIndex'
    ));
}



    public function submitRequest(CorrectionRequest $request, $id)
    {
        $attendance = Attendance::find($id);
        if ($attendance->correctionRequests()->where('status', 'pending')->exists()) {
            return redirect()->route('attendance.detail', ['id' => $id])
                ->with('error', '既に承認待ちの修正申請があります。');
        }

        $correctionRequest = new AttendanceCorrectionRequest;
        $correctionRequest->attendance_id = $id;
        $correctionRequest->clock_in_new = $request->clock_in;   
        $correctionRequest->clock_out_new = $request->clock_out;
        $correctionRequest->remarks = $request->remarks;
        $correctionRequest->status = 'pending';
        $correctionRequest->save(); 

        if ($request->has('rest_mod')) {
            foreach ($request->rest_mod as $restId => $times) {
                if (! empty($times['start']) && ! empty($times['end'])) { 
                    CorrectionRest::create([
                        'correction_id' => $correctionRequest->id,
                        'original_rest_id' => $restId, 
                        'start_time_new' => $times['start'],
                        'end_time_new' => $times['end'],
                    ]);
                }
            }
        }
        if ($request->has('rest_add')) {
            foreach ($request->rest_add as $times) {
                if (! empty($times['start']) && ! empty($times['end'])) { 
                    CorrectionRest::create([
                        'correction_id' => $correctionRequest->id,
                        'original_rest_id' => null, 
                        'start_time_new' => $times['start'],
                        'end_time_new' => $times['end'],
                    ]);
                }
            }
        }

        return redirect()->route('attendance.detail', ['id' => $id])
            ->with('status', '修正申請を送信しました。承認待ちです。');
    }

        public function requestList(Request $request)
    {
        $userId = Auth::id();
        $selectedStatus = $request->input('status', 'pending'); // ★デフォルトはpending

        // ベースとなるクエリ（自分の申請のみ）
        $query = AttendanceCorrectionRequest::whereHas('attendance', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with('attendance.user')->orderBy('created_at', 'desc');

        // ★ステータスで絞り込み
        if ($selectedStatus === 'pending') {
            $query->where('status', 'pending');
        } elseif ($selectedStatus === 'approved') {
            $query->where('status', 'approved');
        }

        $requests = $query->get();

        return view('stamp_correction_request_list', compact('requests', 'selectedStatus'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        //
    }
}

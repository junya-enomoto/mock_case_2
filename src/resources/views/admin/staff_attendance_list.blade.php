@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_staff_attendance_list.css') }}"> 
@endsection

@section('content')
<div class="admin-attendance-list-container"> 
    <div class="attendance-header">
        <h2>{{ $targetUser->name }}さんの勤怠一覧</h2>
    </div>

    <div class="month-navigation"> 
        <a href="{{ route('admin.staff.attendance.list', ['id' => $targetUser->id, 'year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="nav-arrow">← 前月</a>
        <div class="current-date-display">
            <span class="calendar-icon">&#128197;</span>
            <span>{{ $targetMonth->year }}年{{ str_pad($targetMonth->month, 2, '0', STR_PAD_LEFT) }}月</span> 
        </div>
        <a href="{{ route('admin.staff.attendance.list', ['id' => $targetUser->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="nav-arrow">翌月 →</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendanceList as $record)
                <tr>
                    <td>{{ $record->date }}({{ $record->dayOfWeek }})</td>
                    <td>{{ $record->clock_in ?? '' }}</td>
                    <td>{{ $record->clock_out ?? '' }}</td>
                    <td>{{ $record->total_break_time ?? '' }}</td>
                    <td>{{ $record->total_work_time ?? '' }}</td>
                    <td>
                        @if ($record->has_detail)
                            <a href="{{ route('admin.attendance.detail', ['id' => $record->attendance_id]) }}">詳細</a>
                        @else
                            詳細
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="no-data">この月の勤怠データはありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="export-action-wrapper">
        <a href="{{ route('admin.staff.attendance.csv', ['id' => $targetUser->id, 'year' => $targetMonth->year, 'month' => $targetMonth->month]) }}" class="btn-csv-export">
            CSV出力
        </a>
    </div>
</div>
@endsection

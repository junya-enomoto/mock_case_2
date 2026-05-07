@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list-container">
    <div class="attendance-header">
        <h2>勤怠一覧</h2>
        <div class="month-navigation">
            <a href="{{ route('attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="nav-arrow">← 前月</a>
            <div class="current-month-display">
                <span class="calendar-icon">&#128197;</span>
                <span>{{ $targetMonth->year }}年{{ $targetMonth->month }}月</span>
            </div>
            <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="nav-arrow">翌月 →</a>
        </div>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>勤務開始</th>
                <th>勤務終了</th>
                <th>休憩時間</th>
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
                            <a href="{{ route('attendance.detail', ['id' => $record->attendance_id]) }}">詳細</a>
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
</div>
@endsection

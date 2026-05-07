@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endsection

@section('content')
<div class="admin-attendance-list-container">
    <div class="admin-attendance-header">
        <h2>{{ $targetDate->format('Y年m月d日') }}の勤怠</h2>
    </div>

    <div class="date-navigation">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="nav-arrow">← 前日</a>
        <div class="current-date-display">
            <span class="calendar-icon">&#128197;</span>
            <span>{{ $targetDate->format('Y年m月d日') }}({{ ['日','月','火','水','木','金','土'][$targetDate->dayOfWeek] }})</span>
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="nav-arrow">翌日 →</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->total_break_time ?? '-' }}</td>
                    <td>{{ $attendance->total_work_time ?? '-' }}</td>
                    <td>
                        <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}">詳細</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="no-data">この日の勤怠データはありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

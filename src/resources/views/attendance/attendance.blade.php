@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
@php use Carbon\Carbon; @endphp

<div class="attendance__container">
    <div class="attendance__status">
        @if ($status == 0)
            <span class="status-label">勤務外</span>
        @elseif ($status == 1)
            <span class="status-label">勤務中</span>
        @elseif ($status == 2)
            <span class="status-label">休憩中</span>
        @elseif ($status == 3)
            <span class="status-label">退勤済</span>
        @endif
    </div>

    <div class="attendance__clock">
        <p class="attendance__date">{{ \Carbon\Carbon::now()->format('Y年n月j日') }}({{ ['日','月','火','水','木','金','土'][\Carbon\Carbon::now()->format('w')] }})</p>
        <p class="attendance__time" id="realtime">08:00</p>
    </div>

    <div class="attendance__button">
        @if ($status == 0)
            <form action="{{ route('attendance.clockin') }}" method="POST">
                @csrf
                <button type="submit" class="btn-punch">出勤</button>
            </form>
        @elseif ($status == 1)
            <div class="button-group" style="display: flex; gap: 10px; justify-content: center;">
                <form action="{{ route('attendance.clockout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-punch">退勤</button>
                </form>
                <form action="{{ route('attendance.break_start') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-punch btn-punch-inverted">休憩入</button>
                </form>
            </div>
        @elseif ($status == 2)
            <div class="button-group" style="display: flex; gap: 10px; justify-content: center;">
                <form action="{{ route('attendance.break_end') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-punch btn-punch-inverted">休憩終</button>
                </form>
            </div>
        @elseif ($status == 3)
            <p class="text-message">お疲れ様でした。</p>
        @endif
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('realtime').innerText = `${hours}:${minutes}`;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
@endsection

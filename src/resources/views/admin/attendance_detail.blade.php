@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="attendance-header">
        <h2>勤怠詳細（管理者変更）</h2>
    </div>

    @if (session('status'))
        <div class="alert alert-info">
            {{ session('status') }}
        </div>
    @endif
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="POST">
        @csrf
        <div class="detail-form-group">
            <div class="form-row">
                <label class="form-label">名前</label>
                <div class="form-value">{{ $attendance->user->name }}</div>
            </div>

            <div class="form-row">
                <label class="form-label">日付</label>
                <div class="form-value">
                    <span class="date-part">{{ \Carbon\Carbon::parse($attendance->work_date)->year }}年</span>
                    <span class="date-part">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                </div>
            </div>

            <div class="form-row">
                <label class="form-label">出勤・退勤</label>
                <div class="form-value time-inputs">
                    <input type="time" name="clock_in" 
                           value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}" 
                           class="time-input">
                    <span class="tilde">～</span>
                    <input type="time" name="clock_out" 
                           value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}" 
                           class="time-input">
                </div>
            </div>

            @foreach($attendance->rests as $index => $rest)
            <div class="form-row">
                <label class="form-label">休憩{{ $index + 1 }}</label>
                <div class="form-value time-inputs">
                    <input type="hidden" name="rest_mod[{{ $rest->id }}][id]" value="{{ $rest->id }}">
                    <input type="time" name="rest_mod[{{ $rest->id }}][start]" 
                           value="{{ old('rest_mod.'.$rest->id.'.start', \Carbon\Carbon::parse($rest->start_time)->format('H:i')) }}" 
                           class="time-input">
                    <span class="tilde">～</span>
                    <input type="time" name="rest_mod[{{ $rest->id }}][end]" 
                           value="{{ old('rest_mod.'.$rest->id.'.end', $rest->end_time ? \Carbon\Carbon::parse($rest->end_time)->format('H:i') : '') }}" 
                           class="time-input">
                </div>
            </div>
            @endforeach

            <div class="form-row add-rest-row">
                <label class="form-label">休憩追加</label>
                <div class="form-value time-inputs">
                    <input type="time" name="rest_add[0][start]" class="time-input">
                    <span class="tilde">～</span>
                    <input type="time" name="rest_add[0][end]" class="time-input">
                </div>
            </div>

            <div class="form-row">
                <label class="form-label">備考</label>
                <div class="form-value">
                    <textarea name="remarks" class="remarks-input" placeholder="管理者備考">{{ old('remarks', $attendance->remarks ?? '') }}</textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions" style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn-submit">修正する</button>
        </div>
    </form>
</div>
@endsection

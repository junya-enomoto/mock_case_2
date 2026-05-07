@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="attendance-header">
        <h2>勤怠詳細</h2>
    </div>

    @if (session('status'))
        <div class="alert alert-info">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
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

    <form action="{{ route('attendance.correction.request', ['id' => $attendance->id]) }}" method="POST">
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
                           value="{{ old('clock_in', $hasPendingRequest ? \Carbon\Carbon::parse($pendingRequest->clock_in_new)->format('H:i') : ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')) }}" 
                           class="time-input" {{ $hasPendingRequest ? 'disabled' : '' }}>
                    <span class="tilde">～</span>
                
                    <input type="time" name="clock_out" 
                           value="{{ old('clock_out', $hasPendingRequest ? \Carbon\Carbon::parse($pendingRequest->clock_out_new)->format('H:i') : ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')) }}" 
                           class="time-input" {{ $hasPendingRequest ? 'disabled' : '' }}>
                </div>
            </div>

            @foreach($allBreaksForDisplay as $index => $break)
                <div class="form-row">
                    <label class="form-label">休憩{{ $index + 1 }}</label>
                    <div class="form-value time-inputs">
                        @if (!$break->is_new)
                            <input type="hidden" name="rest_mod[{{ $break->original_rest_id }}][id]" value="{{ $break->original_rest_id }}">
                        @endif
                        
                        <input type="time" 
                            name="{{ $break->is_new ? 'rest_add['.$index.'][start]' : 'rest_mod['.$break->original_rest_id.'][start]' }}"
                            value="{{ old($break->is_new ? 'rest_add.'.$index.'.start' : 'rest_mod.'.$break->original_rest_id.'.start', $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '') }}"
                            class="time-input" {{ $hasPendingRequest ? 'disabled' : '' }}>
                        <span class="tilde">～</span>
                        <input type="time" 
                            name="{{ $break->is_new ? 'rest_add['.$index.'][end]' : 'rest_mod['.$break->original_rest_id.'][end]' }}"
                            value="{{ old($break->is_new ? 'rest_add.'.$index.'.end' : 'rest_mod.'.$break->original_rest_id.'.end', $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '') }}"
                            class="time-input" {{ $hasPendingRequest ? 'disabled' : '' }}>
                    </div>
                </div>
            @endforeach

            @if (!$hasPendingRequest)
                <div class="form-row add-rest-row">
                    <label class="form-label">休憩{{ $nextRestIndex + 1 }}</label>
                    <div class="form-value time-inputs">
                        <input type="time" name="rest_add[{{ $nextRestIndex }}][start]" class="time-input">
                        <span class="tilde">～</span>
                        <input type="time" name="rest_add[{{ $nextRestIndex }}][end]" class="time-input">
                    </div>
                </div>
            @endif

            <div class="form-row">
                <label class="form-label">備考</label>
                <div class="form-value">
                    <textarea name="remarks" class="remarks-input" placeholder="備考を入力してください" {{ $hasPendingRequest ? 'disabled' : '' }}>{{ old('remarks', $hasPendingRequest ? $pendingRequest->remarks : '') }}</textarea>
                </div>
            </div>
        </div>

        @if ($hasPendingRequest)
            <p class="pending-message">*承認待ちのため修正はできません。</p>
        @endif

        <div class="form-actions">
            <button type="submit" class="btn-submit" {{ $hasPendingRequest ? 'disabled' : '' }}>修正</button>
        </div>
    </form>
</div>
@endsection

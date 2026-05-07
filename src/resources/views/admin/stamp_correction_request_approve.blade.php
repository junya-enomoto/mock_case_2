@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_stamp_correction_request_approve.css') }}">
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

    <div class="detail-form-group">
        <div class="form-row">
            <label class="form-label">名前</label>
            <div class="form-value"><span class="text-display">{{ $correctionRequest->attendance->user->name }}</span></div>
        </div>

        <div class="form-row">
            <label class="form-label">日付</label>
            <div class="form-value">
                <span class="date-part text-display">{{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->year }}年</span>
                <span class="date-part text-display">{{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('n月j日') }}</span>
            </div>
        </div>

        <div class="form-row">
            <label class="form-label">出勤・退勤</label>
            <div class="form-value time-inputs">
                <span class="time-display">{{ $correctionRequest->clock_in_new ? \Carbon\Carbon::parse($correctionRequest->clock_in_new)->format('H:i') : '-' }}</span>
                <span class="tilde">～</span>
                <span class="time-display">{{ $correctionRequest->clock_out_new ? \Carbon\Carbon::parse($correctionRequest->clock_out_new)->format('H:i') : '-' }}</span>
            </div>
        </div>

        @forelse($correctionRequest->correctionRests as $index => $crequestRest)
        <div class="form-row">
            <label class="form-label">休憩{{ $index + 1 }}</label>
            <div class="form-value time-inputs">
                <span class="time-display">{{ $crequestRest->start_time_new ? \Carbon\Carbon::parse($crequestRest->start_time_new)->format('H:i') : '-' }}</span>
                <span class="tilde">～</span>
                <span class="time-display">{{ $crequestRest->end_time_new ? \Carbon\Carbon::parse($crequestRest->end_time_new)->format('H:i') : '-' }}</span>
            </div>
        </div>
        @empty
        <div class="form-row">
            <label class="form-label">休憩</label>
            <div class="form-value"><span class="text-display">申請された休憩はありません。</span></div>
        </div>
        @endforelse

        <div class="form-row">
            <label class="form-label">備考</label>
            <div class="form-value">
                <span class="text-display">{{ $correctionRequest->remarks }}</span>
            </div>
        </div>
    </div>

    <div class="approval-actions">
        @if ($correctionRequest->status === 'pending')
            <button type="button" id="approveButton" class="btn-approve-action">承認</button>
        @elseif ($correctionRequest->status === 'approved')
            <span class="processed-button">承認済み</span>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const approveButton = document.getElementById('approveButton');

    if (approveButton) {
        approveButton.addEventListener('click', async function() {
            const requestId = parseInt("{{ $correctionRequest->id }}");
            const token = "{{ csrf_token() }}";

            try {
                const response = await fetch(`/admin/stamp_correction_request/approve/${requestId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ action: 'approve' })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    approveButton.style.display = 'none';
                    const approvalActionsDiv = document.querySelector('.approval-actions');
                    const approvedSpan = document.createElement('span');
                    approvedSpan.classList.add('processed-button');
                    approvedSpan.innerText = '承認済み';
                    approvalActionsDiv.appendChild(approvedSpan);
                    alert(data.message); 
                } else {
                    alert(data.message || '承認処理に失敗しました。');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('ネットワークエラーが発生しました。');
            }
        });
    }
});
</script>
@endsection

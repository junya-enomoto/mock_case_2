@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_stamp_correction_request_list.css') }}">
@endsection

@section('content')
<div class="request-list-container">
    <div class="request-list-header"> 
        <h2>申請一覧</h2>
    </div>

    <div class="tab-menu">
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}" class="tab-item {{ $selectedStatus == 'pending' ? 'active' : '' }}">承認待ち</a>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}" class="tab-item {{ $selectedStatus == 'approved' ? 'active' : '' }}">承認済み</a>
    </div>

    <table class="request-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $request)
                <tr>
                    <td>
                        @if ($request->status == 'pending')
                            承認待ち
                        @elseif ($request->status == 'approved')
                            承認済み
                        @endif
                    </td>
                    <td>{{ $request->attendance->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}</td>
                    <td>{{ $request->remarks }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('admin.stamp_correction_request.approve', ['attendance_correct_request_id' => $request->id]) }}">詳細</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="no-data">このステータスの申請履歴はありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

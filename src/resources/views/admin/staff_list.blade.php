@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_staff_list.css') }}">
@endsection

@section('content')
<div class="staff-list-container">
    <div class="staff-list-header">
        <h2>スタッフ一覧</h2>
    </div>

    <table class="staff-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($staffs as $staff)
                <tr>
                    <td>{{ $staff->name }}</td>
                    <td>{{ $staff->email }}</td>
                    <td>
                        <a href="{{ route('admin.staff.attendance.list', ['id' => $staff->id]) }}">詳細</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="no-data">登録されているスタッフはいません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="pagination-links">
        {{ $staffs->links() }}
    </div>
</div>
@endsection

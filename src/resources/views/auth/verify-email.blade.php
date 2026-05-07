@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify_email.css') }}">
@endsection

@section('content')
<div class="verify-email-container">
    <div class="verify-email-wrapper">
        <p class="message">
            登録していただいたメールアドレスに認証メールを送信しました。<br>
            メール認証を完了してください。
        </p>

       <div class="verify-btn-wrapper">
            <a href="http://localhost:8025" target="_blank" class="verify-btn">
        認証はこちらから
            </a>
        </div>

        <form class="resend-form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="resend-link-btn">認証メールを再送する</button>
        </form>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mt-3" role="alert">
                新しい認証リンクが、ご登録いただいたメールアドレスに送信されました。
            </div>
        @endif
    </div>
</div>
@endsection
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="register-container">
    <div class="register-wrapper">
        <h2 class="register-title">会員登録</h2>
        
        <form method="POST" action="/register">
            @csrf
            
            <div class="form-group">
                <label for="name">ユーザー名</label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}"  autofocus>
                
                @error('name')
                    <span class="error-message"> 
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" >
                
                @error('email')
                    <span class="error-message">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" >
                
                @error('password')
                    <span class="error-message">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">パスワード（確認用）</label>
                <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" >
            </div>

            <button type="submit" class="register-btn">
                登録する
            </button>

            <div class="login-link">
                <a href="/login">ログインはこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection

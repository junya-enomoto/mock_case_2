<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>模擬案件2</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__title">
                <a href="/login">
                    <img src="{{ asset('images/header-logo.png') }}" alt="coachtech" class="header-logo">
                </a>
            </h1>

            <nav>
                <ul class="header-nav">
                   @auth('admin')
                        <li><a href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
                        <li><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
                        <li><a href="{{ route('stamp_correction_request.list') }}">申請一覧</a></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST" class="logout-form">
                                @csrf
                                <button type="submit" class="logout-btn">ログアウト</button>
                            </form>
                        </li>
                    @elseauth('web') 
                        @if (!request()->is('login') && !request()->is('register'))
                            <li><a href="/attendance">勤怠</a></li>
                            <li><a href="/attendance/list">勤怠一覧</a></li>
                            <li><a href="/stamp_correction_request/list">申請</a></li>
                            <li>
                                <form action="/logout" method="POST" class="logout-form">
                                    @csrf
                                    <button type="submit" class="logout-btn">ログアウト</button>
                                </form>
                            </li>
                        @endif
                    @else

                    @endauth

                </ul>
            </nav>

        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>

</html>
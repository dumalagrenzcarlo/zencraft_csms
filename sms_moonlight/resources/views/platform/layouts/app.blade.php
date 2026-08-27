<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Platform') · ZenCraft CSMS</title>
    <link rel="stylesheet" href="{{ global_asset('platform.css') }}">
    <link rel="stylesheet" href="{{ global_asset('commercial.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="shell">
            <a class="brand" href="{{ route('platform.dashboard') }}">ZenCraft <span>CSMS</span></a>
            <nav class="nav" aria-label="Platform navigation">
                <a href="{{ route('platform.dashboard') }}">Overview</a>
                <a href="{{ route('platform.schools.index') }}">Schools</a>
                <a href="{{ route('platform.schools.create') }}">Provision school</a>
                @if(auth()->user()->role === 'owner')<a href="{{ route('platform.audit') }}">Audit log</a>@endif
                <form method="post" action="{{ route('platform.logout') }}">
                    @csrf
                    <button class="link-button" type="submit">Sign out</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="page shell">
        @if (session('status'))
            <div class="notice" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>

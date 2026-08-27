<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform sign in · ZenCraft CSMS</title>
    <link rel="stylesheet" href="{{ global_asset('platform.css') }}">
</head>
<body class="login-page">
    <section class="card login-card">
        <span class="eyebrow">SaaS control plane</span>
        <h1>ZenCraft CSMS</h1>
        <p>Sign in with a platform owner or support account.</p>
        <form method="post" action="{{ route('platform.login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                @error('email')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password')<span class="error">{{ $message }}</span>@enderror
            </div>
            <label><input name="remember" type="checkbox" value="1"> Keep me signed in</label>
            <button class="button orange" type="submit">Sign in</button>
        </form>
    </section>
</body>
</html>

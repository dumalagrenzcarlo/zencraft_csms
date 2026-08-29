<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check your email · ZenCraft CSMS</title>
    <link rel="stylesheet" href="{{ global_asset('platform.css') }}">
    <link rel="stylesheet" href="{{ global_asset('signup.css') }}">
</head>
<body class="signup-page">
    <main class="signup-shell success-shell">
        <section class="card signup-card success-card">
            <span class="success-mark">✓</span>
            <span class="eyebrow">Workspace created</span>
            <h1>Check your email.</h1>
            <p>We sent a verification link to <strong>{{ $email }}</strong>. Verify it before signing in to your school administrator portal.</p>
            <dl><dt>School</dt><dd>{{ $school->name }}</dd><dt>Workspace</dt><dd>{{ url($school->slug) }}</dd></dl>
            <p class="signup-footnote">The verification link expires after {{ config('saas.public_signup.verification_expiration_hours', 24) }} hours.</p>
        </section>
    </main>
</body>
</html>

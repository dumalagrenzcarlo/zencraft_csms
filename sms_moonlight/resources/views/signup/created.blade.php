<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $requiresVerification ? 'Check your email' : 'Workspace ready' }} · ZenCraft CSMS</title>
    <link rel="stylesheet" href="{{ global_asset('platform.css') }}">
    <link rel="stylesheet" href="{{ global_asset('signup.css') }}">
</head>
<body class="signup-page">
    <main class="signup-shell success-shell">
        <section class="card signup-card success-card">
            <span class="success-mark">✓</span>
            <span class="eyebrow">Workspace created</span>
            @if ($requiresVerification)
                <h1>Check your email.</h1>
                <p>We sent a verification link to <strong>{{ $email }}</strong>. Verify it before signing in to your school administrator portal.</p>
            @else
                <h1>Your workspace is ready.</h1>
                <p>Sign in with username <strong>admin</strong> and the password you just created.</p>
            @endif
            <dl><dt>School</dt><dd>{{ $school->name }}</dd><dt>Workspace</dt><dd>{{ url($school->slug) }}</dd></dl>
            @if ($requiresVerification)
                <p class="signup-footnote">The verification link expires after {{ config('saas.public_signup.verification_expiration_hours', 24) }} hours.</p>
            @else
                <a class="button orange signup-submit" href="{{ url($school->slug.'/admin/login') }}">Open administrator login</a>
            @endif
        </section>
    </main>
</body>
</html>

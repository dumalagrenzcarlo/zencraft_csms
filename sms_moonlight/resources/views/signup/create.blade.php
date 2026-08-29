<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your free workspace · ZenCraft CSMS</title>
    <link rel="stylesheet" href="{{ global_asset('platform.css') }}">
    <link rel="stylesheet" href="{{ global_asset('signup.css') }}">
</head>
<body class="signup-page">
    <main class="signup-shell">
        <section class="signup-intro">
            <span class="eyebrow">Free campus workspace</span>
            <h1>Start clearly.<br>Grow when you’re ready.</h1>
            <p>Create a secure ZenCraft-hosted workspace for up to 100 students, 10 faculty and staff, and one administrator.</p>
            <ul><li>Free forever</li><li>Isolated school database</li><li>No DNS setup required</li></ul>
        </section>
        <section class="card signup-card">
            <h2>Create your school</h2>
            <p>Your address will look like <strong>{{ request()->getSchemeAndHttpHost() }}/your-school</strong>.</p>
            <form method="post" action="{{ route('signup.store') }}">
                @csrf
                <div class="signup-grid">
                    <div class="field full"><label for="school_name">School name</label><input id="school_name" name="school_name" value="{{ old('school_name') }}" required autofocus>@error('school_name')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="slug">Workspace address</label><input id="slug" name="slug" value="{{ old('slug') }}" placeholder="my-school" required>@error('slug')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="timezone">Timezone</label><select id="timezone" name="timezone"><option value="Asia/Manila" @selected(old('timezone', 'Asia/Manila') === 'Asia/Manila')>Asia/Manila</option></select>@error('timezone')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field full section-label"><strong>School administrator</strong></div>
                    <div class="field"><label for="admin_name">Full name</label><input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>@error('admin_name')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required>@error('admin_email')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="admin_password">Password</label><input id="admin_password" name="admin_password" type="password" minlength="12" autocomplete="new-password" required>@error('admin_password')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="admin_password_confirmation">Confirm password</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
                    <div class="field"><label for="captcha_answer">Security check: {{ $left }} + {{ $right }} =</label><input id="captcha_answer" name="captcha_answer" type="number" inputmode="numeric" required>@error('captcha_answer')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="trap" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                    <label class="terms full"><input name="terms" type="checkbox" value="1" required> I agree to use the service responsibly and receive account-related email.</label>
                    @error('terms')<span class="error full">{{ $message }}</span>@enderror
                </div>
                <button class="button orange signup-submit" type="submit">Create free workspace</button>
            </form>
            <p class="signup-footnote">Already have a workspace? Open your school address to sign in.</p>
        </section>
    </main>
</body>
</html>

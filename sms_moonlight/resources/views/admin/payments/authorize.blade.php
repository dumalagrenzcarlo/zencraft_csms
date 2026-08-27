<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Access Confirmation</title>
    <style>
        * { box-sizing: border-box; }
        body {
            align-items: center;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            font-family: Arial, sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 24px;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
            max-width: 440px;
            padding: 32px;
            width: 100%;
        }
        h1 { font-size: 24px; margin: 0 0 10px; }
        p { color: #64748b; line-height: 1.55; margin: 0 0 24px; }
        label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; }
        input {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 16px;
            padding: 12px 14px;
            width: 100%;
        }
        input:focus { border-color: #4f46e5; outline: 2px solid #e0e7ff; }
        .error {
            background: #fff1f2;
            border-radius: 10px;
            color: #be123c;
            font-size: 14px;
            margin-bottom: 16px;
            padding: 12px;
        }
        button {
            background: #4f46e5;
            border: 0;
            border-radius: 10px;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            font-weight: 700;
            margin-top: 18px;
            padding: 12px 16px;
            width: 100%;
        }
        a { color: #475569; display: block; font-size: 14px; margin-top: 18px; text-align: center; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Payment access required</h1>
        <p>
            Enter the password for the authorized payment administrator
            <strong>{{ $authorizedUsername ?: 'configured in the environment' }}</strong>
            to continue.
        </p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.payments.authorize') }}">
            @csrf
            <label for="password">Payment administrator password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password">
            <button type="submit">Unlock Payment Pages</button>
        </form>

        <a href="{{ route('moonshine.index') }}">Return to Dashboard</a>
    </main>
</body>
</html>

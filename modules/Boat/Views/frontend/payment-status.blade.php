<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payment Status') }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef1f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #1f2a37;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(16, 24, 40, 0.08);
            padding: 40px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 28px;
            color: #fff;
        }
        .icon.success { background: #2f9e5c; }
        .icon.error { background: #e0483c; }
        h1 {
            font-size: 18px;
            margin: 0 0 8px;
        }
        p {
            color: #6b7684;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon {{ $success ? 'success' : 'error' }}">{{ $success ? '✓' : '!' }}</div>
        <h1>{{ $success ? __('Success') : __('Something went wrong') }}</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>

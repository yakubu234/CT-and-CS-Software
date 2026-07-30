<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;background:#f4f6f9;font-family:Arial,sans-serif;color:#212529">
<div style="max-width:560px;margin:0 auto;padding:32px 16px">
    <div style="background:#fff;border-radius:8px;padding:32px;border:1px solid #dee2e6">
        <h1 style="font-size:22px;margin:0 0 16px">Staff login verification</h1>
        <p>Hello {{ $user->name }},</p>
        <p>Use this one-time verification code to complete your staff login:</p>
        <div style="font-size:34px;font-weight:bold;letter-spacing:10px;text-align:center;padding:20px;background:#eef4ff;border-radius:6px;margin:24px 0">
            {{ $code }}
        </div>
        <p>This code expires in {{ $expiresMinutes }} minutes and can only be used once.</p>
        <p style="color:#6c757d;font-size:13px;margin-bottom:0">If you did not attempt to sign in, do not share this code with anyone and contact the system administrator.</p>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">

    <div style="max-width: 500px; margin: auto; background: #ffffff; padding: 20px; border-radius: 8px;">

        <h2 style="color: #333;">Password Reset Request</h2>
        <p>Hello, {{ $user->name }}</p>

        <p>Your One-Time Password (OTP) is:</p>

        <h1 style="letter-spacing: 5px; color: #0d6efd;">{{ $otp }}</h1>

        <p>This OTP will expire in <b>10 minutes</b>.</p>

        <p>If you did not request a password reset, please ignore this email.</p>

    </div>

</body>
</html>

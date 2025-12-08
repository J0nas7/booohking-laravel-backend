<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Password Reset</title>
</head>

<body>
    <h1>Hello {{ $user->User_Name }},</h1>

    <p>You requested a password reset. Use the following token to reset your password:</p>

    <h2>{{ $token }}</h2>

    <p>If you did not request this, please ignore this email.</p>

    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>

</html>

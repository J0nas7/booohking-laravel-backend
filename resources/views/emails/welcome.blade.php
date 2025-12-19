<x-mail::message>
    # Welcome, {{ $user->name }}!

    Thanks for registering! Please verify your email by entering this token on the activation page.

    {{ $token }}

    If you did not register, please ignore this email.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>

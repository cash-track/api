<!DOCTYPE html>
<html lang="">
<head>
    <title></title>
</head>
<body>
    <h1>[[forgot_password_mail_hello]], {{ $user->name }} {{ $user->lastName }}</h1>
    <p>[[forgot_password_mail_line_1]] <a href="https://cash-track.app" target="_blank">Cash Track</a>.</p>
    <p>[[forgot_password_mail_line_2]]</p>
    <p></p>
    <p><b><a href="{{ $link }}" target="_blank">{{ $link }}</a></b></p>
    <p></p>
    <p>[[forgot_password_mail_footer]]</p>
</body>
</html>

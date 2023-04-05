<!DOCTYPE html>
<html lang="">
<head>
    <title></title>
</head>
<body>
    <h1>[[email_confirmation_mail_hello]], {{ $user->name }} {{ $user->lastName }}</h1>
    <p>[[email_confirmation_mail_line_1]] <a href="https://cash-track.app" target="_blank">Cash Track</a>.</p>
    <p>[[email_confirmation_mail_line_2]]</p>
    <p></p>
    <p><b><a href="{{ $link }}" target="_blank">{{ $link }}</a></b></p>
    <p></p>
    <p>[[email_confirmation_mail_footer]]</p>
</body>
</html>

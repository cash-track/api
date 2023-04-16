<!DOCTYPE html>
<html lang="">
<head>
    <title></title>
</head>
<body>
    <h1>[[wallet_share_mail_hello]], {{ $user->name }} {{ $user->lastName }}</h1>
    <p>{{ $sharer->name }} {{ $sharer->lastName }} [[wallet_share_mail_line_invited]] <a href="{{ $link }}" target="_blank">{{ $wallet->name }}</a> [[wallet_share_mail_line_invited_to]] <a href="https://cash-track.app" target="_blank">Cash Track</a>.</p>
    <p>[[wallet_share_mail_line_2]]</p>
    <p></p>
    <p>[[wallet_share_mail_footer]]</p>
</body>
</html>

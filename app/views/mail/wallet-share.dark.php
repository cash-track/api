<!DOCTYPE html>
<html lang="">
<head>
    <title></title>
</head>
<body>
    <h1>Hey, {{ $user->name }} {{ $user->lastName }}</h1>
    <p>{{ $sharer->name }} {{ $sharer->lastName }} invited you to wallet <a href="{{ $link }}" target="_blank">{{ $wallet->name }}</a> at <a href="https://cash-track.ml" target="_blank">Cash Track</a>.</p>
    <p>Now you can use it together.</p>
    <p></p>
    <p>If it's not you just ignore this.</p>
</body>
</html>

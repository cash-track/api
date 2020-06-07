<!DOCTYPE html>
<html lang="">
<head>
    <title></title>
</head>
<body>
    <h1>Hey, {{ $user->name }} {{ $user->lastName }}</h1>
    <p>Your email was used to register at <a href="https://cash-track.ml" target="_blank">Cash Track</a>.</p>
    <p>Click to the link bellow to confirm your account email:</p>
    <p></p>
    <p><b><a href="{{ $link }}" target="_blank">{{ $link }}</a></b></p>
    <p></p>
    <p>If it's not you just ignore this.</p>
</body>
</html>

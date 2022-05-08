<!DOCTYPE html>
<html lang="">
<head>
    <title></title>
</head>
<body>
    <h1>Hey, {{ $user->name }} {{ $user->lastName }}</h1>
    <p>Someone has been requested password reset at <a href="https://cash-track.ml" target="_blank">Cash Track</a>.</p>
    <p>Click to the link bellow to continue process of resetting your password:</p>
    <p></p>
    <p><b><a href="{{ $link }}" target="_blank">{{ $link }}</a></b></p>
    <p></p>
    <p>If it's not you then someone trying to access your account. We're keeping your account safe, no worries.</p>
</body>
</html>

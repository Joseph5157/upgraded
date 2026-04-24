<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ route('login', ['expired' => 1]) }}">
    <title>{{ config('app.name') }}</title>
    <script>
        window.location.replace(@json(route('login', ['expired' => 1])));
    </script>
</head>
<body>
    <p>Your session expired. Please sign in again.</p>
    <p><a href="{{ route('login', ['expired' => 1]) }}">Continue to sign in</a></p>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
</head>
<body>
    <h1>Two-Factor Authentication</h1>
    <p>Please confirm access to your account by entering the authentication code provided by your authenticator application or one of your recovery codes.</p>
</body>
</html>

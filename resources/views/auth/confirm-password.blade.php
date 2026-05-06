<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm Password</title>
</head>
<body>
    <p>Please confirm your password before continuing.</p>
    <form method="POST" action="{{ url('user/confirm-password') }}">
        @csrf
        <input type="password" name="password" required autocomplete="current-password">
        @error('password')
            <span>{{ $message }}</span>
        @enderror
        <button type="submit">Confirm</button>
    </form>
</body>
</html>

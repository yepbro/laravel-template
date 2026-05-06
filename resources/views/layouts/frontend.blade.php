<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
        @vite($vite ?? ['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background text-foreground">
        <div id="toast-root"></div>

        <main class="mx-auto min-h-screen max-w-6xl px-6 py-10">
            @yield('content')
        </main>
    </body>
</html>

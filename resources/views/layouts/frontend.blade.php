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
            <nav class="mb-8 flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm">
                <span class="text-sm font-medium text-muted-foreground">
                    Frontend mode
                </span>

                <a
                    class="{{ request()->is('spa*') ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-accent hover:text-accent-foreground' }} inline-flex rounded-md border border-border px-3 py-2 text-sm font-medium transition-colors"
                    href="/spa"
                >
                    Vue-only SPA
                </a>

                <a
                    class="{{ request()->is('islands*') ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-accent hover:text-accent-foreground' }} inline-flex rounded-md border border-border px-3 py-2 text-sm font-medium transition-colors"
                    href="/islands"
                >
                    Blade + Vue islands
                </a>
            </nav>

            @yield('content')
        </main>
    </body>
</html>

@extends('layouts.frontend')

@section('content')
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

    <div id="app"></div>
@endsection

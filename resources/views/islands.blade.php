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

    <section class="rounded-2xl border border-border bg-card p-8 shadow-sm">
        <span class="inline-flex rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
            Blade + Vue islands
        </span>

        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-foreground">
            Blade-first pages with focused Vue widgets
        </h1>

        <p class="mt-4 max-w-3xl text-sm leading-6 text-muted-foreground">
            This mode keeps the page rendered by Blade and mounts small Vue apps
            only where the UI benefits from it. It is useful when you want a
            traditional Laravel page flow without giving up modern Vue
            components, local state, router access, and shared notifications.
        </p>
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-3">
        <div data-island="form-demo"></div>
        <div data-island="table-demo"></div>
        <div data-island="toast-demo"></div>
    </section>
@endsection

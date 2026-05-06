@extends('layouts.frontend')

@section('content')
    <section class="flex min-h-[calc(100vh-5rem)] items-center justify-center">
        <div class="relative isolate w-full overflow-hidden rounded-[2rem] border border-border bg-card px-6 py-16 shadow-sm sm:px-10 lg:px-16 lg:py-24">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(15,23,42,0.06),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.12),transparent_32%),linear-gradient(135deg,rgba(255,255,255,0.96),rgba(248,250,252,0.9))]"></div>
            <div class="absolute -left-20 top-10 -z-10 h-52 w-52 rounded-full bg-primary/8 blur-3xl"></div>
            <div class="absolute -bottom-16 right-0 -z-10 h-64 w-64 rounded-full bg-foreground/6 blur-3xl"></div>

            <div class="mx-auto flex max-w-5xl flex-col items-center gap-12 text-center">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full border border-border bg-background/80 px-4 py-1.5 text-xs font-medium tracking-[0.24em] text-muted-foreground uppercase backdrop-blur">
                        Laravel starter
                    </span>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                        Laravel Frontend Playground
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg">
                        A compact starter for trying different frontend approaches in Laravel.
                    </p>

                    <p class="mx-auto mt-4 max-w-3xl text-sm leading-7 text-muted-foreground sm:text-base">
                        This landing page intentionally stays quiet: one name, one short description, and one polished visual to greet the project before you dive into the implementation details.
                    </p>
                </div>

                <div class="w-full max-w-4xl rounded-[1.75rem] border border-border/80 bg-background/80 p-4 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur sm:p-6">
                    <div class="overflow-hidden rounded-[1.35rem] border border-border bg-[linear-gradient(160deg,rgba(15,23,42,0.96),rgba(30,41,59,0.92))] p-6 sm:p-8">
                        <svg
                            aria-hidden="true"
                            class="h-auto w-full"
                            viewBox="0 0 960 540"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <defs>
                                <linearGradient id="hero-grid" x1="160" y1="60" x2="840" y2="460" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="white" stop-opacity="0.28" />
                                    <stop offset="1" stop-color="white" stop-opacity="0.04" />
                                </linearGradient>
                                <linearGradient id="hero-card" x1="320" y1="120" x2="640" y2="400" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#F8FAFC" />
                                    <stop offset="1" stop-color="#CBD5E1" />
                                </linearGradient>
                                <linearGradient id="hero-accent" x1="260" y1="160" x2="720" y2="360" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#60A5FA" />
                                    <stop offset="1" stop-color="#C084FC" />
                                </linearGradient>
                            </defs>

                            <rect x="1" y="1" width="958" height="538" rx="28" stroke="url(#hero-grid)" />
                            <path d="M96 132H864" stroke="url(#hero-grid)" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M96 408H864" stroke="url(#hero-grid)" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M192 72V468" stroke="url(#hero-grid)" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M768 72V468" stroke="url(#hero-grid)" stroke-width="1.5" stroke-linecap="round" />

                            <rect x="236" y="120" width="488" height="300" rx="28" fill="rgba(15,23,42,0.28)" />
                            <rect x="256" y="140" width="448" height="260" rx="24" fill="url(#hero-card)" />
                            <rect x="286" y="174" width="168" height="16" rx="8" fill="#94A3B8" />
                            <rect x="286" y="206" width="242" height="12" rx="6" fill="#CBD5E1" />
                            <rect x="286" y="230" width="202" height="12" rx="6" fill="#E2E8F0" />

                            <rect x="286" y="278" width="388" height="84" rx="20" fill="url(#hero-accent)" fill-opacity="0.95" />
                            <circle cx="334" cy="320" r="24" fill="white" fill-opacity="0.94" />
                            <path d="M380 310H620" stroke="white" stroke-width="12" stroke-linecap="round" />
                            <path d="M380 334H566" stroke="white" stroke-opacity="0.78" stroke-width="10" stroke-linecap="round" />

                            <circle cx="708" cy="118" r="54" fill="#60A5FA" fill-opacity="0.16" />
                            <circle cx="746" cy="166" r="20" fill="#C084FC" fill-opacity="0.34" />
                            <circle cx="224" cy="410" r="34" fill="#F8FAFC" fill-opacity="0.14" />
                            <path d="M146 344C196 296 252 296 304 344" stroke="#93C5FD" stroke-width="10" stroke-linecap="round" stroke-opacity="0.74" />
                            <path d="M650 392C692 350 736 350 782 392" stroke="#C084FC" stroke-width="10" stroke-linecap="round" stroke-opacity="0.68" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

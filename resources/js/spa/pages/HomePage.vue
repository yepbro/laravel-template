<script setup lang="ts">
import { useHead } from '@unhead/vue';
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import { fetchCurrentUser, logout } from '@/auth/api/client';

const router = useRouter();

const sessionPending = ref(true);
const isAuthenticated = ref(false);

useHead({
    title: 'Laravel Frontend Playground',
});

onMounted(async () => {
    try {
        await fetchCurrentUser();
        isAuthenticated.value = true;
    } catch {
        isAuthenticated.value = false;
    } finally {
        sessionPending.value = false;
    }
});

async function handleLogout(): Promise<void> {
    await logout();
    isAuthenticated.value = false;
    await router.push('/');
}
</script>

<template>
    <div
        class="relative isolate mx-auto w-full max-w-[1200px] px-4 sm:px-10 lg:px-14"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-24 left-1/2 -z-10 h-[28rem] w-[min(100%,48rem)] -translate-x-1/2 bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.07),transparent_65%)]"
        ></div>
        <div
            aria-hidden="true"
            class="pointer-events-none absolute top-40 right-[-12%] -z-10 h-64 w-64 rounded-full bg-zinc-200/40 blur-3xl"
        ></div>

        <header
            class="flex flex-wrap items-center justify-between gap-4 py-2 sm:gap-6"
        >
            <div class="flex items-center gap-2.5">
                <span
                    class="size-2 shrink-0 rounded-sm bg-indigo-600 shadow-[0_0_0_1px_rgba(255,255,255,0.9)_inset]"
                    aria-hidden="true"
                ></span>
                <span
                    class="text-base font-semibold tracking-tight text-zinc-900 sm:text-lg"
                >
                    Laravel Frontend Playground
                </span>
            </div>

            <nav
                class="flex min-h-10 items-center gap-2 sm:gap-3"
                aria-label="Account"
            >
                <template v-if="sessionPending">
                    <span class="w-32" aria-hidden="true"></span>
                </template>
                <template v-else-if="!isAuthenticated">
                    <RouterLink
                        data-testid="home-login-link"
                        :to="{ name: 'auth.login' }"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium text-zinc-600 transition-colors duration-200 hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        Login
                    </RouterLink>
                    <RouterLink
                        data-testid="home-register-link"
                        :to="{ name: 'auth.register' }"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-600/20 transition-colors duration-200 hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        Register
                    </RouterLink>
                </template>
                <template v-else>
                    <button
                        type="button"
                        data-testid="home-logout-button"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium text-zinc-600 transition-colors duration-200 hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        @click="handleLogout"
                    >
                        Logout
                    </button>
                </template>
            </nav>
        </header>

        <section
            class="flex flex-col items-center pt-12 pb-16 text-center sm:pt-16 sm:pb-20"
        >
            <h1
                class="max-w-3xl text-3xl leading-tight font-bold tracking-tight text-zinc-950 sm:text-5xl sm:leading-[1.08] lg:text-6xl"
            >
                Clean Laravel frontends
            </h1>

            <p
                class="mt-5 max-w-[36rem] text-base leading-relaxed text-zinc-600 sm:text-lg"
            >
                A clean starting point for building modern Laravel applications
                with Tailwind and contemporary UI practices.
            </p>

            <div class="mt-14 w-full max-w-lg sm:mt-16" aria-hidden="true">
                <div
                    class="rounded-3xl border border-zinc-200/80 bg-gradient-to-b from-white via-zinc-50/90 to-zinc-100/50 p-5 shadow-[0_20px_50px_-20px_rgba(24,24,27,0.18)] sm:p-6"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <span
                            class="size-2.5 rounded-full bg-red-400/90"
                        ></span>
                        <span
                            class="size-2.5 rounded-full bg-amber-400/90"
                        ></span>
                        <span
                            class="size-2.5 rounded-full bg-emerald-400/90"
                        ></span>
                        <span
                            class="ml-auto font-mono text-[10px] tracking-wide text-zinc-400 uppercase"
                        >
                            HomePage.vue
                        </span>
                    </div>
                    <div
                        class="rounded-2xl border border-zinc-100 bg-white/90 px-4 py-4 text-left shadow-inner shadow-zinc-100/80"
                    >
                        <p
                            class="font-mono text-[11px] leading-6 text-zinc-500 sm:text-xs sm:leading-7"
                        >
                            <span class="text-indigo-600">&lt;section</span>
                            <span class="text-zinc-700">&nbsp;class=</span
                            ><span class="text-emerald-700">"…"</span>
                            <span class="text-indigo-600">&gt;</span>
                        </p>
                        <p
                            class="mt-1 ml-3 font-mono text-[11px] leading-6 text-zinc-500 sm:text-xs sm:leading-7"
                        >
                            <span class="text-indigo-600">&lt;h1&gt;</span
                            ><span class="text-zinc-800">Headline</span
                            ><span class="text-indigo-600">&lt;/h1&gt;</span>
                        </p>
                        <p
                            class="mt-1 ml-3 font-mono text-[11px] leading-6 sm:text-xs sm:leading-7"
                        >
                            <span class="text-zinc-500"
                                >// quiet, intentional layout</span
                            >
                        </p>
                    </div>
                </div>
            </div>

            <p
                class="mt-14 inline-flex max-w-full items-center gap-x-2 rounded-full border border-zinc-200/90 bg-white/80 px-3 py-1 text-[10px] font-semibold tracking-[0.18em] text-zinc-500 uppercase shadow-sm backdrop-blur-sm sm:text-[11px]"
            >
                <span>Laravel Starter</span>
                <span class="text-zinc-300" aria-hidden="true">·</span>
                <span>Frontend Playground</span>
                <span class="text-zinc-300" aria-hidden="true">·</span>
                <span>Minimal UI</span>
            </p>
        </section>
    </div>
</template>

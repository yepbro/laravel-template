<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

import { fetchCurrentUser } from '@/auth/api/client';
import AppHeaderUserToolbar from '@/layouts/AppHeaderUserToolbar.vue';

const sessionPending = ref(true);
const isAuthenticated = ref(false);

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

function handleUserLoggedOut(): void {
    isAuthenticated.value = false;
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
            <slot name="header-brand">
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
            </slot>

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
                    <AppHeaderUserToolbar
                        :redirect-after-logout="'/'"
                        @logged-out="handleUserLoggedOut"
                    />
                </template>
            </nav>
        </header>

        <slot />
    </div>
</template>

<script setup lang="ts">
import { useHead } from '@unhead/vue';

useHead({
    title: 'Laravel Frontend Playground',
});
</script>

<template>
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
            A clean starting point for building modern Laravel applications with
            Tailwind and contemporary UI practices.
        </p>

        <div class="mt-14 w-full max-w-lg sm:mt-16" aria-hidden="true">
            <div
                class="rounded-3xl border border-zinc-200/80 bg-gradient-to-b from-white via-zinc-50/90 to-zinc-100/50 p-5 shadow-[0_20px_50px_-20px_rgba(24,24,27,0.18)] sm:p-6"
            >
                <div class="mb-4 flex items-center gap-2">
                    <span class="size-2.5 rounded-full bg-red-400/90"></span>
                    <span class="size-2.5 rounded-full bg-amber-400/90"></span>
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
</template>

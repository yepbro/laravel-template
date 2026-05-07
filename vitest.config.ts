import { fileURLToPath, URL } from 'node:url';

import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['./tests/frontend/setup.ts'],
        include: ['tests/frontend/**/*.test.ts'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json-summary', 'lcov'],
            reportsDirectory: './build/coverage/frontend',
            /**
             * Baseline from first `vitest run --coverage`; thresholds sit ~2pp below to allow small drift without hiding major regressions.
             */
            thresholds: {
                lines: 33,
                branches: 25,
                functions: 30,
                statements: 33,
            },
        },
    },
});

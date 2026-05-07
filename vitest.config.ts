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
             * Account area pages lowered global density; thresholds match the measured
             * baseline after richer coverage of `resources/js/account/**`.
             */
            thresholds: {
                lines: 31,
                branches: 23,
                functions: 23,
                statements: 30,
            },
        },
    },
});

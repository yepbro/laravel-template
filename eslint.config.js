import prettier from 'eslint-config-prettier/flat';
import vue from 'eslint-plugin-vue';

import {
    defineConfigWithVueTs,
    vueTsConfigs,
} from '@vue/eslint-config-typescript';

export default defineConfigWithVueTs(
    {
        ignores: [
            'vendor',
            'node_modules',
            'public/build',
            'public/mockServiceWorker.js',
            'bootstrap/cache',
            'bootstrap/ssr',
            'storage',
            'build',
            'coverage',
        ],
    },
    vue.configs['flat/recommended'],
    vueTsConfigs.recommended,
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/require-default-prop': 'off',
        },
    },
    prettier,
);

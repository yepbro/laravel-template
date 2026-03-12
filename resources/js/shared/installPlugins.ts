import { VueQueryPlugin } from '@tanstack/vue-query';
import { MotionPlugin } from '@vueuse/motion';
import { vMaska } from 'maska/vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import type { App as VueApp } from 'vue';
import type { Router } from 'vue-router';

import { createSharedHead } from '@/shared/head';
import { createSharedI18n } from '@/shared/i18n';
import { createSharedQueryClient } from '@/shared/queryClient';

export function installSharedPlugins(app: VueApp, router: Router): void {
    const pinia = createPinia();

    pinia.use(piniaPluginPersistedstate);

    app.use(createSharedHead());
    app.use(createSharedI18n());
    app.use(pinia);
    app.use(router);
    app.use(MotionPlugin);
    app.use(VueQueryPlugin, {
        queryClient: createSharedQueryClient(),
    });
    app.directive('maska', vMaska);
}

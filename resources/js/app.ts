import './bootstrap';

import { createApp } from 'vue';

import App from '@/App.vue';
import { installSharedPlugins } from '@/shared/installPlugins';
import { startMocking } from '@/shared/mocks/browser';
import { mountToaster } from '@/shared/mountToaster';
import { router } from '@/spa/router';

startMocking().finally(() => {
    const app = createApp(App);

    installSharedPlugins(app, router);
    mountToaster();

    router.isReady().then(() => {
        app.mount('#app');
    });
});

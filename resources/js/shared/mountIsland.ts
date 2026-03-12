import { createApp, type Component } from 'vue';

import { createIslandsRouter } from '@/shared/createRouter';
import { installSharedPlugins } from '@/shared/installPlugins';

interface MountIslandOptions {
    component: Component;
    routeName: string;
    props?: Record<string, unknown>;
}

export async function mountIsland(
    targetSelector: string,
    options: MountIslandOptions,
): Promise<void> {
    const target = document.querySelector(targetSelector);

    if (!(target instanceof HTMLElement)) {
        return;
    }

    const router = createIslandsRouter(options.routeName);
    const app = createApp(options.component, options.props ?? {});

    installSharedPlugins(app, router);

    await router.push('/');
    await router.isReady();

    app.mount(target);
}

import { setupWorker } from 'msw/browser';

import { handlers } from '@/shared/mocks/handlers';

let workerPromise: Promise<void> | undefined;

export function startMocking(): Promise<void> {
    if (import.meta.env.VITE_ENABLE_MSW !== 'true') {
        return Promise.resolve();
    }

    if (!workerPromise) {
        const worker = setupWorker(...handlers);

        workerPromise = worker
            .start({
                onUnhandledRequest: 'bypass',
            })
            .then(() => undefined);
    }

    return workerPromise;
}

let workerPromise: Promise<void> | undefined;

export function startMocking(): Promise<void> {
    if (import.meta.env.VITE_ENABLE_MSW !== 'true') {
        return Promise.resolve();
    }

    if (!workerPromise) {
        workerPromise = import('msw/browser')
            .then(async ({ setupWorker }) => {
                const { handlers } = await import('@/shared/mocks/handlers');
                const worker = setupWorker(...handlers);

                return worker.start({
                    onUnhandledRequest: 'bypass',
                });
            })
            .then(() => undefined);
    }

    return workerPromise;
}

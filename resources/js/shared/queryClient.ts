import { QueryClient } from '@tanstack/vue-query';

export function createSharedQueryClient() {
    return new QueryClient({
        defaultOptions: {
            queries: {
                staleTime: 60_000,
                refetchOnWindowFocus: false,
                retry: 1,
            },
        },
    });
}

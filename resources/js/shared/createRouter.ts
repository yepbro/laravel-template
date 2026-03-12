import {
    createMemoryHistory,
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
    type Router,
} from 'vue-router';

export function createIslandsRouter(routeName: string): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            {
                path: '/',
                name: routeName,
                component: {
                    template: '<div />',
                },
            },
        ],
    });
}

export function createSpaRouter(routes: RouteRecordRaw[]): Router {
    return createRouter({
        history: createWebHistory(),
        routes,
        scrollBehavior() {
            return {
                top: 0,
            };
        },
    });
}

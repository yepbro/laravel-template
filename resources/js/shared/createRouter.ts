import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
    type Router,
} from 'vue-router';

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

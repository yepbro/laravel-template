import 'vue-router';

declare module 'vue-router' {
    interface RouteMeta {
        layout?: 'guest' | 'account' | 'demo' | 'landing';
        requiresAuth?: boolean;
    }
}

import type { RouteRecordRaw } from 'vue-router';

import { createSpaRouter } from '@/shared/createRouter';
import FormPage from '@/spa/pages/FormPage.vue';
import OverviewPage from '@/spa/pages/OverviewPage.vue';
import TablePage from '@/spa/pages/TablePage.vue';
import ToastPage from '@/spa/pages/ToastPage.vue';

const routes: RouteRecordRaw[] = [
    {
        path: '/spa',
        name: 'spa.overview',
        component: OverviewPage,
    },
    {
        path: '/spa/form',
        name: 'spa.form',
        component: FormPage,
    },
    {
        path: '/spa/table',
        name: 'spa.table',
        component: TablePage,
    },
    {
        path: '/spa/toast',
        name: 'spa.toast',
        component: ToastPage,
    },
];

export const router = createSpaRouter(routes);

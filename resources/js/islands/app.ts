import '@/bootstrap';

import FormExample from '@/shared/components/examples/FormExample.vue';
import TableExample from '@/shared/components/examples/TableExample.vue';
import ToastExample from '@/shared/components/examples/ToastExample.vue';
import { startMocking } from '@/shared/mocks/browser';
import { mountIsland } from '@/shared/mountIsland';
import { mountToaster } from '@/shared/mountToaster';

const modeLabel = 'Blade + Vue islands';

startMocking().finally(() => {
    mountToaster();

    void Promise.all([
        mountIsland('[data-island="form-demo"]', {
            component: FormExample,
            routeName: 'islands.form',
            props: {
                modeLabel,
            },
        }),
        mountIsland('[data-island="table-demo"]', {
            component: TableExample,
            routeName: 'islands.table',
            props: {
                modeLabel,
            },
        }),
        mountIsland('[data-island="toast-demo"]', {
            component: ToastExample,
            routeName: 'islands.toast',
            props: {
                modeLabel,
            },
        }),
    ]);
});

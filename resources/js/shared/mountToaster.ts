import { createApp } from 'vue';

import ToasterRoot from '@/shared/components/ToasterRoot.vue';

export function mountToaster(targetId = 'toast-root'): void {
    const target = document.getElementById(targetId);

    if (!target) {
        return;
    }

    createApp(ToasterRoot).mount(target);
}

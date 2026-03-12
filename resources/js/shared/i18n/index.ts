import { createI18n } from 'vue-i18n';

import { en } from '@/shared/i18n/messages/en';

export function createSharedI18n() {
    return createI18n({
        legacy: false,
        locale: 'en',
        fallbackLocale: 'en',
        messages: {
            en,
        },
    });
}

import type { RouteRecordRaw } from 'vue-router';

import ConfirmPasswordPage from '@/auth/pages/ConfirmPasswordPage.vue';
import ForgotPasswordPage from '@/auth/pages/ForgotPasswordPage.vue';
import LoginPage from '@/auth/pages/LoginPage.vue';
import RegisterPage from '@/auth/pages/RegisterPage.vue';
import ResetPasswordPage from '@/auth/pages/ResetPasswordPage.vue';
import SecuritySettingsPage from '@/auth/pages/SecuritySettingsPage.vue';
import TwoFactorChallengePage from '@/auth/pages/TwoFactorChallengePage.vue';
import VerifyEmailPage from '@/auth/pages/VerifyEmailPage.vue';
import VerifyPhonePage from '@/auth/pages/VerifyPhonePage.vue';
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
    {
        path: '/spa/auth/login',
        name: 'auth.login',
        component: LoginPage,
    },
    {
        path: '/spa/auth/register',
        name: 'auth.register',
        component: RegisterPage,
    },
    {
        path: '/spa/auth/forgot-password',
        name: 'auth.forgot-password',
        component: ForgotPasswordPage,
    },
    {
        path: '/spa/auth/reset-password/:token?',
        name: 'auth.reset-password',
        component: ResetPasswordPage,
    },
    {
        path: '/spa/auth/verify-email',
        name: 'auth.verify-email',
        component: VerifyEmailPage,
    },
    {
        path: '/spa/auth/verify-phone',
        name: 'auth.verify-phone',
        component: VerifyPhonePage,
    },
    {
        path: '/spa/auth/two-factor-challenge',
        name: 'auth.two-factor-challenge',
        component: TwoFactorChallengePage,
    },
    {
        path: '/spa/auth/confirm-password',
        name: 'auth.confirm-password',
        component: ConfirmPasswordPage,
    },
    {
        path: '/spa/auth/security',
        name: 'auth.security',
        component: SecuritySettingsPage,
    },
];

export const router = createSpaRouter(routes);

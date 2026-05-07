import type { RouteRecordRaw } from 'vue-router';

import AccountDeletePage from '@/account/pages/AccountDeletePage.vue';
import AccountLoginCredentialsPage from '@/account/pages/AccountLoginCredentialsPage.vue';
import AccountPasswordPage from '@/account/pages/AccountPasswordPage.vue';
import AccountProfilePage from '@/account/pages/AccountProfilePage.vue';
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
        meta: { layout: 'demo' },
    },
    {
        path: '/spa/form',
        name: 'spa.form',
        component: FormPage,
        meta: { layout: 'demo' },
    },
    {
        path: '/spa/table',
        name: 'spa.table',
        component: TablePage,
        meta: { layout: 'demo' },
    },
    {
        path: '/spa/toast',
        name: 'spa.toast',
        component: ToastPage,
        meta: { layout: 'demo' },
    },
    {
        path: '/spa/auth/login',
        name: 'auth.login',
        component: LoginPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/register',
        name: 'auth.register',
        component: RegisterPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/forgot-password',
        name: 'auth.forgot-password',
        component: ForgotPasswordPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/reset-password/:token?',
        name: 'auth.reset-password',
        component: ResetPasswordPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/verify-email',
        name: 'auth.verify-email',
        component: VerifyEmailPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/verify-phone',
        name: 'auth.verify-phone',
        component: VerifyPhonePage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/two-factor-challenge',
        name: 'auth.two-factor-challenge',
        component: TwoFactorChallengePage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/confirm-password',
        name: 'auth.confirm-password',
        component: ConfirmPasswordPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/spa/auth/security',
        name: 'auth.security',
        component: SecuritySettingsPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/account',
        redirect: { name: 'account.profile' },
    },
    {
        path: '/account/profile',
        name: 'account.profile',
        component: AccountProfilePage,
        meta: { layout: 'account', requiresAuth: true },
    },
    {
        path: '/account/password',
        name: 'account.password',
        component: AccountPasswordPage,
        meta: { layout: 'account', requiresAuth: true },
    },
    {
        path: '/account/login-credentials',
        name: 'account.login-credentials',
        component: AccountLoginCredentialsPage,
        meta: { layout: 'account', requiresAuth: true },
    },
    {
        path: '/account/delete',
        name: 'account.delete',
        component: AccountDeletePage,
        meta: { layout: 'account', requiresAuth: true },
    },
];

export const router = createSpaRouter(routes);

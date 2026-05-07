import type { RouteLocationRaw, RouteRecordRaw } from 'vue-router';

import AccountDeletePage from '@/account/pages/AccountDeletePage.vue';
import AccountLoginCredentialsPage from '@/account/pages/AccountLoginCredentialsPage.vue';
import AccountPasswordPage from '@/account/pages/AccountPasswordPage.vue';
import AccountProfilePage from '@/account/pages/AccountProfilePage.vue';
import DashboardPage from '@/account/pages/DashboardPage.vue';
import { clearCurrentUserCache, fetchCurrentUser } from '@/auth/api/client';
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

/** Canonical public auth routes (Batch B). Legacy `/spa/auth/*` URLs redirect here. */
const canonicalGuestAuthRoutes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'auth.login',
        component: LoginPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/register',
        name: 'auth.register',
        component: RegisterPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/forgot-password',
        name: 'auth.forgot-password',
        component: ForgotPasswordPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/reset-password/:token?',
        name: 'auth.reset-password',
        component: ResetPasswordPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/email/verify',
        name: 'auth.verify-email',
        component: VerifyEmailPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/phone/verify',
        name: 'auth.verify-phone',
        component: VerifyPhonePage,
        meta: { layout: 'guest' },
    },
    {
        path: '/two-factor-challenge',
        name: 'auth.two-factor-challenge',
        component: TwoFactorChallengePage,
        meta: { layout: 'guest' },
    },
    {
        path: '/user/confirm-password',
        name: 'auth.confirm-password',
        component: ConfirmPasswordPage,
        meta: { layout: 'guest' },
    },
    {
        path: '/confirm-password',
        redirect: { path: '/user/confirm-password' },
    },
];

const legacySpaAuthRedirects: RouteRecordRaw[] = [
    { path: '/spa/auth/login', redirect: '/login' },
    { path: '/spa/auth/register', redirect: '/register' },
    { path: '/spa/auth/forgot-password', redirect: '/forgot-password' },
    {
        path: '/spa/auth/reset-password/:token?',
        redirect: (to): RouteLocationRaw => {
            const raw = to.params.token;
            const token =
                typeof raw === 'string'
                    ? raw
                    : Array.isArray(raw)
                      ? raw[0]
                      : '';
            const path =
                token !== '' ? `/reset-password/${token}` : '/reset-password';

            return to.hash !== '' && to.hash !== undefined
                ? {
                      path,
                      query: { ...to.query },
                      hash: to.hash,
                  }
                : {
                      path,
                      query: { ...to.query },
                  };
        },
    },
    { path: '/spa/auth/verify-email', redirect: '/email/verify' },
    { path: '/spa/auth/verify-phone', redirect: '/phone/verify' },
    {
        path: '/spa/auth/two-factor-challenge',
        redirect: '/two-factor-challenge',
    },
    {
        path: '/spa/auth/confirm-password',
        redirect: '/user/confirm-password',
    },
    { path: '/spa/auth/security', redirect: '/account/security' },
];

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
    ...canonicalGuestAuthRoutes,
    ...legacySpaAuthRedirects,
    {
        path: '/account',
        name: 'account.dashboard',
        component: DashboardPage,
        meta: { layout: 'account', requiresAuth: true },
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
        path: '/account/security',
        name: 'auth.security',
        component: SecuritySettingsPage,
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

router.beforeEach(async (to) => {
    if (to.meta.requiresAuth !== true) {
        return true;
    }

    try {
        await fetchCurrentUser();

        return true;
    } catch {
        clearCurrentUserCache();

        return {
            path: '/login',
            query: { redirect: to.fullPath },
        };
    }
});

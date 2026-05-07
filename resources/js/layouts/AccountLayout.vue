<script setup lang="ts">
import { useHead } from '@unhead/vue';
import { LogOut } from 'lucide-vue-next';
import { RouterLink, useRouter } from 'vue-router';

import { logout } from '@/auth/api/client';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const router = useRouter();

useHead({
    title: () => 'Account',
});

async function handleLogout(): Promise<void> {
    await logout();
    await router.push('/spa/auth/login');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <header
            class="flex items-center justify-between gap-4 border-b border-border bg-card px-4 py-3 shadow-sm sm:px-6"
        >
            <RouterLink
                class="text-sm font-semibold tracking-tight text-foreground hover:underline"
                to="/spa"
            >
                {{ $t('app.heading') }}
            </RouterLink>

            <div class="flex items-center gap-3">
                <span class="hidden text-sm text-muted-foreground sm:inline">
                    Account
                </span>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            class="relative size-9 rounded-full p-0"
                        >
                            <Avatar class="size-9">
                                <AvatarFallback class="text-xs font-medium">
                                    AC
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-56"
                        align="end"
                        :side-offset="8"
                    >
                        <DropdownMenuLabel>Account menu</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            @click="router.push('/account/profile')"
                        >
                            Profile
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            @click="router.push('/account/password')"
                        >
                            Password
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            @click="router.push('/account/login-credentials')"
                        >
                            Login credentials
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant="destructive"
                            @click="router.push('/account/delete')"
                        >
                            Delete account
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            data-testid="account-logout"
                            @click="handleLogout"
                        >
                            <LogOut class="size-4" />
                            Log out
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>

        <div class="flex-1">
            <slot />
        </div>
    </div>
</template>

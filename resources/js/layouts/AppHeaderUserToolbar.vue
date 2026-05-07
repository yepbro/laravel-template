<script setup lang="ts">
import { LogOut } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import type { RouteLocationRaw } from 'vue-router';
import { useRouter } from 'vue-router';

import { fetchCurrentUser, logout } from '@/auth/api/client';
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

const props = defineProps<{
    redirectAfterLogout: RouteLocationRaw;
}>();

const emit = defineEmits<{
    loggedOut: [];
}>();

const router = useRouter();

const displayName = ref('Account');
const avatarInitials = ref('AC');

function initialsFromName(label: string): string {
    const parts = label.trim().split(/\s+/).filter(Boolean);

    if (parts.length >= 2) {
        const first = parts[0]!.charAt(0);
        const last = parts[parts.length - 1]!.charAt(0);

        return (first + last).toUpperCase();
    }

    if (parts.length === 1 && parts[0]!.length >= 2) {
        return parts[0]!.slice(0, 2).toUpperCase();
    }

    return 'AC';
}

onMounted(async () => {
    try {
        const user = await fetchCurrentUser();
        const label =
            user.name !== ''
                ? user.name
                : (user.email.split('@')[0] ?? 'Account');

        displayName.value = label;
        avatarInitials.value = initialsFromName(label);
    } catch {
        displayName.value = 'Account';
        avatarInitials.value = 'AC';
    }
});

async function handleLogout(): Promise<void> {
    await logout();
    emit('loggedOut');
    await router.push(props.redirectAfterLogout);
}
</script>

<template>
    <div class="flex items-center gap-3">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    type="button"
                    class="group flex h-9 cursor-pointer items-center gap-2 rounded-md px-1.5 font-normal sm:px-2"
                >
                    <span
                        class="hidden cursor-pointer text-sm tracking-tight text-muted-foreground group-hover:text-foreground group-hover:underline sm:inline"
                    >
                        {{ displayName }}
                    </span>
                    <span class="relative shrink-0 rounded-full p-0">
                        <Avatar class="size-9">
                            <AvatarFallback class="text-xs font-medium">
                                {{ avatarInitials }}
                            </AvatarFallback>
                        </Avatar>
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56" align="end" :side-offset="8">
                <DropdownMenuLabel>Account menu</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="router.push('/account')">
                    Dashboard
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="router.push('/account/profile')">
                    Profile
                </DropdownMenuItem>
                <DropdownMenuItem @click="router.push('/account/security')">
                    Security
                </DropdownMenuItem>
                <DropdownMenuItem @click="router.push('/account/password')">
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
            </DropdownMenuContent>
        </DropdownMenu>
        <Button
            variant="ghost"
            size="icon"
            class="shrink-0"
            type="button"
            data-testid="header-logout-button"
            aria-label="Log out"
            @click="handleLogout"
        >
            <LogOut class="size-4" />
        </Button>
    </div>
</template>

<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-2 p-6">
        <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p class="text-sm text-muted-foreground">
            Signed-in home for your account. Use the menu to manage your profile
            and security settings.
        </p>

        <Card class="mt-6">
            <CardHeader>
                <CardTitle>Welcome</CardTitle>
                <CardDescription>
                    This area is intentionally minimal; build your account
                    experience here step by step.
                </CardDescription>
            </CardHeader>
            <CardContent />
        </Card>
    </div>
</template>

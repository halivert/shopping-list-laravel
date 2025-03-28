<script setup lang="ts">
import { Head } from "@inertiajs/vue3"
import { Button } from "@/components/ui/button"
import AuthBase from "@/layouts/AuthLayout.vue"

defineProps<{
    status?: string
    canResetPassword: boolean
}>()
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Click the following button to log in"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div class="flex flex-col gap-6">
            <Button
                as="a"
                class="mt-1 w-full"
                variant="secondary"
                :href="route('google.login')"
            >
                Log in with Google
            </Button>
        </div>
    </AuthBase>
</template>

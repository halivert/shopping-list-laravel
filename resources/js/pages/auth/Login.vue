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
        title="Inicia sesión"
        description="Da click en el siguiente botón para iniciar sesión"
    >
        <Head title="Inicio de sesión" />

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
                Inicia sesión con Google
            </Button>
        </div>
    </AuthBase>
</template>

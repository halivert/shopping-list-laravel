<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3"

import { Button } from "@/components/ui/button"
import AuthBase from "@/layouts/AuthLayout.vue"
import AppInput from "@/components/ui/input/Input.vue"

defineProps<{
    local?: boolean
    status?: string
    canResetPassword: boolean
}>()

const loginForm = useForm({
    email: "",
})

function handleSubmit() {
    loginForm.post(route("local-login"))
}
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

        <form v-if="local" @submit.prevent="handleSubmit">
            <AppInput placeholder="Login local" v-model="loginForm.email" />
        </form>
    </AuthBase>
</template>

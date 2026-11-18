<script setup lang="ts">
import { computed } from "vue"
import { Head, Link } from "@inertiajs/vue3"

import AppButton from "@/components/ui/button/Button.vue"
import AppLogoIcon from "@/components/AppLogoIcon.vue"

/*
 * Rendered by the exception handler (bootstrap/app.php) for 403, 404, 419,
 * 500 and 503. Standalone on purpose: a 404 for an unknown URL never goes
 * through the web middleware, so shared props (auth, sidebar) may be missing.
 */
const props = defineProps<{
    status: number
}>()

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: "No tienes acceso",
        description: "No tienes permiso para ver esta página.",
    },
    404: {
        title: "Página no encontrada",
        description:
            "La página que buscas no existe o fue eliminada.",
    },
    419: {
        title: "La sesión expiró",
        description: "Recarga la página e inténtalo de nuevo.",
    },
    500: {
        title: "Algo salió mal",
        description: "Ocurrió un error en el servidor. Inténtalo más tarde.",
    },
    503: {
        title: "En mantenimiento",
        description: "Volvemos en un momento.",
    },
}

const message = computed(
    () =>
        messages[props.status] ?? {
            title: "Algo salió mal",
            description: "Inténtalo de nuevo.",
        }
)
</script>

<template>
    <Head :title="message.title" />

    <main
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background px-4 text-center"
    >
        <AppLogoIcon class="size-12 fill-current text-foreground" />

        <div class="space-y-2">
            <p class="text-sm font-medium text-muted-foreground">
                Error {{ status }}
            </p>
            <h1 class="text-2xl font-semibold">{{ message.title }}</h1>
            <p class="text-muted-foreground">{{ message.description }}</p>
        </div>

        <AppButton :as="Link" href="/" class="h-11 px-6">
            Ir al inicio
        </AppButton>
    </main>
</template>

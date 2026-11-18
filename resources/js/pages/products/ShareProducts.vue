<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3"

import type { Access } from "@/types/Access"
import type { BreadcrumbItem } from "@/types"
import AppLayout from "@/layouts/AppLayout.vue"
import AppInput from "@/components/ui/input/Input.vue"
import AppInputError from "@/components/InputError.vue"
import AppLabel from "@/components/ui/label/Label.vue"
import AppButton from "@/components/ui/button/Button.vue"
import ConfirmDialog from "@/components/ConfirmDialog.vue"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Inicio",
        href: "/",
    },
    {
        title: "Compartir productos",
        href: "",
    },
]

defineProps<{
    sharedWith: Access[]
    sharedBy: Access[]
    requests: Access[]
}>()

const addUserForm = useForm({
    email: "",
})

function removeAccess(access: Access) {
    router.delete(route("products-share.destroy", access.id), {
        preserveScroll: true,
    })
}

function handleSubmit() {
    addUserForm.post(route("products-share.store"), {
        onSuccess: () => addUserForm.reset(),
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Compartir productos" />
        <section class="px-4 py-2 max-w-prose space-y-3 min-w-0">
            <h1 class="text-2xl">Compartir productos</h1>

            <p>
                Puedes compartir tus productos con alguien más solo agrega su
                correo electrónico y si tiene cuenta podrá acceder a ellos
            </p>

            <form @submit.prevent="handleSubmit" class="space-y-2">
                <div class="grid gap-2">
                    <AppLabel for="email">Email</AppLabel>
                    <AppInput
                        id="email"
                        class="mt-1 block w-full"
                        v-model="addUserForm.email"
                        placeholder="test@test.com"
                        required
                    />
                    <AppInputError
                        class="mt-2"
                        :message="addUserForm.errors.email"
                    />
                </div>

                <div class="text-right">
                    <AppButton type="submit">Agregar</AppButton>
                </div>
            </form>

            <hr />

            <section>
                <h2 class="text-lg font-semibold">Compartiendo</h2>

                <ul>
                    <li v-for="access in sharedWith" :key="access.id">
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2 w-full">
                            <span>
                                {{ access.user?.name }} (<span
                                    class="text-gray-500 break-all"
                                    >{{ access.userEmail }}</span
                                >)
                                <span
                                    class="text-xs text-amber-900"
                                    v-if="!access.approvedAt"
                                    >Pendiente</span
                                >
                            </span>

                            <ConfirmDialog
                                :title="`¿Dejar de compartir con ${access.user?.name ?? access.userEmail}?`"
                                description="Ya no podrá ver ni editar tus productos."
                                confirmLabel="Dejar de compartir"
                                @confirm="removeAccess(access)"
                            >
                                <button type="button" class="py-1 text-destructive">
                                    Eliminar
                                </button>
                            </ConfirmDialog>
                        </div>
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold">Acceso</h2>

                <ul>
                    <li v-for="access in sharedBy" :key="access.id">
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2 w-full">
                            <Link
                                :href="route('products-share.show', access.id)"
                            >
                                {{ access.accessible?.name }} (<span
                                    class="text-gray-500 break-all"
                                    >{{ access.accessible?.email }}</span
                                >)
                            </Link>

                            <ConfirmDialog
                                :title="`¿Quitar tu acceso a los productos de ${access.accessible?.name}?`"
                                description="Para recuperarlo tendrán que volver a compartirlos contigo."
                                confirmLabel="Quitar acceso"
                                @confirm="removeAccess(access)"
                            >
                                <button type="button" class="py-1 text-destructive">
                                    Eliminar
                                </button>
                            </ConfirmDialog>
                        </div>
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold">Solicitudes pendientes</h2>

                <ul>
                    <li v-for="access in requests" :key="access.id">
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2 w-full">
                            <span>
                                {{ access.accessible?.name }} (<span
                                    class="text-gray-500 break-all"
                                    >{{ access.accessible?.email }}</span
                                >)
                            </span>

                            <div class="inline-flex gap-4">
                                <Link
                                    :href="
                                        route(
                                            'products-share.update',
                                            access.id
                                        )
                                    "
                                    method="put"
                                    as="button"
                                    class="py-1"
                                    >Aceptar</Link
                                >
                                <ConfirmDialog
                                    :title="`¿Rechazar la solicitud de ${access.accessible?.name}?`"
                                    confirmLabel="Rechazar"
                                    @confirm="removeAccess(access)"
                                >
                                    <button type="button" class="py-1">
                                        Rechazar
                                    </button>
                                </ConfirmDialog>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>
        </section>
    </AppLayout>
</template>

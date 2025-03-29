<script setup lang="ts">
import { Head, useForm, usePage } from "@inertiajs/vue3"

import DeleteUser from "@/components/DeleteUser.vue"
import HeadingSmall from "@/components/HeadingSmall.vue"
import InputError from "@/components/InputError.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import AppLayout from "@/layouts/AppLayout.vue"
import SettingsLayout from "@/layouts/settings/Layout.vue"
import { type BreadcrumbItem, type SharedData } from "@/types"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Configuración de perfil",
        href: "/settings/profile",
    },
]

const page = usePage<SharedData>()
const user = page.props.auth.user

const form = useForm({
    name: user.name,
})

const submit = () => {
    form.patch(route("profile.update"), {
        preserveScroll: true,
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Configuración de perfil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Información de perfil"
                    description="Actualiza tu nombre"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            placeholder="Nombre"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Guardar</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Guardado.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

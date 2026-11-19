<script setup lang="ts">
import { ref } from "vue"
import { useForm } from "@inertiajs/vue3"

import type { User } from "@/types"
import type { Store } from "@/types/Store"
import AppButton from "@/components/ui/button/Button.vue"
import AppDialog from "@/components/ui/dialog/Dialog.vue"
import DialogClose from "@/components/ui/dialog/DialogClose.vue"
import DialogContent from "@/components/ui/dialog/DialogContent.vue"
import DialogFooter from "@/components/ui/dialog/DialogFooter.vue"
import DialogHeader from "@/components/ui/dialog/DialogHeader.vue"
import DialogTitle from "@/components/ui/dialog/DialogTitle.vue"
import DialogTrigger from "@/components/ui/dialog/DialogTrigger.vue"
import AppInput from "@/components/ui/input/Input.vue"
import AppLabel from "@/components/ui/label/Label.vue"

/**
 * Creates a store for `owner`, or renames `store` when given. The trigger
 * is the default slot; `saved` is emitted once the server accepted it.
 */
const props = defineProps<{
    owner: User
    store?: Store
}>()

const emit = defineEmits<{ saved: [] }>()

const open = ref(false)
const form = useForm({ name: props.store?.name ?? "" })

function submit() {
    const options = {
        preserveScroll: true,
        only: ["stores"],
        onSuccess: () => {
            open.value = false
            emit("saved")
        },
    }

    if (props.store) {
        form.put(route("stores.update", { store: props.store.id }), options)
    } else {
        form.post(route("users.stores.store", { owner: props.owner.id }), {
            ...options,
            onSuccess: () => {
                form.reset()
                options.onSuccess()
            },
        })
    }
}

function onOpenChange(value: boolean) {
    open.value = value

    if (value) {
        form.clearErrors()
        form.name = props.store?.name ?? ""
    }
}
</script>

<template>
    <AppDialog :open="open" @update:open="onOpenChange">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{ store ? "Renombrar tienda" : "Nueva tienda" }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-2">
                    <AppLabel for="store-name">Nombre</AppLabel>
                    <AppInput
                        id="store-name"
                        v-model="form.name"
                        placeholder="p. ej. 3B, Aurrera"
                        autocomplete="off"
                        required
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <AppButton type="button" variant="secondary">
                            Cancelar
                        </AppButton>
                    </DialogClose>

                    <AppButton type="submit" :disabled="form.processing">
                        Guardar
                    </AppButton>
                </DialogFooter>
            </form>
        </DialogContent>
    </AppDialog>
</template>

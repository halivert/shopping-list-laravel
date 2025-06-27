<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3"

import type { ShoppingDay } from "@/types/ShoppingDay"

import AppButton from "../ui/button/Button.vue"
import AppDialog from "../ui/dialog/Dialog.vue"
import DialogClose from "../ui/dialog/DialogClose.vue"
import DialogContent from "../ui/dialog/DialogContent.vue"
import DialogDescription from "../ui/dialog/DialogDescription.vue"
import DialogFooter from "../ui/dialog/DialogFooter.vue"
import DialogHeader from "../ui/dialog/DialogHeader.vue"
import DialogTitle from "../ui/dialog/DialogTitle.vue"
import DialogTrigger from "../ui/dialog/DialogTrigger.vue"

const props = defineProps<{
    shoppingDay: ShoppingDay
}>()

const form = useForm({})

function deleteShoppingDay(e: Event) {
    e.preventDefault()

    form.delete(
        route("shopping-days.destroy", { shoppingDay: props.shoppingDay }),
        {
            async: true,
            onFinish: () => form.reset(),
        }
    )
}

function closeModal() {
    form.clearErrors()
    form.reset()
}
</script>

<template>
    <AppDialog>
        <DialogTrigger as-child>
            <AppButton class="basis-1/2" variant="destructive">
                Borrar
            </AppButton>
        </DialogTrigger>
        <DialogContent>
            <form class="space-y-6" @submit.prevent="deleteShoppingDay">
                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        ¿Seguro que deseas borrar este día de compras?
                    </DialogTitle>
                    <DialogDescription>
                        Una vez que este día sea borrado, todos los recursos y
                        datos también serán permanentemente borrados.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <AppButton variant="secondary" @click="closeModal">
                            Cancelar
                        </AppButton>
                    </DialogClose>

                    <AppButton
                        variant="destructive"
                        :disabled="form.processing"
                    >
                        <button type="submit">Borrar día de compras</button>
                    </AppButton>
                </DialogFooter>
            </form>
        </DialogContent>
    </AppDialog>
</template>

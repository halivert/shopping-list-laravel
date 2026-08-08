<script setup lang="ts">
import { useForm } from "@inertiajs/vue3"

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

function preservePendingItems(e: Event) {
    e.preventDefault()

    form.post(
        route("shopping-days.preserve-pending", { shoppingDay: props.shoppingDay }),
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
            <AppButton class="flex-1" variant="secondary">
                Guardar pendientes
            </AppButton>
        </DialogTrigger>
        <DialogContent>
            <form class="space-y-6" @submit.prevent="preservePendingItems">
                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        ¿Guardar los productos no comprados?
                    </DialogTitle>
                    <DialogDescription>
                        Los productos sin precio volverán a tu lista para el
                        próximo día de compras.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <AppButton
                            type="button"
                            variant="secondary"
                            @click="closeModal"
                        >
                            Cancelar
                        </AppButton>
                    </DialogClose>

                    <AppButton
                        type="submit"
                        variant="default"
                        :disabled="form.processing"
                    >
                        Guardar pendientes
                    </AppButton>
                </DialogFooter>
            </form>
        </DialogContent>
    </AppDialog>
</template>

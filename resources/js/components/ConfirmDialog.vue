<script setup lang="ts">
import { ref } from "vue"

import AppButton from "@/components/ui/button/Button.vue"
import AppDialog from "@/components/ui/dialog/Dialog.vue"
import DialogClose from "@/components/ui/dialog/DialogClose.vue"
import DialogContent from "@/components/ui/dialog/DialogContent.vue"
import DialogDescription from "@/components/ui/dialog/DialogDescription.vue"
import DialogFooter from "@/components/ui/dialog/DialogFooter.vue"
import DialogHeader from "@/components/ui/dialog/DialogHeader.vue"
import DialogTitle from "@/components/ui/dialog/DialogTitle.vue"
import DialogTrigger from "@/components/ui/dialog/DialogTrigger.vue"

/**
 * Asks for confirmation before a destructive action. The trigger is the
 * default slot; `confirm` is emitted only when the user accepts.
 */
withDefaults(
    defineProps<{
        title: string
        description?: string
        confirmLabel?: string
        processing?: boolean
    }>(),
    {
        confirmLabel: "Eliminar",
        processing: false,
    }
)

const emit = defineEmits<{ confirm: [] }>()

const open = ref(false)

function handleConfirm() {
    emit("confirm")
    open.value = false
}
</script>

<template>
    <AppDialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <AppButton type="button" variant="secondary">
                        Cancelar
                    </AppButton>
                </DialogClose>

                <AppButton
                    type="button"
                    variant="destructive"
                    :disabled="processing"
                    @click="handleConfirm"
                >
                    {{ confirmLabel }}
                </AppButton>
            </DialogFooter>
        </DialogContent>
    </AppDialog>
</template>

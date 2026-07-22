<script setup lang="ts">
import { computed, ref } from "vue"
import { Calculator } from "lucide-vue-next"

import AppButton from "@/components/ui/button/Button.vue"
import AppInput from "@/components/ui/input/Input.vue"
import AppLabel from "@/components/ui/label/Label.vue"
import AppDialog from "@/components/ui/dialog/Dialog.vue"
import DialogClose from "@/components/ui/dialog/DialogClose.vue"
import DialogContent from "@/components/ui/dialog/DialogContent.vue"
import DialogDescription from "@/components/ui/dialog/DialogDescription.vue"
import DialogFooter from "@/components/ui/dialog/DialogFooter.vue"
import DialogHeader from "@/components/ui/dialog/DialogHeader.vue"
import DialogTitle from "@/components/ui/dialog/DialogTitle.vue"
import DialogTrigger from "@/components/ui/dialog/DialogTrigger.vue"
import { formatCurrency } from "@/composables/formatHelpers"

const props = defineProps<{
    unit?: string | null
}>()

const emit = defineEmits<{
    apply: [{ unitPrice: number; quantity: number }]
}>()

// ── Unit conversion ──────────────────────────────────────────────────────────
// factor[productUnit][packageUnit] = how many productUnit one packageUnit is
// worth, e.g. UNIT_PAIRS.L.ml = 0.001 (1 ml = 0.001 L).

const UNIT_PAIRS: Record<string, Record<string, number>> = {
    L: { L: 1, ml: 0.001 },
    ml: { ml: 1, L: 1000 },
    kg: { kg: 1, g: 0.001 },
    g: { g: 1, kg: 1000 },
}

const sizeOptions = computed(() =>
    props.unit ? Object.keys(UNIT_PAIRS[props.unit] ?? {}) : []
)

// ── Form state ────────────────────────────────────────────────────────────────

const open = ref(false)
const pricePaid = ref<number>()
const packageSize = ref<number>()
const packageUnit = ref(props.unit ?? "")

function resetForm() {
    pricePaid.value = undefined
    packageSize.value = undefined
    packageUnit.value = sizeOptions.value[0] ?? props.unit ?? ""
}

function onOpenChange(next: boolean) {
    open.value = next
    if (next) resetForm()
}

// ── Result ────────────────────────────────────────────────────────────────────

const result = computed(() => {
    if (!pricePaid.value || !packageSize.value) return null

    const factor = props.unit
        ? (UNIT_PAIRS[props.unit]?.[packageUnit.value] ?? 1)
        : 1

    const quantity = packageSize.value * factor
    if (!quantity || Number.isNaN(quantity)) return null

    const unitPrice = pricePaid.value / quantity
    if (!Number.isFinite(unitPrice)) return null

    return {
        unitPrice: Math.round(unitPrice * 10000) / 10000,
        quantity: Math.round(quantity * 10000) / 10000,
    }
})

function handleApply() {
    if (!result.value) return
    emit("apply", result.value)
    open.value = false
}
</script>

<template>
    <AppDialog :open="open" @update:open="onOpenChange">
        <DialogTrigger as-child>
            <AppButton
                type="button"
                variant="ghost"
                size="sm"
                class="aspect-square p-0 shrink-0"
                title="Calcular precio por unidad"
            >
                <Calculator :size="16" />
            </AppButton>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Calcular precio por unidad</DialogTitle>
                <DialogDescription>
                    Si compraste una presentación distinta, ingresa lo que
                    pagaste y el contenido del paquete para calcular el precio
                    por {{ unit || "unidad" }}.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-1">
                    <AppLabel for="price-paid">Precio pagado</AppLabel>
                    <AppInput
                        id="price-paid"
                        v-model.number="pricePaid"
                        type="number"
                        min="0"
                        step="0.01"
                        autofocus
                    />
                </div>

                <div class="space-y-1">
                    <AppLabel for="package-size">Contenido del paquete</AppLabel>
                    <div class="flex gap-2">
                        <AppInput
                            id="package-size"
                            v-model.number="packageSize"
                            class="flex-1"
                            type="number"
                            min="0"
                            step="0.001"
                        />
                        <div
                            v-if="sizeOptions.length > 0"
                            class="flex rounded-md border overflow-hidden shrink-0"
                        >
                            <button
                                v-for="option in sizeOptions"
                                :key="option"
                                type="button"
                                class="px-3 text-sm"
                                :class="
                                    packageUnit === option
                                        ? 'bg-primary text-background'
                                        : 'bg-background'
                                "
                                @click="packageUnit = option"
                            >
                                {{ option }}
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="result" class="text-sm text-muted-foreground">
                    ≈ {{ formatCurrency(result.unitPrice) }}
                    <template v-if="unit"> / {{ unit }}</template>
                </p>
            </div>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <AppButton type="button" variant="secondary">
                        Cancelar
                    </AppButton>
                </DialogClose>

                <AppButton
                    type="button"
                    :disabled="!result"
                    @click="handleApply"
                >
                    Aplicar
                </AppButton>
            </DialogFooter>
        </DialogContent>
    </AppDialog>
</template>

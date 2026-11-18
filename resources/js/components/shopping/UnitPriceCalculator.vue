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
import { PRODUCT_UNITS } from "@/types/Product"

const props = defineProps<{
    unit?: string | null
}>()

const emit = defineEmits<{
    apply: [{ unitPrice: number; quantity: number }]
}>()

// ── Unit conversion ──────────────────────────────────────────────────────────
// Products are tracked per kg or L, but packages are usually labeled in g or
// ml (a 78 g tuna pouch). PACKAGE_UNITS[productUnit][packageUnit] is how many
// productUnit one packageUnit is worth. Keyed on PRODUCT_UNITS so a new
// product unit without conversions is a type error. The first entry is the
// default, since package labels use the small unit.
type ProductUnit = (typeof PRODUCT_UNITS)[number]

const PACKAGE_UNITS: Record<ProductUnit, Record<string, number>> = {
    kg: { g: 0.001, kg: 1 },
    L: { ml: 0.001, L: 1 },
}

function isProductUnit(unit: string | null | undefined): unit is ProductUnit {
    return !!unit && (PRODUCT_UNITS as readonly string[]).includes(unit)
}

const productUnit = computed(() =>
    isProductUnit(props.unit) ? props.unit : null
)

const packageUnitOptions = computed(() =>
    productUnit.value ? Object.keys(PACKAGE_UNITS[productUnit.value]) : []
)

// ── Form state ────────────────────────────────────────────────────────────────

type PriceMode = "each" | "total"

const open = ref(false)
const packages = ref<number>(1)
const packageSize = ref<number>()
const packageUnit = ref("")
const price = ref<number>()
const priceMode = ref<PriceMode>("each")

function resetForm() {
    packages.value = 1
    packageSize.value = undefined
    packageUnit.value = packageUnitOptions.value[0] ?? ""
    price.value = undefined
    priceMode.value = "each"
}

function onOpenChange(next: boolean) {
    open.value = next
    if (next) resetForm()
}

// ── Result ────────────────────────────────────────────────────────────────────

// Match the database precision: decimal(9, 4) for quantity and unit price.
function round4(n: number): number {
    return Math.round(n * 10_000) / 10_000
}

const result = computed(() => {
    if (!packages.value || packages.value <= 0 || !price.value) return null

    // Without a unit the product is counted in pieces: 3 cans → quantity 3.
    let quantity = packages.value

    if (productUnit.value) {
        if (!packageSize.value || packageSize.value <= 0) return null

        const factor =
            PACKAGE_UNITS[productUnit.value][packageUnit.value] ?? 1
        quantity = packages.value * packageSize.value * factor
    }

    const total =
        priceMode.value === "each" ? price.value * packages.value : price.value
    const unitPrice = total / quantity

    if (!Number.isFinite(unitPrice) || unitPrice < 0) return null

    return {
        unitPrice: round4(unitPrice),
        quantity: round4(quantity),
        total,
    }
})

function handleApply() {
    if (!result.value) return
    emit("apply", {
        unitPrice: result.value.unitPrice,
        quantity: result.value.quantity,
    })
    open.value = false
}
</script>

<template>
    <AppDialog :open="open" @update:open="onOpenChange">
        <DialogTrigger as-child>
            <AppButton
                type="button"
                variant="ghost"
                size="icon"
                class="size-10 shrink-0"
                title="Calcular precio por unidad"
            >
                <Calculator :size="16" />
            </AppButton>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Calcular precio por {{ unit || "pieza" }}</DialogTitle>
                <DialogDescription>
                    Indica cuántos paquetes compraste<template v-if="unit">,
                        cuánto trae cada uno</template
                    >
                    y el precio; se calculan la cantidad y el precio por
                    {{ unit || "pieza" }}.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-1">
                    <AppLabel for="calc-packages">Paquetes</AppLabel>
                    <AppInput
                        id="calc-packages"
                        v-model.number="packages"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        step="1"
                    />
                </div>

                <div v-if="productUnit" class="space-y-1">
                    <AppLabel for="calc-package-size">
                        Contenido de cada paquete
                    </AppLabel>
                    <div class="flex gap-2">
                        <AppInput
                            id="calc-package-size"
                            v-model.number="packageSize"
                            class="flex-1"
                            type="number"
                            inputmode="decimal"
                            min="0"
                            step="any"
                            autofocus
                        />
                        <div
                            class="flex rounded-md border overflow-hidden shrink-0"
                        >
                            <button
                                v-for="option in packageUnitOptions"
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

                <div class="space-y-1">
                    <AppLabel for="calc-price">Precio</AppLabel>
                    <div class="flex gap-2">
                        <AppInput
                            id="calc-price"
                            v-model.number="price"
                            class="flex-1"
                            type="number"
                            inputmode="decimal"
                            min="0"
                            step="any"
                        />
                        <div
                            class="flex rounded-md border overflow-hidden shrink-0"
                        >
                            <button
                                v-for="mode in (['each', 'total'] as const)"
                                :key="mode"
                                type="button"
                                class="px-3 text-sm"
                                :class="
                                    priceMode === mode
                                        ? 'bg-primary text-background'
                                        : 'bg-background'
                                "
                                @click="priceMode = mode"
                            >
                                {{ mode === "each" ? "c/u" : "total" }}
                            </button>
                        </div>
                    </div>
                </div>

                <dl
                    v-if="result"
                    class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 rounded-md bg-secondary p-3 text-sm"
                >
                    <dt class="text-muted-foreground">Cantidad</dt>
                    <dd class="text-right tabular-nums">
                        {{ result.quantity }} {{ unit || "pzas" }}
                    </dd>
                    <dt class="text-muted-foreground">
                        Precio por {{ unit || "pieza" }}
                    </dt>
                    <dd class="text-right tabular-nums">
                        {{ formatCurrency(result.unitPrice) }}
                    </dd>
                    <dt class="font-medium">Total</dt>
                    <dd class="text-right font-medium tabular-nums">
                        {{ formatCurrency(result.total) }}
                    </dd>
                </dl>
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

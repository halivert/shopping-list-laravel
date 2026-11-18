<script setup lang="ts">
import { computed, ref } from "vue"
import { useDebounceFn } from "@vueuse/core"

import AppInput from "../ui/input/Input.vue"
import UnitPriceCalculator from "./UnitPriceCalculator.vue"
import { formatCurrency } from "@/composables/formatHelpers"

const unitPrice = defineModel<number>("unitPrice", { default: 0 })
const quantity = defineModel<number>("quantity", { default: 0 })
const checked = defineModel<boolean>("checked")

const props = defineProps<{
    lastPrice?: number
    unit?: string | null
}>()

const updateQuantity = (newQuantity: number) => {
    if (newQuantity <= 0) return
    quantity.value = newQuantity
}

const total = computed(() => {
    const total = quantity.value * unitPrice.value

    return Number.isNaN(total) ? 0 : total
})

const lastPriceFormatted = computed(() =>
    props.lastPrice ? formatCurrency(props.lastPrice) : ""
)

const editCount = ref(false)

const context = (fn?: () => void) => {
    if ("vibrate" in navigator) {
        navigator.vibrate([100, 100, 150])
    }

    fn?.()
}

function handleSubmit(e: Event) {
    const form = e.target as HTMLFormElement
    const quantityInput = form.elements.namedItem("quantity")

    if (!(quantityInput instanceof HTMLInputElement)) {
        return
    }

    handleUpdateQuantity(quantityInput)
}

function handleUpdateQuantity(quantityInput: HTMLInputElement) {
    const quantity = quantityInput.valueAsNumber

    if (Number.isNaN(quantity)) {
        return
    }

    updateQuantity(quantity)
    editCount.value = false
}

const handleMaybeChecked = useDebounceFn(function handleMaybeChecked() {
    checked.value = Boolean(unitPrice.value)
}, 1000)

function handleUnitPriceInput(e: Event) {
    const value = (e.target as HTMLInputElement).valueAsNumber

    unitPrice.value = Number.isNaN(value) ? 0 : Math.max(0, value)
}

function handleApplyCalculated({
    unitPrice: calculatedPrice,
    quantity: calculatedQuantity,
}: {
    unitPrice: number
    quantity: number
}) {
    unitPrice.value = calculatedPrice
    quantity.value = calculatedQuantity
    handleMaybeChecked()
}
</script>

<template>
    <div
        :class="[
            { checked },
            'shopping-list-item flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-5',
        ]"
    >
        <div
            class="sm:basis-2/6 sm:shrink-0 flex items-baseline justify-between gap-2"
        >
            <span class="slot break-words min-w-0">
                <slot />
            </span>
            <!-- Line total lives here, not inside the price input, so it never
                 overlaps the typed price or the unit suffix -->
            <span
                v-if="total && quantity !== 1"
                class="shrink-0 text-sm tabular-nums text-muted-foreground"
                >{{ formatCurrency(total) }}</span
            >
        </div>
        <div class="flex-1 flex gap-2 items-center px-0.5 sm:justify-end">
            <div
                v-if="!editCount"
                class="flex items-center justify-between shrink-0 w-32"
            >
                <button
                    type="button"
                    class="size-9 rounded bg-secondary disabled:opacity-30 disabled:cursor-not-allowed p-1.5"
                    @click="updateQuantity(Math.ceil(quantity - 1))"
                    @contextmenu.prevent="context(() => updateQuantity(1))"
                    :disabled="quantity <= 1"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="mx-auto"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 12H6"
                        />
                    </svg>
                </button>
                <span
                    class="px-1 text-center tabular-nums select-none"
                    @contextmenu.prevent="context(() => (editCount = true))"
                >
                    {{ quantity }}<span
                        v-if="unit"
                        class="text-xs text-muted-foreground"
                        >{{ " " + unit }}</span
                    >
                </span>
                <button
                    type="button"
                    class="size-9 rounded bg-primary text-background p-1.5"
                    @click="updateQuantity(Math.floor(quantity + 1))"
                    @contextmenu.prevent
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="mx-auto"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6v12m6-6H6"
                        />
                    </svg>
                </button>
            </div>
            <form v-else @submit.prevent="handleSubmit" class="shrink-0 w-32">
                <AppInput
                    class="bg-secondary text-primary px-1 py-0.5 text-lg rounded w-full invalid:text-red-600 invalid:saturate-200"
                    name="quantity"
                    type="number"
                    inputmode="decimal"
                    step="0.001"
                    :modelValue="quantity"
                    @blur="
                        (e: Event) =>
                            handleUpdateQuantity(e.target as HTMLInputElement)
                    "
                />
            </form>
            <div class="relative flex-1 min-w-0 sm:max-w-48">
                <AppInput
                    :class="[
                        'bg-secondary invalid:text-red-600 invalid:saturate-200',
                        unit ? 'pr-10' : '',
                    ]"
                    type="number"
                    inputmode="decimal"
                    :placeholder="lastPriceFormatted"
                    :modelValue="unitPrice || ''"
                    @input="handleUnitPriceInput"
                    @blur="handleMaybeChecked"
                    min="0"
                    step="0.001"
                />

                <span
                    v-if="unit"
                    class="absolute top-1/2 -translate-y-1/2 right-2 text-xs text-muted-foreground pointer-events-none"
                    >/{{ unit }}</span
                >
            </div>

            <UnitPriceCalculator :unit="unit" @apply="handleApplyCalculated" />
        </div>
    </div>
</template>

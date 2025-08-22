<script setup lang="ts">
import { computed, ref } from "vue"
import { useDebounceFn } from "@vueuse/core"

import AppInput from "../ui/input/Input.vue"
import { formatCurrency } from "@/composables/formatHelpers"

const unitPrice = defineModel<number>("unitPrice", { default: 0 })
const quantity = defineModel<number>("quantity", { default: 0 })
const checked = defineModel<boolean>("checked")

const props = defineProps<{
    lastPrice?: number
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
</script>

<template>
    <div
        :class="[
            { checked },
            'shopping-list-item flex gap-5 flex-nowrap items-start',
        ]"
    >
        <span class="basis-2/6 shrink-0 slot">
            <slot />
        </span>
        <div class="flex-1 flex gap-3 px-0.5 flex-wrap-reverse justify-end">
            <div
                v-if="!editCount"
                class="flex items-center justify-between w-1/2 sm:w-1/3"
            >
                <button
                    class="h-6 w-6 rounded bg-secondary disabled:opacity-30 disabled:cursor-not-allowed p-0.5"
                    @click="updateQuantity(Math.ceil(quantity - 1))"
                    @contextmenu.prevent="context(() => updateQuantity(1))"
                    :disabled="quantity === 1"
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
                    class="px-3"
                    @contextmenu.prevent="context(() => (editCount = true))"
                >
                    {{ quantity }}
                </span>
                <button
                    class="h-6 w-6 rounded bg-primary text-background p-0.5"
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
            <form v-else @submit.prevent="handleSubmit" class="w-1/2 sm:w-1/3">
                <AppInput
                    class="bg-secondary text-primary px-1 py-0.5 text-lg rounded w-full invalid:text-red-600 invalid:saturate-200"
                    name="quantity"
                    type="number"
                    step="0.001"
                    :modelValue="quantity"
                    @blur="
                        (e: Event) =>
                            handleUpdateQuantity(e.target as HTMLInputElement)
                    "
                />
            </form>
            <div class="relative">
                <AppInput
                    class="bg-secondary invalid:text-red-600 invalid:saturate-200"
                    type="number"
                    :placeholder="lastPriceFormatted"
                    :modelValue="unitPrice || ''"
                    @input="unitPrice = $event.target.valueAsNumber"
                    @blur="handleMaybeChecked"
                    min="0"
                    step="0.001"
                />

                <span
                    v-if="total && quantity !== 1"
                    class="absolute top-1/2 -translate-y-1/2 right-10 bg-secondary px-1 rounded"
                    >{{ formatCurrency(total) }}</span
                >
            </div>
        </div>
    </div>
</template>

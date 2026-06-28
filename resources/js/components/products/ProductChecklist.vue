<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3"
import { useDebounceFn } from "@vueuse/core"

import type { Product } from "@/types/Product"
import type { User } from "@/types"
import AppInput from "@/components/ui/input/Input.vue"
import AppButton from "@/components/ui/button/Button.vue"
import AppCheckbox from "@/components/ui/checkbox/Checkbox.vue"

const props = defineProps<{
    products: Product[]
    user?: User
}>()

const emit = defineEmits<{
    changeQuantity: [productId: string, next: number]
}>()

// ── Add new product ───────────────────────────────────────────────────────────

const productForm = useForm({
    name: "",
    products: [] as string[],
})

function handleMarkdownProducts() {
    productForm.products = productForm.name
        .split(/- \[[\s|x]\]/)
        .map((item) => item.trim())
        .filter(Boolean)
}

function handleNewProduct() {
    productForm
        .transform((data) => ({
            name: data.products.length === 1 ? data.name : undefined,
            products: data.products.length > 1 ? data.products : undefined,
            user_id: props.user ? props.user.id : undefined,
        }))
        .post(route("products.store"), {
            onSuccess: () => productForm.reset(),
            preserveScroll: true,
        })
}

// ── Checklist state ───────────────────────────────────────────────────────────

function patchProduct<T extends { is_required: boolean }>(
    product: Product,
    attrs: T
) {
    router.put<T>(route("products.update", product.id), attrs, {
        preserveScroll: true,
        preserveState: true,
    })
}

const debouncedPatchRequired = useDebounceFn(
    (product: Product, value: boolean) => {
        patchProduct(product, { is_required: value })
    },
    500
)

function handleRequiredChange(product: Product, checked: boolean) {
    product.isRequired = checked
    debouncedPatchRequired(product, checked)
}

function decrementQuantity(product: Product) {
    const next = product.requiredQuantity - 1
    if (next < 1) return
    if ("vibrate" in navigator) navigator.vibrate(50)
    emit("changeQuantity", product.id, next)
}

function incrementQuantity(product: Product) {
    if ("vibrate" in navigator) navigator.vibrate(50)
    emit("changeQuantity", product.id, product.requiredQuantity + 1)
}
</script>

<template>
    <section class="py-3">
        <ul class="space-y-2">
            <li
                v-for="product in products"
                :key="product.id"
                class="flex items-center gap-3 py-1"
            >
                <!-- Required checkbox -->
                <AppCheckbox
                    :id="`req-${product.id}`"
                    :checked="product.isRequired"
                    class="accent-primary size-4 shrink-0 cursor-pointer"
                    @update:checked="
                        (value) => handleRequiredChange(product, value)
                    "
                ></AppCheckbox>

                <!-- Product name — tapping toggles the checkbox via <label for> -->
                <label
                    :for="`req-${product.id}`"
                    class="flex-1 cursor-pointer select-none"
                    :class="{ 'font-medium': product.isRequired }"
                >
                    {{ product.name }}
                </label>

                <!-- Quantity stepper (only when required) -->
                <div
                    v-if="product.isRequired"
                    class="flex items-center gap-1 shrink-0"
                >
                    <button
                        type="button"
                        class="h-6 w-6 rounded bg-secondary disabled:opacity-30 disabled:cursor-not-allowed p-0.5"
                        :disabled="product.requiredQuantity <= 1"
                        @click="decrementQuantity(product)"
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

                    <span class="w-6 text-center text-sm tabular-nums">
                        {{ product.requiredQuantity }}
                    </span>

                    <button
                        type="button"
                        class="h-6 w-6 rounded bg-primary text-background p-0.5"
                        @click="incrementQuantity(product)"
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
            </li>

            <!-- Add new product form -->
            <li class="list-none pt-1">
                <form
                    class="inline-flex w-full h-10 gap-2"
                    @submit.prevent="handleNewProduct"
                >
                    <AppInput
                        class="rounded-e-none h-[unset]"
                        v-model="productForm.name"
                        @change="handleMarkdownProducts"
                        required
                    />

                    <AppButton
                        class="rounded-s-none aspect-square h-[unset] w-auto p-0"
                        :disabled="productForm.processing"
                    >
                        <div
                            v-if="productForm.processing"
                            class="border-2 border-transparent border-b-current border-l-current size-5 block aspect-square rounded-3xl animate-spin"
                        />

                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-5"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </AppButton>
                </form>
            </li>
        </ul>
    </section>
</template>

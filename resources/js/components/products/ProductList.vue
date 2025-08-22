<script setup lang="ts">
import { ref } from "vue"
import { useForm } from "@inertiajs/vue3"

import { Product } from "@/types/Product"
import AppInput from "@/components/ui/input/Input.vue"
import AppButton from "@/components/ui/button/Button.vue"
import { User } from "@/types"

const props = defineProps<{
    products: Product[]
    user?: User
}>()

const edit = ref<number | undefined>(undefined)

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

function handleItemChange(event: FocusEvent | KeyboardEvent) {
    edit.value = undefined
    if (productForm.processing) return

    if (!event.target) {
        return
    }

    const target = event.target as HTMLLIElement
    const index = parseInt(target.dataset.index ?? "", 10)
    const value = target.innerText.trim()
    target.innerText = value

    if (value === props.products[index].name) {
        return
    }

    const productId = props.products[index].id

    if (!value) {
        return productForm.delete(route("products.destroy", productId), {
            preserveScroll: true,
        })
    }

    productForm
        .transform(() => ({
            name: value,
        }))
        .put(route("products.update", productId), {
            onSuccess: () => productForm.reset(),
            preserveScroll: true,
        })
}
</script>

<template>
    <section class="py-3">
        <ul class="list-disc ml-5 space-y-3">
            <li
                v-for="(product, i) in products"
                :contenteditable="edit === i ? 'plaintext-only' : 'false'"
                :data-index="i"
                @click="edit = i"
                @keyup.enter="handleItemChange"
                @blur="handleItemChange"
                :key="product.id"
            >
                {{ product.name }}
            </li>
            <li class="list-none">
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
                        <!-- Loading spinner -->
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

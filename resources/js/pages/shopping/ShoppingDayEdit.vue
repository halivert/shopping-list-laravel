<script setup lang="ts">
import { computed } from "vue"
import { useDebounceFn } from "@vueuse/core"
import { Head, router, useForm } from "@inertiajs/vue3"
import type { Page, PageProps } from "@inertiajs/core"

import AppLayout from "@/layouts/AppLayout.vue"
import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import type { ShoppingDay } from "@/types/ShoppingDay"
import AppButton from "@/components/ui/button/Button.vue"
import NewProductInput from "@/components/shopping/NewProductInput.vue"
import { formatCurrency, formatDate } from "@/composables/formatHelpers"
import { useCreateNewProductToShoppingDay } from "@/composables/useCreateNewProductToShoppingDay"
import { useEditShoppingDay } from "@/composables/useEditShoppingDay"
import EditShoppingDayDateInput from "@/components/shopping/EditShoppingDayDateInput.vue"
import DeleteShoppingDay from "@/components/shopping/DeleteShoppingDay.vue"

interface Props extends PageProps {
    shoppingDay: ShoppingDay
    products: Product[]
}

const props = defineProps<Props>()

const computedProducts = computed(() => props.products)
const shoppingDay = computed(() => props.shoppingDay)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Inicio",
        href: "/",
    },
    {
        title: `Día de compras: ${formatDate(shoppingDay.value.date)}`,
        href: "",
    },
])

const updateProductsForm = useForm({
    products: props.shoppingDay.items?.map(({ product }) => product.id) ?? [],
})

const autoSaveItems = useDebounceFn(function autoSaveItems() {
    handleSaveShoppingDay({ async: true })
}, 6 * 1000)

const { form: productForm, handleSubmit: handleNewProduct } =
    useCreateNewProductToShoppingDay(
        computed(() => shoppingDay.value.id),
        {
            onSuccess: (response) => {
                const responseProps = (response as Page<Props>).props

                if (!("shoppingDay" in responseProps)) {
                    return
                }

                updateProductsForm.products =
                    responseProps.shoppingDay.items?.map(
                        ({ product }) => product.id
                    ) ?? []
            },
        }
    )

function handleSaveProducts() {
    handleSaveShoppingDay({ async: false })
}

function handleSaveShoppingDay({ async }: { async: boolean }) {
    updateProductsForm.patch(
        route("shopping-days.update", {
            shoppingDay: props.shoppingDay.id,
        }),
        {
            async,
            preserveScroll: true,
            onSuccess: () => {
                if (!async) {
                    return router.get(
                        route("shopping-days.show", {
                            shoppingDay: props.shoppingDay.id,
                        })
                    )
                }
            },
        }
    )
}

const productsSuggestions = computed(() =>
    computedProducts.value.filter(
        ({ id }) => !updateProductsForm.products.includes(id)
    )
)

const {
    isEditing: editDate,
    form: editDateForm,
    handleSubmit: handleSubmitDate,
} = useEditShoppingDay(
    shoppingDay,
    computed(() => ({ products: updateProductsForm.products }))
)

const estimatedTotal = computed(() =>
    updateProductsForm.products.reduce((total, id) => {
        const product = props.products.find((product) => product.id === id)
        if (!product || !product.lastPrice) return total

        const quantity = parseInt(product.name.split("-").at(1) ?? "")
        if (Number.isNaN(quantity)) return total + product.lastPrice

        return total + product.lastPrice * quantity
    }, 0)
)
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <header class="p-3 top-0 sticky bg-background z-10 space-y-1">
            <div class="w-full flex justify-between items-center">
                <form @submit.prevent="handleSubmitDate">
                    <EditShoppingDayDateInput
                        class="flex gap-2 items-center"
                        v-model="editDateForm.date"
                        v-model:isEditing="editDate"
                    >
                        {{ formatDate(shoppingDay.date, "full") }}
                    </EditShoppingDayDateInput>
                </form>

                <AppButton form="productsForm" type="submit">
                    ¡De compras!
                </AppButton>
            </div>

            <div>Total estimado: {{ formatCurrency(estimatedTotal) }}</div>
        </header>

        <form
            class="flex gap-2 my-3 flex-col mx-3"
            @submit.prevent="handleNewProduct"
        >
            <NewProductInput
                v-model="productForm.name"
                :loading="productForm.processing"
                :productsSuggestions="productsSuggestions"
            />
        </form>

        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <form
                class="columns-2"
                @submit.prevent="handleSaveProducts"
                id="productsForm"
            >
                <article v-for="product in computedProducts" :key="product.id">
                    <label
                        :class="[
                            'accent-primary dark:accent-secondary',
                            {
                                underline:
                                    product.lastPrice &&
                                    updateProductsForm.products.includes(
                                        product.id
                                    ),
                            },
                        ]"
                    >
                        <input
                            type="checkbox"
                            v-model="updateProductsForm.products"
                            @change="autoSaveItems"
                            :value="product.id"
                        />
                        {{ product.name }}
                    </label>
                </article>
            </form>

            <div class="flex justify-end">
                <DeleteShoppingDay :shoppingDay="shoppingDay" />
            </div>
        </div>
    </AppLayout>
</template>

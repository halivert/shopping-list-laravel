<script setup lang="ts">
import { computed, ref, watch } from "vue"
import { Head, router, useForm, usePage } from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import { type BreadcrumbItem } from "@/types"
import { ShoppingDay } from "@/types/ShoppingDay"
import SortableItemList from "@/components/SortableItemList.vue"
import { Product } from "@/types/Product"
import AppButton from "@/components/ui/button/Button.vue"
import NewProductInput from "@/components/shopping/NewProductInput.vue"

const page = usePage()

const props = defineProps<{
    shoppingDay: ShoppingDay
    otherProducts: Product[]
}>()

const products = ref(
    props.otherProducts.map((product) => ({ ...product, type: "product" }))
)

const items = ref(
    props.shoppingDay.items?.map(({ product, id }) => ({
        id,
        name: product.name,
        type: "item",
    })) ?? []
)

watch(
    () => props.shoppingDay.items,
    (newItems) => {
        if (!newItems) return

        items.value = newItems.map(({ product, id }) => ({
            id,
            name: product.name,
            type: "item",
        }))
    }
)

watch(
    () => props.otherProducts,
    (newItems) => {
        products.value = newItems.map((product) => ({
            ...product,
            type: "product",
        }))
    }
)

function formatDate(strDate: string): string {
    const date = new Date(strDate)

    return new Intl.DateTimeFormat(page.props.lang, {
        dateStyle: "medium",
    }).format(date)
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Dashboard",
        href: "/",
    },
    {
        title: `Día de compras: ${formatDate(props.shoppingDay.date)}`,
        href: "",
    },
])

const productForm = useForm({ name: "", productId: undefined })
function handleNewProduct() {
    productForm.post(
        route("shopping-days.products.create", {
            shoppingDay: props.shoppingDay.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => (productForm.name = ""),
        }
    )
}

function handleSaveShoppingDay() {
    const itemsWithIndex = items.value.map(({ id, type }, index) => ({
        id,
        index,
        type,
    }))

    const newProducts = itemsWithIndex.filter((item) => item.type === "product")
    const currentItems = itemsWithIndex.filter((item) => item.type === "item")

    router.patch(
        route("shopping-days.update", {
            shoppingDay: props.shoppingDay.id,
        }),
        {
            items: currentItems,
            products: newProducts,
        },
        { preserveScroll: true }
    )
}
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <header class="flex p-3 top-0 sticky bg-white z-10 justify-end">
            <AppButton @click="handleSaveShoppingDay">Siguiente</AppButton>
        </header>

        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <div class="flex flex-nowrap gap-1">
                <section class="flex-1 top-0 sticky">
                    <h2 class="text-lg font-semibold">
                        Para luego <small>({{ products.length }})</small>
                    </h2>
                    <SortableItemList
                        group="products"
                        class="line-through min-h-dvh"
                        :sortable="false"
                        v-model="products"
                    />
                </section>

                <section class="flex-1">
                    <h2 class="text-lg font-semibold">
                        Por comprar
                        <small>({{ items.length }})</small>
                    </h2>
                    <SortableItemList
                        class="min-h-dvh"
                        group="products"
                        v-model="items"
                    />

                    <hr class="mt-2" />

                    <form
                        class="flex w-full gap-2 mt-3 flex-col"
                        @submit.prevent="handleNewProduct"
                    >
                        <NewProductInput
                            v-model="productForm.name"
                            :loading="productForm.processing"
                            :productsSuggestions="products"
                        />
                    </form>
                </section>
            </div>

            <div class="flex gap-3 justify-end"></div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue"
import { useDebounceFn, watchIgnorable } from "@vueuse/core"
import { Head, router } from "@inertiajs/vue3"
import type { Page, PageProps } from "@inertiajs/core"

import AppLayout from "@/layouts/AppLayout.vue"
import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import type { ShoppingDay } from "@/types/ShoppingDay"
import SortableItemList, { type Item } from "@/components/SortableItemList.vue"
import AppButton from "@/components/ui/button/Button.vue"
import NewProductInput from "@/components/shopping/NewProductInput.vue"
import { formatDate } from "@/composables/formatHelpers"
import { useCreateNewProductToShoppingDay } from "@/composables/useCreateNewProductToShoppingDay"
import {
    productToSortableListItem,
    shoppingDayItemToSortableListItem,
} from "@/composables/mapHelpers"
import { useEditShoppingDay } from "@/composables/useEditShoppingDay"
import EditShoppingDayDateInput from "@/components/shopping/EditShoppingDayDateInput.vue"
import DeleteShoppingDay from "@/components/shopping/DeleteShoppingDay.vue"

interface Props extends PageProps {
    shoppingDay: ShoppingDay
    otherProducts: Product[]
}

const props = defineProps<Props>()

function mapItems(items: ShoppingDay["items"]) {
    return items?.map(shoppingDayItemToSortableListItem) ?? []
}

function mapProducts(products: Product[] | undefined) {
    return products?.map(productToSortableListItem) ?? []
}

const shoppingDay = computed(() => props.shoppingDay)

const products = ref(mapProducts(props.otherProducts))
const items = ref<(Item & { type: "product" | "item" })[]>(
    mapItems(shoppingDay.value.items)
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Dashboard",
        href: "/",
    },
    {
        title: `Día de compras: ${formatDate(shoppingDay.value.date)}`,
        href: "",
    },
])

const itemsWithIndex = computed(() =>
    items.value.map(({ id, type }, index) => ({
        id,
        index,
        type,
    }))
)

const groupedItemsWithIndex = computed(() => ({
    items: itemsWithIndex.value.filter(({ type }) => type === "item"),
    products: itemsWithIndex.value.filter(({ type }) => type === "product"),
}))

const autoSaveItems = useDebounceFn(function autoSaveItems() {
    handleSaveShoppingDay({ async: true })
}, 5 * 1000)

const { ignoreUpdates } = watchIgnorable(groupedItemsWithIndex, autoSaveItems)

const { form: productForm, handleSubmit: handleNewProduct } =
    useCreateNewProductToShoppingDay(
        computed(() => shoppingDay.value.id),
        {
            onSuccess: (response) => {
                const responseProps = (response as Page<Props>).props
                ignoreUpdates(() => {
                    products.value = mapProducts(responseProps.otherProducts)
                    items.value = mapItems(responseProps.shoppingDay.items)
                })
            },
        }
    )

function handleSaveShoppingDay({ async }: { async: boolean }) {
    router.patch(
        route("shopping-days.update", {
            shoppingDay: props.shoppingDay.id,
        }),
        groupedItemsWithIndex.value,
        {
            async,
            preserveScroll: true,
            onSuccess: (response) => {
                if (!async) {
                    return router.get(
                        route("shopping-days.show", {
                            shoppingDay: props.shoppingDay.id,
                        })
                    )
                }

                const responseProps = (response as Page<Props>).props
                ignoreUpdates(() => {
                    products.value = mapProducts(responseProps.otherProducts)
                    items.value = mapItems(responseProps.shoppingDay.items)
                })
            },
        }
    )
}

const {
    isEditing: editDate,
    form: editDateForm,
    handleSubmit: handleSubmitDate,
} = useEditShoppingDay(shoppingDay, groupedItemsWithIndex)
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <header
            class="flex p-3 top-0 sticky bg-background z-10 justify-between"
        >
            <form @submit.prevent="handleSubmitDate">
                <EditShoppingDayDateInput
                    class="flex gap-2 items-center"
                    v-model="editDateForm.date"
                    v-model:isEditing="editDate"
                >
                    {{ formatDate(shoppingDay.date, "full") }}
                </EditShoppingDayDateInput>
            </form>

            <AppButton @click="() => handleSaveShoppingDay({ async: false })">
                Llenar
            </AppButton>
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

            <div class="flex justify-end">
                <DeleteShoppingDay :shoppingDay="shoppingDay" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { Head, router, usePage } from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import { type BreadcrumbItem } from "@/types"
import { ShoppingDay } from "@/types/ShoppingDay"
import SortableItemList from "@/components/SortableItemList.vue"
import { Product } from "@/types/Product"

const page = usePage()

const props = defineProps<{
    shoppingDay: ShoppingDay
    otherProducts: Product[]
}>()

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

function handleDeleteItem(item: { id: string }) {
    router.delete(
        route("shopping-days.items.destroy", {
            shoppingDay: props.shoppingDay,
            shoppingDayItem: item.id,
        }),
        { preserveScroll: true }
    )
}

function handleUpdateItems(items: { id: string }[]) {
    router.put(
        route("shopping-days.update", {
            shoppingDay: props.shoppingDay,
        }),
        { items: items.map(({ id }) => id) },
        { preserveScroll: true }
    )
}

function handleCreateItem(
    item: { id: string; name: string },
    items: { id: string }[]
) {
    router.post(
        route("shopping-days.items.store", {
            shoppingDay: props.shoppingDay,
        }),
        {
            product_id: item.id,
            index: items.findIndex(({ id }) => id === item.id),
            items: items.filter(({ id }) => id !== item.id).map(({ id }) => id),
        },
        { preserveScroll: true }
    )
}
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <div class="flex flex-nowrap">
                <section class="flex-1 top-0 sticky">
                    <h2 class="text-lg font-semibold">Para luego</h2>
                    <SortableItemList
                        group="products"
                        class="line-through min-h-dvh"
                        :sortable="false"
                        :items="otherProducts"
                        @delete="handleCreateItem"
                    />
                </section>

                <section class="flex-1">
                    <h2 class="text-lg font-semibold">Por comprar</h2>
                    <SortableItemList
                        class="min-h-dvh"
                        group="products"
                        :items="
                            shoppingDay.items?.map((item) => ({
                                id: item.id,
                                name: item.product.name,
                            })) ?? []
                        "
                        @delete="handleDeleteItem"
                        @update="handleUpdateItems"
                    />
                </section>
            </div>

            <div class="flex gap-3 justify-end"></div>
        </div>
    </AppLayout>
</template>

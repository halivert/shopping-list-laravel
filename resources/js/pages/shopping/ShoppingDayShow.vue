<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, router, usePage } from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import { type BreadcrumbItem } from "@/types"
import { ShoppingDay } from "@/types/ShoppingDay"
import ShoppingList from "@/components/shopping/ShoppingList.vue"
import { getCurrency } from "@/composables/formatHelpers"
import { ShoppingDayItem } from "@/types/ShoppingDayItem"

const page = usePage()

const props = defineProps<{
    shoppingDay: ShoppingDay
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

const total = ref(0)
const hideChecked = ref(false)

function handleSave(items: ShoppingDayItem[]) {
    router.patch(
        route("shopping-days.update", {
            shoppingDay: props.shoppingDay.id,
        }),
        {
            touch: true,
            items: items.map((item) => ({
                id: item.id,
                unitPrice: item.unitPrice,
                quantity: item.quantity,
            })),
        },
        { preserveScroll: true }
    )
}
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <h2 class="text-lg font-semibold">Lista de compras</h2>
            <header
                class="text-xl sticky top-0 pt-2 pb-1 bg-white flex justify-between z-10 border-b-2 border-white-c -mx-2 px-2 items-center"
            >
                <span>
                    Total: <span class="">{{ getCurrency(total) }}</span>
                </span>
                <label class="text-xs inline-flex items-center gap-1">
                    Ocultar comprados
                    <input type="checkbox" v-model="hideChecked" />
                </label>
            </header>
            <ShoppingList
                :shoppingDay="shoppingDay"
                :hideChecked="hideChecked"
                @save="handleSave"
                @updateTotal="total = $event"
            />
        </div>
    </AppLayout>
</template>

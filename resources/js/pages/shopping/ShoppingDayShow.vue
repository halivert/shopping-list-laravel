<script setup lang="ts">
import { computed } from "vue"
import { Head, Link, usePage } from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import { type BreadcrumbItem } from "@/types"
import AppButton from "@/components/ui/button/Button.vue"
import ProductList from "@/components/products/ProductList.vue"
import { ShoppingDay } from "@/types/ShoppingDay"

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
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <section>
                <h2 class="text-lg font-semibold">Lista de compras</h2>
                <header
                    class="text-xl sticky top-0 pt-2 pb-1 bg-white-a flex justify-between z-10 border-b-2 border-white-c -mx-2 px-2"
                >
                    <span>Total: <span class="">$0.00</span></span
                    ><small
                        ><label>
                            Ocultar comprados <input type="checkbox" /></label
                    ></small>
                </header>
                <!-- <ProductList :products="" /> -->
            </section>

            <div class="flex gap-3 justify-end"></div>
        </div>
    </AppLayout>
</template>

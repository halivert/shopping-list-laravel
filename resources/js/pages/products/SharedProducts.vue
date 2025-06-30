<script setup lang="ts">
import { computed } from "vue"
import { Head, Link, router } from "@inertiajs/vue3"

import type { User, BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import ProductList from "@/components/products/ProductList.vue"

const props = defineProps<{
    products: Product[]
    user: User
}>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Inicio",
        href: "/",
    },
    {
        title: `Productos de ${props.user.name}`,
        href: "",
    },
])

function handleCreateShoppingDay() {
    const today = new Date(new Date().toJSON().split("T")[0] + "T00:00")

    if (!props.user) return

    router.post(route("users.shopping-days.store", { owner: props.user.id }), {
        date: today,
    })
}
</script>

<template>
    <Head :title="`Productos de ${props.user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <section>
                <h2 class="text-lg font-semibold">Lista de productos</h2>
                <ProductList :products="products" :user="user" />
            </section>

            <div class="flex gap-3 justify-end">
                <AppButton variant="default" @click="handleCreateShoppingDay">
                    ¡De compras!
                </AppButton>
            </div>
        </div>
    </AppLayout>
</template>

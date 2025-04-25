<script setup lang="ts">
import { computed } from "vue"
import { Head, Link } from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import { User, type BreadcrumbItem } from "@/types"
import AppButton from "@/components/ui/button/Button.vue"
import { Product } from "@/types/Product"
import ProductList from "@/components/products/ProductList.vue"

const props = defineProps<{
    products: Product[]
    user: User
}>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Dashboard",
        href: "/",
    },
    {
        title: `Productos de ${props.user.name}`,
        href: "",
    },
])
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
                <AppButton :as="Link" variant="default" href="/"
                    >¡De compras!</AppButton
                >
            </div>
        </div>
    </AppLayout>
</template>

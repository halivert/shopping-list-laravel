<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue"
import { type BreadcrumbItem } from "@/types"
import { Head } from "@inertiajs/vue3"
import PlaceholderPattern from "../components/PlaceholderPattern.vue"
import { Product } from "@/types/Product"
import ProductList from "../components/products/ProductList.vue"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Dashboard",
        href: "/",
    },
]

defineProps<{
    products: Product[]
}>()
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1">
            <h2 class="text-lg font-semibold">Lista de productos</h2>
            <ProductList :products="products" />
        </div>
    </AppLayout>
</template>

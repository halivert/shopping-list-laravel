<script setup lang="ts">
import { computed } from "vue"
import { Head } from "@inertiajs/vue3"

import type { User, BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import ProductChecklistView from "@/components/products/ProductChecklistView.vue"

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
</script>

<template>
    <Head :title="`Productos de ${props.user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <ProductChecklistView :products="products" :owner="user" />
    </AppLayout>
</template>

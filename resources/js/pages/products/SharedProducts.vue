<script setup lang="ts">
import { computed } from "vue"
import { Head } from "@inertiajs/vue3"

import type { User, BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import type { Store } from "@/types/Store"
import AppLayout from "@/layouts/AppLayout.vue"
import ProductChecklistView from "@/components/products/ProductChecklistView.vue"

const props = defineProps<{
    products: Product[]
    user: User
    stores: Store[]
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
        <ProductChecklistView
            :products="products"
            :owner="user"
            :stores="stores"
        />
    </AppLayout>
</template>

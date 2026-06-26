<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3"

import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import ProductChecklistView from "@/components/products/ProductChecklistView.vue"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Inicio",
        href: "/",
    },
]

defineProps<{
    products: Product[]
}>()
</script>

<template>
    <Head title="Inicio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <ProductChecklistView :products="products">
            <template #footer>
                <div class="px-3 py-2 flex justify-end">
                    <AppButton
                        :as="Link"
                        variant="link"
                        :href="route('products-share.create')"
                        >Compartir productos</AppButton
                    >
                </div>
            </template>
        </ProductChecklistView>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { Head, Link, usePage } from "@inertiajs/vue3"

import AppLayout from "@/layouts/AppLayout.vue"
import { type BreadcrumbItem } from "@/types"
import AppButton from "@/components/ui/button/Button.vue"
import { Product } from "@/types/Product"
import ProductList from "@/components/products/ProductList.vue"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Dashboard",
        href: "/",
    },
]

const page = usePage()

const user = computed(() => page.props.auth.user)

defineProps<{
    products: Product[]
}>()
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <section>
                <h2 class="text-lg font-semibold">Lista de productos</h2>
                <ProductList :products="products" />
            </section>

            <div class="flex gap-3 justify-end">
                <AppButton
                    :as="Link"
                    variant="default"
                    method="POST"
                    :href="
                        route('users.shopping-days.store', {
                            owner: user.id,
                        })
                    "
                    >¡De compras!</AppButton
                >

                <AppButton
                    :as="Link"
                    variant="link"
                    :href="route('products-share.create')"
                    >Compartir productos</AppButton
                >
            </div>
        </div>
    </AppLayout>
</template>

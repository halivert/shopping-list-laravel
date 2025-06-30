<script setup lang="ts">
import { computed } from "vue"
import { Head, Link, router, usePage } from "@inertiajs/vue3"

import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import ProductList from "@/components/products/ProductList.vue"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Inicio",
        href: "/",
    },
]

const page = usePage()

const user = computed(() => page.props.auth.user)

defineProps<{
    products: Product[]
}>()

function handleCreateShoppingDay() {
    if (!user.value) return

    router.post(route("users.shopping-days.store", { owner: user.value.id }), {
        date: new Date(),
    })
}
</script>

<template>
    <Head title="Inicio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <section>
                <h2 class="text-lg font-semibold">Lista de productos</h2>
                <ProductList :products="products" />
            </section>

            <div class="flex gap-3 justify-end">
                <AppButton
                    v-if="user"
                    variant="default"
                    @click="handleCreateShoppingDay"
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

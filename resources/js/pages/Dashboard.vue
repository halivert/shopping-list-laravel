<script setup lang="ts">
import { computed } from "vue"
import { Head, Link, router, usePage } from "@inertiajs/vue3"

import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import ProductChecklist from "@/components/products/ProductChecklist.vue"
import { formatCurrency } from "@/composables/formatHelpers"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Inicio",
        href: "/",
    },
]

const page = usePage()

const user = computed(() => page.props.auth.user)

const props = defineProps<{
    products: Product[]
}>()

const requiredProducts = computed(() =>
    props.products.filter((p) => p.isRequired)
)

const articleCount = computed(() => requiredProducts.value.length)

const totalUnits = computed(() =>
    requiredProducts.value.reduce((sum, p) => sum + p.requiredQuantity, 0)
)

const estimatedTotal = computed(() =>
    requiredProducts.value.reduce((sum, p) => {
        if (!p.lastPrice) return sum
        return sum + p.lastPrice * p.requiredQuantity
    }, 0)
)

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
        <div class="flex flex-col h-full">
            <!-- Summary header -->
            <header class="px-3 py-2 sticky top-0 bg-background z-10 border-b space-y-0.5">
                <div class="flex justify-between items-center gap-3">
                    <div>
                        <span class="font-semibold">{{ articleCount }}</span>
                        {{ articleCount === 1 ? "artículo" : "artículos" }}
                        <span v-if="totalUnits > 0 && totalUnits !== articleCount" class="text-muted-foreground text-sm">
                            ({{ totalUnits }} unidades)
                        </span>
                    </div>

                    <AppButton
                        v-if="user"
                        variant="default"
                        :disabled="articleCount === 0"
                        @click="handleCreateShoppingDay"
                    >
                        Empezar día de compras
                    </AppButton>
                </div>

                <div v-if="estimatedTotal > 0" class="text-sm text-muted-foreground">
                    Total estimado: {{ formatCurrency(estimatedTotal) }}
                </div>
            </header>

            <!-- Checklist -->
            <section class="px-3 flex-1 overflow-y-auto">
                <ProductChecklist :products="products" />
            </section>

            <!-- Footer actions -->
            <div class="px-3 py-2 flex justify-end">
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

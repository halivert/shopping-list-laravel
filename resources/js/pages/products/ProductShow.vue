<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, Link, router, useForm } from "@inertiajs/vue3"

import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import type { ProductPurchase, ProductStats } from "@/types/ProductShow"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import AppInput from "@/components/ui/input/Input.vue"
import PriceChart from "@/components/products/PriceChart.vue"
import { formatCurrency, formatDate } from "@/composables/formatHelpers"

const props = defineProps<{
    product: Product
    purchases: ProductPurchase[]
    stats: ProductStats
}>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: "Inicio", href: "/" },
    {
        title: "Productos",
        href: route("users.products.index", { owner: props.product.owner?.id }),
    },
    { title: props.product.name, href: "" },
])

// ── Rename ────────────────────────────────────────────────────────────────────

const isEditing = ref(false)

const renameForm = useForm({ name: props.product.name })

function startEdit() {
    renameForm.name = props.product.name
    isEditing.value = true
}

function submitRename() {
    renameForm.put(route("products.update", props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false
        },
    })
}

function cancelEdit() {
    renameForm.reset()
    isEditing.value = false
}

// ── Delete ────────────────────────────────────────────────────────────────────

function deleteProduct() {
    router.delete(route("products.destroy", props.product.id))
}

// ── Duration label ────────────────────────────────────────────────────────────

const durationLabel = computed(() => {
    if (props.stats.estimatedDuration === null) return null
    const days = Math.round(props.stats.estimatedDuration)
    return `Comprar ${props.product.requiredQuantity} rinde ~${days} días`
})

// ── Price chart points ────────────────────────────────────────────────────────

const chartPoints = computed(() =>
    props.purchases
        .filter((p) => p.unitPrice !== null)
        .map((p) => ({ date: p.date, price: p.unitPrice as number }))
)
</script>

<template>
    <Head :title="product.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 px-4 py-4 max-w-lg mx-auto">
            <!-- Header + rename -->
            <div class="flex items-center gap-2">
                <template v-if="isEditing">
                    <form
                        class="flex flex-1 items-center gap-2"
                        @submit.prevent="submitRename"
                    >
                        <AppInput
                            v-model="renameForm.name"
                            class="flex-1"
                            autofocus
                            required
                        />
                        <AppButton type="submit" :disabled="renameForm.processing">
                            Guardar
                        </AppButton>
                        <AppButton
                            type="button"
                            variant="ghost"
                            @click="cancelEdit"
                        >
                            Cancelar
                        </AppButton>
                    </form>
                </template>
                <template v-else>
                    <h1 class="text-xl font-semibold flex-1">{{ product.name }}</h1>
                    <AppButton
                        variant="ghost"
                        size="sm"
                        @click="startEdit"
                        title="Renombrar producto"
                    >
                        ✏️
                    </AppButton>
                </template>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">Veces comprado</p>
                    <p class="text-2xl font-semibold">{{ stats.timesBought }}</p>
                </div>

                <div v-if="stats.averagePrice !== null" class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">Precio promedio</p>
                    <p class="text-2xl font-semibold">
                        {{ formatCurrency(stats.averagePrice) }}
                    </p>
                </div>

                <div
                    v-if="stats.minPrice !== null && stats.maxPrice !== null"
                    class="rounded-lg border p-3"
                >
                    <p class="text-xs text-muted-foreground">Rango de precio</p>
                    <p class="font-semibold">
                        {{ formatCurrency(stats.minPrice) }} –
                        {{ formatCurrency(stats.maxPrice) }}
                    </p>
                </div>

                <div v-if="stats.totalSpent > 0" class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">Total gastado</p>
                    <p class="text-2xl font-semibold">
                        {{ formatCurrency(stats.totalSpent) }}
                    </p>
                </div>

                <div
                    v-if="durationLabel !== null"
                    class="rounded-lg border p-3 col-span-2"
                >
                    <p class="text-xs text-muted-foreground">Duración estimada</p>
                    <p class="font-semibold">{{ durationLabel }}</p>
                    <p
                        v-if="stats.daysPerUnit !== null"
                        class="text-xs text-muted-foreground"
                    >
                        ~{{ Math.round(stats.daysPerUnit) }} días por unidad
                    </p>
                </div>

                <div
                    v-if="stats.avgDaysBetween !== null"
                    class="rounded-lg border p-3"
                >
                    <p class="text-xs text-muted-foreground">Frecuencia de compra</p>
                    <p class="font-semibold">
                        Cada ~{{ Math.round(stats.avgDaysBetween) }} días
                    </p>
                </div>
            </div>

            <!-- Price graph -->
            <div v-if="chartPoints.length > 0">
                <h2 class="text-sm font-medium mb-2 text-muted-foreground">
                    Historial de precio
                </h2>
                <PriceChart :points="chartPoints" />
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Aún no hay precios registrados para este producto.
            </p>

            <!-- Purchase history -->
            <div v-if="purchases.length > 0">
                <h2 class="text-sm font-medium mb-2 text-muted-foreground">
                    Compras anteriores
                </h2>
                <ul class="space-y-1">
                    <li
                        v-for="purchase in purchases"
                        :key="purchase.shoppingDayId"
                        class="flex items-center justify-between text-sm py-1 border-b last:border-0"
                    >
                        <Link
                            :href="
                                route('shopping-days.show', {
                                    shoppingDay: purchase.shoppingDayId,
                                })
                            "
                            class="text-primary underline-offset-2 hover:underline"
                        >
                            {{ formatDate(purchase.date, "medium") }}
                        </Link>
                        <span class="text-muted-foreground">
                            <template v-if="purchase.quantity !== null">
                                {{ purchase.quantity }}×
                            </template>
                            <template v-if="purchase.unitPrice !== null">
                                {{ formatCurrency(purchase.unitPrice) }}
                            </template>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Delete -->
            <div class="pt-4 border-t">
                <AppButton
                    variant="ghost"
                    class="text-destructive hover:text-destructive"
                    @click="deleteProduct"
                >
                    Eliminar producto
                </AppButton>
            </div>
        </div>
    </AppLayout>
</template>

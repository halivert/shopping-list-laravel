<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue"
import { router, usePage } from "@inertiajs/vue3"
import { useEcho } from "@laravel/echo-vue"
import { useDebounceFn } from "@vueuse/core"

import type { Product } from "@/types/Product"
import type { User } from "@/types"
import AppButton from "@/components/ui/button/Button.vue"
import ProductChecklist from "@/components/products/ProductChecklist.vue"
import { formatCurrency } from "@/composables/formatHelpers"

const props = defineProps<{
    products: Product[]
    owner?: User
}>()

const page = usePage()
const auth = computed(() => page.props.auth as { user: User })

// The owner of the list: explicitly passed on the shared page, self on home
const ownerId = computed(() => props.owner?.id ?? auth.value.user.id)

// Local reactive copy — updated by realtime events without a page reload
const items = ref<Product[]>([...props.products])

// Resync if Inertia reloads push fresh props (e.g. after adding a product)
watch(
    () => props.products,
    (next) => {
        items.value = [...next]
    }
)

// ── Summary ───────────────────────────────────────────────────────────────────

const requiredItems = computed(() => items.value.filter((p) => p.isRequired))

const articleCount = computed(() => requiredItems.value.length)

const totalUnits = computed(() =>
    requiredItems.value.reduce((sum, p) => sum + p.requiredQuantity, 0)
)

const estimatedTotal = computed(() =>
    requiredItems.value.reduce((sum, p) => {
        if (!p.lastPrice) return sum
        return sum + p.lastPrice * p.requiredQuantity
    }, 0)
)

// ── Optimistic quantity updates ───────────────────────────────────────────────

// One debounced saver per product so rapid edits on different products
// don't cancel each other.
const quantitySavers = new Map<
    string,
    ReturnType<typeof useDebounceFn>
>()

function getQuantitySaver(productId: string) {
    if (!quantitySavers.has(productId)) {
        quantitySavers.set(
            productId,
            useDebounceFn((next: number) => {
                router.put(
                    route("products.update", productId),
                    { required_quantity: next },
                    { preserveScroll: true, preserveState: true }
                )
            }, 600)
        )
    }
    return quantitySavers.get(productId)!
}

function onChangeQuantity(productId: string, next: number) {
    // Optimistic: mutate the local items immediately so the stepper and
    // summary header update before the server responds.
    const item = items.value.find((p) => p.id === productId)
    if (item) item.requiredQuantity = next

    // Debounced: fire a single PUT after the user stops tapping.
    getQuantitySaver(productId)(next)
}

// ── Start shopping day ────────────────────────────────────────────────────────

function handleCreateShoppingDay() {
    router.post(
        route("users.shopping-days.store", { owner: ownerId.value }),
        { date: new Date() }
    )
}

// ── Realtime ──────────────────────────────────────────────────────────────────

const echoUpdated = useEcho<{ product: Product }>(
    `product-list.${ownerId.value}`,
    "Products\\Events\\ProductUpdated",
    ({ product }) => {
        const index = items.value.findIndex((p) => p.id === product.id)
        if (index >= 0) {
            items.value[index] = { ...items.value[index], ...product }
        }
    }
)

const echoCreated = useEcho<{ product: Product }>(
    `product-list.${ownerId.value}`,
    "Products\\Events\\ProductCreated",
    ({ product }) => {
        if (!items.value.some((p) => p.id === product.id)) {
            items.value.push(product)
        }
    }
)

onMounted(() => {
    echoUpdated.listen()
    echoCreated.listen()
})

onUnmounted(() => {
    echoUpdated.leave()
    echoCreated.leave()
})
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Summary header -->
        <header
            class="px-3 py-2 sticky top-0 bg-background z-10 border-b space-y-0.5"
        >
            <div class="flex justify-between items-center gap-3">
                <div>
                    <span class="font-semibold">{{ articleCount }}</span>
                    {{ articleCount === 1 ? "artículo" : "artículos" }}
                    <span
                        v-if="totalUnits > 0 && totalUnits !== articleCount"
                        class="text-muted-foreground text-sm"
                    >
                        ({{ totalUnits }} unidades)
                    </span>
                </div>

                <AppButton
                    variant="default"
                    :disabled="articleCount === 0"
                    @click="handleCreateShoppingDay"
                >
                    Empezar día de compras
                </AppButton>
            </div>

            <div
                v-if="estimatedTotal > 0"
                class="text-sm text-muted-foreground"
            >
                Total estimado: {{ formatCurrency(estimatedTotal) }}
            </div>
        </header>

        <!-- Checklist -->
        <section class="px-3 flex-1 overflow-y-auto">
            <!-- Pass owner only for a shared page so add-product sends user_id -->
            <ProductChecklist
                :products="items"
                :user="owner"
                @change-quantity="onChangeQuantity"
            />
        </section>

        <!-- Footer -->
        <slot name="footer" />
    </div>
</template>

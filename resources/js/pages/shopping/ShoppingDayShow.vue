<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, Link, useForm } from "@inertiajs/vue3"
import type { Page, PageProps } from "@inertiajs/core"
import { SwitchRoot, SwitchThumb } from "radix-vue"

import type { BreadcrumbItem } from "@/types"
import type { ShoppingDay } from "@/types/ShoppingDay"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import NewProductInput from "@/components/shopping/NewProductInput.vue"
import ShoppingList from "@/components/shopping/ShoppingList.vue"
import PreservePendingItems from "@/components/shopping/PreservePendingItems.vue"
import {
    formatCurrency,
    formatDate,
    formatShoppingDay,
} from "@/composables/formatHelpers"
import { useCreateNewProductToShoppingDay } from "@/composables/useCreateNewProductToShoppingDay"
import { useQueuedUpdates } from "@/composables/useQueuedUpdates"
import { Product } from "@/types/Product"

interface Props extends PageProps {
    shoppingDay: ShoppingDay
    products?: Product[]
}

const props = defineProps<Props>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: "Inicio", href: "/" },
    {
        title: `Día de compras: ${formatShoppingDay(props.shoppingDay)}`,
        href: "",
    },
])

// The store order learns from the order items are priced in. Turned off
// when prices were entered after paying (no real route through the store).
const learnOrder = ref(props.shoppingDay.learnOrder)

const { queue: queueDayUpdate } = useQueuedUpdates<{ learnOrder: boolean }>(
    (id) => route("shopping-days.update", { shoppingDay: id }),
    { method: "put", delay: 300 }
)

function setLearnOrder(value: boolean) {
    learnOrder.value = value
    queueDayUpdate(props.shoppingDay.id, { learnOrder: value })
}

const total = ref(0)
const calculatedTotal = ref(0)
const hideChecked = ref(false)

const totalDiff = computed(() => total.value - calculatedTotal.value)
const totalDiffPercent = computed(() =>
    calculatedTotal.value ? (totalDiff.value / calculatedTotal.value) * 100 : 0
)

const items = ref(
    props.shoppingDay.items?.map((item) => ({
        ...item,
        quantity: item.quantity ?? 1,
        unitPrice: item.unitPrice ?? 0,
        checked: Boolean(item.unitPrice),
    }))
)

const computedProducts = computed(() => props.products)
const updateProductsForm = useForm({
    products: props.shoppingDay.items?.map(({ product }) => product.id) ?? [],
})

const productsSuggestions = computed(
    () =>
        computedProducts.value?.filter(
            ({ id }) => !updateProductsForm.products.includes(id)
        ) ?? []
)

const { form: productForm, handleSubmit: handleNewProduct } =
    useCreateNewProductToShoppingDay(
        computed(() => props.shoppingDay.id),
        {
            onSuccess: (response) => {
                const responseProps = (response as Page<Props>).props

                if (!("shoppingDay" in responseProps)) {
                    return
                }

                updateProductsForm.products =
                    responseProps.shoppingDay.items?.map(
                        ({ product }) => product.id
                    ) ?? []

                items.value =
                    responseProps.shoppingDay.items?.map((item) => ({
                        ...item,
                        quantity: item.quantity ?? 1,
                        unitPrice: item.unitPrice ?? 0,
                        checked: Boolean(item.unitPrice),
                    })) ?? []
            },
        }
    )
</script>

<template>
    <Head :title="`Día de compras: ${formatShoppingDay(props.shoppingDay)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <div>
                <h2 class="text-lg font-semibold">
                    Lista de compras:
                    {{ formatShoppingDay(props.shoppingDay, "long") }}
                </h2>
                <small v-if="shoppingDay.updatedAt">
                    (Actualizado: {{ formatDate(shoppingDay.updatedAt) }})
                </small>
                <div
                    v-if="shoppingDay.store"
                    class="flex items-center gap-2 min-h-9 text-sm"
                >
                    <SwitchRoot
                        id="learn-order"
                        :checked="learnOrder"
                        class="w-12 h-7 px-0.5 shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background flex bg-input data-[state=checked]:bg-primary shadow-xs rounded-full relative cursor-default transition-colors"
                        @update:checked="setLearnOrder"
                    >
                        <SwitchThumb
                            class="block size-6 my-auto bg-background shadow-sm rounded-full transition-transform duration-100 will-change-transform data-[state=checked]:translate-x-5"
                        />
                    </SwitchRoot>
                    <label for="learn-order" class="select-none">
                        Aprender el orden de {{ shoppingDay.store.name }} de
                        este recorrido
                        <span class="block text-xs text-muted-foreground">
                            Desactívalo si llenaste los precios después de
                            pagar.
                        </span>
                    </label>
                </div>
            </div>
            <header
                class="text-xl sticky top-0 pt-2 pb-1 bg-background flex justify-between gap-3 z-10 border-b-2 border-white-c -mx-2 px-2 items-center"
            >
                <span class="flex flex-col min-w-0">
                    <span>Total: <span>{{ formatCurrency(total) }}</span></span>
                    <span
                        v-if="calculatedTotal > 0 && totalDiff !== 0"
                        :class="[
                            'text-sm font-medium',
                            totalDiff > 0 ? 'text-red-500' : 'text-green-500',
                        ]"
                    >
                        {{ totalDiff > 0 ? "+" : "" }}{{ formatCurrency(totalDiff) }}
                        ({{ totalDiff > 0 ? "+" : "" }}{{ totalDiffPercent.toFixed(1) }}%)
                        <span class="text-xs font-normal opacity-70">vs. anterior</span>
                    </span>
                </span>
                <label
                    class="text-xs inline-flex items-center gap-2 py-2 shrink-0 select-none"
                >
                    Ocultar comprados
                    <input
                        type="checkbox"
                        class="size-5 accent-primary"
                        v-model="hideChecked"
                    />
                </label>
            </header>
            <ShoppingList
                v-model="items"
                v-model:total="total"
                v-model:calculatedTotal="calculatedTotal"
                :shoppingDay="shoppingDay"
                :hideChecked="hideChecked"
            />

            <form
                class="flex gap-2 my-3 flex-col mx-3"
                @submit.prevent="handleNewProduct"
            >
                <NewProductInput
                    label="Agregar producto"
                    v-model="productForm.name"
                    :loading="productForm.processing"
                    :productsSuggestions="productsSuggestions"
                />
            </form>

            <div
                class="flex gap-2 bottom-0 left-0 right-0 sticky mt-2 py-2 bg-background [&>*]:h-11"
            >
                <AppButton
                    :as="Link"
                    :href="route('shopping-days.edit', { shoppingDay })"
                    class="flex-1"
                    variant="secondary"
                >
                    Editar
                </AppButton>
                <PreservePendingItems :shoppingDay="shoppingDay" />
            </div>
        </div>
    </AppLayout>
</template>

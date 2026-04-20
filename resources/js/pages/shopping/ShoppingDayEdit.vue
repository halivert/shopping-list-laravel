<script setup lang="ts">
import { computed, ref } from "vue"
import { useDebounceFn } from "@vueuse/core"
import { Head, router, useForm } from "@inertiajs/vue3"
import type { Page, PageProps } from "@inertiajs/core"
import { Search, X } from "lucide-vue-next"

import AppLayout from "@/layouts/AppLayout.vue"
import type { BreadcrumbItem } from "@/types"
import type { Product } from "@/types/Product"
import type { ShoppingDay } from "@/types/ShoppingDay"
import AppButton from "@/components/ui/button/Button.vue"
import AppInput from "@/components/ui/input/Input.vue"
import Separator from "@/components/ui/separator/Separator.vue"
import NewProductInput from "@/components/shopping/NewProductInput.vue"
import ProductCheckboxItem from "@/components/shopping/ProductCheckboxItem.vue"
import QuantityControl from "@/components/shopping/QuantityControl.vue"
import { formatCurrency, formatDate } from "@/composables/formatHelpers"
import { useCreateNewProductToShoppingDay } from "@/composables/useCreateNewProductToShoppingDay"
import { useEditShoppingDay } from "@/composables/useEditShoppingDay"
import { useProductSelection } from "@/composables/useProductSelection"
import EditShoppingDayDateInput from "@/components/shopping/EditShoppingDayDateInput.vue"
import DeleteShoppingDay from "@/components/shopping/DeleteShoppingDay.vue"

interface Props extends PageProps {
    shoppingDay: ShoppingDay
    products: Product[]
}

const props = defineProps<Props>()

const computedProducts = computed(() => props.products)
const shoppingDay = computed(() => props.shoppingDay)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Inicio",
        href: "/",
    },
    {
        title: `Día de compras: ${formatDate(shoppingDay.value.date)}`,
        href: "",
    },
])

const updateProductsForm = useForm({
    products: props.shoppingDay.items?.map(({ product }) => product.id) ?? [],
    items: [] as { id: string; quantity: number }[],
})

const {
    searchQuery,
    selectedIds,
    filteredProducts,
    selectedProducts,
    availableProducts,
    toggleProduct,
    getQuantity,
    setQuantity,
    getItemsPayload,
} = useProductSelection(
    computedProducts,
    updateProductsForm.products,
    props.shoppingDay.items ?? []
)

type SaveStatus = "idle" | "pending" | "saving" | "saved"
const saveStatus = ref<SaveStatus>("idle")
let savedTimer: ReturnType<typeof setTimeout> | null = null

const autoSaveItems = useDebounceFn(function autoSaveItems() {
    updateProductsForm.products = selectedIds.value
    updateProductsForm.items = getItemsPayload(props.shoppingDay.items ?? [])
    handleSaveShoppingDay({ async: true })
}, 6 * 1000)

function handleToggleProduct(id: string, checked: boolean) {
    toggleProduct(id, checked)
    saveStatus.value = "pending"
    autoSaveItems()
}

function handleQuantityChange(productId: string, quantity: number) {
    setQuantity(productId, quantity)
    saveStatus.value = "pending"
    autoSaveItems()
}

const { form: productForm, handleSubmit: handleNewProduct } =
    useCreateNewProductToShoppingDay(
        computed(() => shoppingDay.value.id),
        {
            onSuccess: (response) => {
                const responseProps = (response as Page<Props>).props

                if (!("shoppingDay" in responseProps)) {
                    return
                }

                const newIds =
                    responseProps.shoppingDay.items?.map(
                        ({ product }) => product.id
                    ) ?? []
                updateProductsForm.products = newIds
                selectedIds.value = newIds
            },
        }
    )

function handleSaveProducts() {
    updateProductsForm.products = selectedIds.value
    updateProductsForm.items = getItemsPayload(props.shoppingDay.items ?? [])
    handleSaveShoppingDay({ async: false })
}

function handleSaveShoppingDay({ async }: { async: boolean }) {
    updateProductsForm.patch(
        route("shopping-days.update", {
            shoppingDay: props.shoppingDay.id,
        }),
        {
            async,
            preserveScroll: true,
            onStart: () => {
                if (async) saveStatus.value = "saving"
            },
            onSuccess: () => {
                if (!async) {
                    return router.get(
                        route("shopping-days.show", {
                            shoppingDay: props.shoppingDay.id,
                        })
                    )
                }
                saveStatus.value = "saved"
                if (savedTimer) clearTimeout(savedTimer)
                savedTimer = setTimeout(() => {
                    saveStatus.value = "idle"
                }, 2000)

                // Newly-toggled products are created with qty=1 by the server.
                // If the user changed the quantity before the first save, sync it now.
                const staleItems = (props.shoppingDay.items ?? []).filter(
                    (item) => getQuantity(item.product.id) !== item.quantity
                )
                if (staleItems.length > 0) {
                    updateProductsForm.products = selectedIds.value
                    updateProductsForm.items = getItemsPayload(
                        props.shoppingDay.items ?? []
                    )
                    handleSaveShoppingDay({ async: true })
                }
            },
            onError: () => {
                if (async) saveStatus.value = "idle"
            },
        }
    )
}

const productsSuggestions = computed(() =>
    computedProducts.value.filter(
        ({ id }) => !selectedIds.value.includes(id)
    )
)

const {
    isEditing: editDate,
    form: editDateForm,
    handleSubmit: handleSubmitDate,
} = useEditShoppingDay(
    shoppingDay,
    computed(() => ({ products: selectedIds.value }))
)

const estimatedTotal = computed(() =>
    selectedIds.value.reduce((total, productId) => {
        const product = props.products.find((p) => p.id === productId)
        if (!product || !product.lastPrice) return total

        const quantity = getQuantity(productId)

        return total + product.lastPrice * quantity
    }, 0)
)
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <header class="p-3 top-0 sticky bg-background z-10 space-y-2">
            <div class="w-full flex justify-between items-center">
                <form @submit.prevent="handleSubmitDate">
                    <EditShoppingDayDateInput
                        class="flex gap-2 items-center"
                        v-model="editDateForm.date"
                        v-model:isEditing="editDate"
                    >
                        {{ formatDate(shoppingDay.date, "full") }}
                    </EditShoppingDayDateInput>
                </form>

                <AppButton form="productsForm" type="submit">
                    ¡De compras!
                </AppButton>
            </div>

            <div class="text-sm text-muted-foreground">
                Total estimado: {{ formatCurrency(estimatedTotal) }}
                <span v-if="saveStatus === 'pending'"> · Pendiente…</span>
                <span v-else-if="saveStatus === 'saving'"> · Guardando…</span>
                <span v-else-if="saveStatus === 'saved'"> · Guardado</span>
            </div>

            <div class="relative">
                <Search
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"
                />
                <AppInput
                    v-model="searchQuery"
                    placeholder="Buscar producto..."
                    class="pl-8 pr-8"
                    autocomplete="off"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    @click="searchQuery = ''"
                >
                    <X class="size-4" />
                </button>
            </div>
        </header>

        <form
            class="flex gap-2 my-3 flex-col mx-3"
            @submit.prevent="handleNewProduct"
        >
            <NewProductInput
                v-model="productForm.name"
                :loading="productForm.processing"
                :productsSuggestions="productsSuggestions"
            />
        </form>

        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <form
                @submit.prevent="handleSaveProducts"
                id="productsForm"
                class="space-y-3"
            >
                <div v-if="selectedProducts.length > 0">
                    <p class="text-xs font-medium text-muted-foreground px-2 mb-1">
                        Seleccionados ({{ selectedProducts.length }})
                    </p>
                    <div class="flex flex-col">
                        <div
                            v-for="product in selectedProducts"
                            :key="product.id"
                            class="flex items-center gap-2 pr-2"
                        >
                            <ProductCheckboxItem
                                class="flex-1 min-w-0"
                                :product="product"
                                :checked="true"
                                @update:checked="
                                    handleToggleProduct(product.id, $event)
                                "
                            />
                            <QuantityControl
                                :modelValue="getQuantity(product.id)"
                                @update:modelValue="
                                    handleQuantityChange(product.id, $event)
                                "
                            />
                        </div>
                    </div>
                </div>

                <Separator
                    v-if="
                        selectedProducts.length > 0 &&
                        availableProducts.length > 0
                    "
                />

                <div v-if="availableProducts.length > 0">
                    <p class="text-xs font-medium text-muted-foreground px-2 mb-1">
                        Disponibles ({{ availableProducts.length }})
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-2 gap-y-0.5">
                        <ProductCheckboxItem
                            v-for="product in availableProducts"
                            :key="product.id"
                            :product="product"
                            :checked="false"
                            @update:checked="
                                handleToggleProduct(product.id, $event)
                            "
                        />
                    </div>
                </div>

                <p
                    v-if="filteredProducts.length === 0 && searchQuery"
                    class="text-sm text-muted-foreground px-2 py-4 text-center"
                >
                    Sin resultados para "{{ searchQuery }}"
                </p>

                <p
                    v-if="computedProducts.length === 0 && !searchQuery"
                    class="text-sm text-muted-foreground px-2 py-4 text-center"
                >
                    Aún no tienes productos. Agrega el primero arriba.
                </p>
            </form>

            <div class="flex justify-end mt-6 pt-6 border-t">
                <DeleteShoppingDay :shoppingDay="shoppingDay" />
            </div>
        </div>
    </AppLayout>
</template>

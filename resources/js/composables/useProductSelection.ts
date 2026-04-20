import { computed, reactive, ref, type Ref } from "vue"
import type { Product } from "@/types/Product"
import type { ShoppingDayItem } from "@/types/ShoppingDayItem"

export function useProductSelection(
    products: Ref<Product[]>,
    initialSelectedIds: string[],
    initialItems: ShoppingDayItem[] = []
) {
    const searchQuery = ref("")
    const selectedIds = ref<string[]>([...initialSelectedIds])

    const itemQuantities = reactive<Record<string, number>>(
        Object.fromEntries(initialItems.map((item) => [item.product.id, item.quantity ?? 1]))
    )

    function getQuantity(productId: string): number {
        return itemQuantities[productId] ?? 1
    }

    function setQuantity(productId: string, quantity: number) {
        if (quantity <= 0) return
        itemQuantities[productId] = quantity
    }

    function getItemsPayload(items: ShoppingDayItem[]): { id: string; quantity: number }[] {
        return items.map((item) => ({
            id: item.id,
            quantity: itemQuantities[item.product.id] ?? 1,
        }))
    }

    const filteredProducts = computed(() => {
        const query = searchQuery.value.toLowerCase().trim()
        if (!query) return products.value
        return products.value.filter(({ name }) =>
            name.toLowerCase().includes(query)
        )
    })

    const selectedProducts = computed(() =>
        filteredProducts.value
            .filter(({ id }) => selectedIds.value.includes(id))
            .sort((a, b) => (a.shoppingIndex ?? 0) - (b.shoppingIndex ?? 0))
    )

    const availableProducts = computed(() =>
        filteredProducts.value
            .filter(({ id }) => !selectedIds.value.includes(id))
            .sort((a, b) => (a.searchIndex ?? 0) - (b.searchIndex ?? 0))
    )

    function toggleProduct(id: string, checked: boolean) {
        if (checked) {
            if (!selectedIds.value.includes(id)) {
                selectedIds.value = [...selectedIds.value, id]
            }
        } else {
            selectedIds.value = selectedIds.value.filter((p) => p !== id)
        }
    }

    return {
        searchQuery,
        selectedIds,
        filteredProducts,
        selectedProducts,
        availableProducts,
        toggleProduct,
        getQuantity,
        setQuantity,
        getItemsPayload,
    }
}

import { computed, ref, type Ref } from "vue"
import type { Product } from "@/types/Product"
import type { ShoppingDayItem } from "@/types/ShoppingDayItem"

export function useProductSelection(
    products: Ref<Product[]>,
    initialSelectedIds: string[],
    initialItems: ShoppingDayItem[] = []
) {
    const searchQuery = ref("")
    const selectedIds = ref<string[]>([...initialSelectedIds])

    const itemQuantities = ref<Record<string, number>>(
        Object.fromEntries(initialItems.map((item) => [item.id, item.quantity ?? 1]))
    )

    function getQuantity(itemId: string): number {
        return itemQuantities.value[itemId] ?? 1
    }

    function setQuantity(itemId: string, quantity: number) {
        if (quantity <= 0) return
        itemQuantities.value = { ...itemQuantities.value, [itemId]: quantity }
    }

    function getItemsPayload(): { id: string; quantity: number }[] {
        return Object.entries(itemQuantities.value).map(([id, quantity]) => ({
            id,
            quantity,
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

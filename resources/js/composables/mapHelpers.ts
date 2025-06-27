import { type Item } from "@/components/SortableItemList.vue"
import type { Product } from "@/types/Product"
import type { ShoppingDayItem } from "@/types/ShoppingDayItem"

export function shoppingDayItemToSortableListItem(
    item: ShoppingDayItem
): Item & { type: "item" } {
    return {
        id: item.id,
        name: item.product.name,
        type: "item",
    }
}

export function productToSortableListItem(
    product: Product
): Item & { type: "product" } {
    return {
        id: product.id,
        name: product.name,
        type: "product",
    }
}

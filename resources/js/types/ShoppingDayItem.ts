import { Product } from "./Product"
import { ShoppingDay } from "./ShoppingDay"

export interface ShoppingDayItem {
    id: string
    shoppingDay?: ShoppingDay
    product: Product
    index: number
    unitPrice: number
    quantity: number
}

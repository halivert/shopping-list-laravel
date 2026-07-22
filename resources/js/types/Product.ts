import { User } from "."

/**
 * Canonical units a product's price can be expressed per. Kept in sync with
 * the backend's Product::UNITS (app/Products/Product.php) — every unit here
 * always has a metric conversion pair (ml<->L, g<->kg), which
 * UnitPriceCalculator.vue relies on.
 */
export const PRODUCT_UNITS = ["L", "ml", "kg", "g"] as const

export interface Product {
    id: string
    name: string
    unit?: string | null
    owner?: User
    searchIndex?: number
    shoppingIndex?: number
    lastPrice?: number
    isRequired: boolean
    requiredQuantity: number
}

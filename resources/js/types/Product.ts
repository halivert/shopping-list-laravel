import { User } from "."

/**
 * Canonical units a product's price can be expressed per. Kept in sync with
 * the backend's Product::UNITS (app/Products/Product.php). Only large units:
 * prices per g/ml lose precision in the database. Packages labeled in g/ml
 * are converted by UnitPriceCalculator.vue.
 */
export const PRODUCT_UNITS = ["L", "kg"] as const

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

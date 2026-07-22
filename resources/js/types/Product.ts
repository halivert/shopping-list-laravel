import { User } from "."

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

import { User } from "."
import { ShoppingDayItem } from "./ShoppingDayItem"
import { Store } from "./Store"

export interface ShoppingDay {
    id: string
    date: string
    owner: User
    store?: Store
    /** Whether this trip teaches the store order (see LearnStoreOrder) */
    learnOrder: boolean
    items?: ShoppingDayItem[]
    updatedAt?: string
}

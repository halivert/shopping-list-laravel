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
    /** Already learned: its switch can no longer change anything */
    learned: boolean
    items?: ShoppingDayItem[]
    updatedAt?: string
}

import { User } from "."
import { ShoppingDayItem } from "./ShoppingDayItem"
import { Store } from "./Store"

export interface ShoppingDay {
    id: string
    date: string
    owner: User
    store?: Store
    items?: ShoppingDayItem[]
    updatedAt?: string
}

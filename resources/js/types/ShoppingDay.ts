import { User } from "."
import { ShoppingDayItem } from "./ShoppingDayItem"

export interface ShoppingDay {
    id: string
    date: string
    owner: User
    items?: ShoppingDayItem[]
}

import { User } from "."

export interface Product {
    id: string
    name: string
    owner?: User
    searchIndex?: number
    shoppingIndex?: number
    lastPrice?: number
}

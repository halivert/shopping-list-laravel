import { User } from "."

export interface Product {
    id: string
    name: string
    owner?: User
}

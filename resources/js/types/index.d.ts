import type { PageProps } from "@inertiajs/core"
import type { LucideIcon } from "lucide-vue-next"
import type { Config } from "ziggy-js"

export interface Auth {
    user?: User
}

export interface BreadcrumbItem {
    title: string
    href: string
}

export interface NavLinkItem {
    key?: string
    title: string
    href: string
    icon?: LucideIcon
    isActive?: boolean

    items?: never
}

export interface NavGroupItem {
    key?: string
    title: string
    icon?: LucideIcon
    items: NavLinkItem[]
    footer?: NavLinkItem

    href?: never
}

export type NavItem = NavLinkItem | NavGroupItem

export interface SharedData extends PageProps {
    name: string
    auth: Auth
    lang: string
    ziggy: Config & { location: string }

    sidebarShoppingDays?: ShoppingDay[]
}

export interface User {
    id: number
    name: string
    email: string
    createdAt: string
    updatedAt: string
}

export interface Paginator<TData> {
    current_page: number
    data: TData[]
    first_page_url: string
    from: number | null
    last_page: number
    last_page_url: string
    links: {
        url: null | string
        label: string
        active: boolean
    }[]
    next_page_url: string | null
    path: string
    per_page: number
    prev_page_url: string | null
    to: number | null
    total: number
}

export type BreadcrumbItemType = BreadcrumbItem

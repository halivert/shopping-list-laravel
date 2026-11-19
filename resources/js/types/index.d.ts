import type { PageProps } from "@inertiajs/core"
import type { LucideIcon } from "lucide-vue-next"
import type { Config } from "ziggy-js"

import type { Store } from "./Store"

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
    /** Makes the group title a link */
    href?: string
    /** primary: big bold title; secondary (default): small muted label */
    variant?: "primary" | "secondary"
    /** Draws a separator before the group */
    separated?: boolean
    items: NavLinkItem[]
    footer?: NavLinkItem
}

export type NavItem = NavLinkItem | NavGroupItem

export interface DeletedProductFlash {
    id: string
    name: string
}

export interface SharedData extends PageProps {
    name: string
    auth: Auth
    lang: string
    ziggy: Config & { location: string }

    sidebarShoppingDays?: ShoppingDay[]
    sidebarStores?: SidebarStores | null
    flash?: {
        deletedProduct?: DeletedProductFlash | null
    }
}

/** Stores listed in the sidebar and the owner whose sort page they open */
export interface SidebarStores {
    owner: User
    stores: Store[]
}

export interface User {
    id: number
    name: string
    email: string
    avatar?: string
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

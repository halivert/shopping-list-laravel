import { computed, MaybeRef, unref } from "vue"
import { usePage } from "@inertiajs/vue3"
import { Dot, Plus, ShoppingBasket } from "lucide-vue-next"

import type { NavGroupItem, NavLinkItem, SidebarStores } from "@/types"

/**
 * "Productos" section of the sidebar, for the owner being shown. Stores are
 * its sub items: each one is a way of sorting the products, so it opens the
 * sort page on that store; "Nueva tienda" opens it with the create dialog
 * (`?store=new`).
 */
export function useSidebarProductsMenuItems(
    sidebarStores: MaybeRef<SidebarStores | null | undefined>
) {
    const page = usePage()

    const sidebarProductsMenuItems = computed((): NavGroupItem[] => {
        const data = unref(sidebarStores)
        if (!data) return []

        const { owner, stores } = data
        const isOwn = page.props.auth.user?.id === owner.id

        const sortUrl = (store: string) =>
            route("users.products.sort", { owner: owner.id, store }, false)

        return [
            {
                key: "products",
                title: isOwn ? "Productos" : `Productos de ${owner.name}`,
                href: route("users.products.index", { owner: owner.id }, false),
                icon: ShoppingBasket,
                variant: "primary",
                separated: true,
                items: stores.map(
                    (store): NavLinkItem => ({
                        key: store.id,
                        title: `Orden en ${store.name}`,
                        href: sortUrl(store.id),
                        icon: Dot,
                    })
                ),
                footer: {
                    title: "Nueva tienda",
                    href: sortUrl("new"),
                    icon: Plus,
                },
            },
        ]
    })

    return { sidebarProductsMenuItems }
}

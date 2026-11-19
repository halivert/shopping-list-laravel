import { computed, MaybeRef, unref } from "vue"
import { usePage } from "@inertiajs/vue3"
import { Dot, Plus, Store as StoreIcon } from "lucide-vue-next"

import type { NavGroupItem, NavLinkItem, SidebarStores } from "@/types"

/**
 * Sidebar group with the stores of the owner being shown; each one opens
 * the sort page on that store. "Nueva tienda" opens it with the create
 * dialog (`?store=new`).
 */
export function useSidebarStoresMenuItems(
    sidebarStores: MaybeRef<SidebarStores | null | undefined>
) {
    const page = usePage()

    const sidebarStoresMenuItems = computed((): NavGroupItem[] => {
        const data = unref(sidebarStores)
        if (!data) return []

        const { owner, stores } = data
        const isOwn = page.props.auth.user?.id === owner.id

        const sortUrl = (store: string) =>
            route("users.products.sort", { owner: owner.id, store }, false)

        return [
            {
                title: isOwn ? "Tiendas" : `Tiendas de ${owner.name}`,
                icon: StoreIcon,
                items: stores.map(
                    (store): NavLinkItem => ({
                        key: store.id,
                        title: store.name,
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

    return { sidebarStoresMenuItems }
}

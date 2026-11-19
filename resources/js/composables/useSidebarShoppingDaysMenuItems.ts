import { computed, MaybeRef, unref } from "vue"
import { usePage } from "@inertiajs/vue3"
import { CalendarDays, Dot, ShoppingCart } from "lucide-vue-next"

import type { ShoppingDay } from "@/types/ShoppingDay"
import type { NavGroupItem, NavLinkItem } from "@/types"
import { formatShoppingDay, toLocalDateString } from "./formatHelpers"

export function useSidebarShoppingDaysMenuItems(
    sidebarShoppingDays: MaybeRef<ShoppingDay[] | undefined>
) {
    const page = usePage()

    const sidebarShoppingDaysOwner = computed(() => {
        const firstShoppingDay: ShoppingDay | undefined =
            unref(sidebarShoppingDays)?.at(0)

        return firstShoppingDay?.owner
    })

    const sidebarTitle = computed(() => {
        const owner = sidebarShoppingDaysOwner.value
        const user = page.props.auth.user

        if (user?.id === owner?.id || !owner) {
            return "Días de compras"
        }

        return `Días de compras de ${owner.name}`
    })

    const sidebarFooter = computed(() => {
        return sidebarShoppingDaysOwner.value
            ? {
                  title: "Ver más",
                  href: route(
                      "users.shopping-days.index",
                      {
                          owner: sidebarShoppingDaysOwner.value,
                      },
                      false
                  ),
                  icon: CalendarDays,
              }
            : undefined
    })

    const dayLink = (day: ShoppingDay, title: string): NavLinkItem => ({
        key: day.id,
        title,
        href: route("shopping-days.show", { shoppingDay: day.id }, false),
        icon: Dot,
    })

    /**
     * Today's days get their own prominent group: they are the lists open
     * while shopping (e.g. 3B, then Aurrera). Earlier days follow, muted.
     */
    const sidebarShoppingDaysMenuItems = computed((): NavGroupItem[] => {
        const days = unref(sidebarShoppingDays) ?? []
        if (!days.length) return []

        const today = toLocalDateString()
        const todays = days.filter((day) => day.date === today)
        const earlier = days.filter((day) => day.date !== today)

        const owner = sidebarShoppingDaysOwner.value
        const isOwn = !owner || owner.id === page.props.auth.user?.id

        const groups: NavGroupItem[] = []

        if (todays.length) {
            groups.push({
                key: "today",
                title: isOwn ? "Hoy" : `Hoy · ${owner?.name}`,
                icon: ShoppingCart,
                variant: "primary",
                items: todays.map((day) =>
                    dayLink(day, day.store?.name ?? "Lista de hoy")
                ),
            })
        }

        groups.push({
            key: "days",
            title: todays.length ? "Días anteriores" : sidebarTitle.value,
            icon: CalendarDays,
            items: earlier.map((day) =>
                dayLink(day, formatShoppingDay(day, "long"))
            ),
            footer: sidebarFooter.value,
        })

        return groups
    })

    return { sidebarShoppingDaysMenuItems, sidebarShoppingDaysOwner }
}

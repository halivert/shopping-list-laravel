import { computed, MaybeRef, unref } from "vue"
import { usePage } from "@inertiajs/vue3"
import { CalendarDays, Dot } from "lucide-vue-next"

import type { ShoppingDay } from "@/types/ShoppingDay"
import type { NavLinkItem } from "@/types"
import { formatDate } from "./formatHelpers"

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
                  href: route("users.shopping-days.index", {
                      owner: sidebarShoppingDaysOwner.value,
                  }),
                  icon: CalendarDays,
              }
            : undefined
    })

    const sidebarShoppingDaysMenuItems = computed(() =>
        unref(sidebarShoppingDays)?.length
            ? [
                  {
                      title: sidebarTitle.value,
                      icon: CalendarDays,
                      items:
                          unref(sidebarShoppingDays)?.map(
                              (day): NavLinkItem => ({
                                  key: day.id,
                                  title: formatDate(day.date, "long"),
                                  href: route("shopping-days.show", {
                                      shoppingDay: day.id,
                                  }),
                                  icon: Dot,
                              })
                          ) ?? [],
                      footer: sidebarFooter.value,
                  },
              ]
            : undefined
    )

    return { sidebarShoppingDaysMenuItems }
}

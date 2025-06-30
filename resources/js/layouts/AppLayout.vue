<script setup lang="ts">
import { computed } from "vue"
import { usePage } from "@inertiajs/vue3"

import type { BreadcrumbItemType, NavItem } from "@/types"
import AppLayout from "@/layouts/app/AppSidebarLayout.vue"
import { useSidebarShoppingDaysMenuItems } from "@/composables/useSidebarShoppingDaysMenuItems"
import { ShoppingBasket } from "lucide-vue-next"

interface Props {
    breadcrumbs?: BreadcrumbItemType[]
    sidebarMainItems?: NavItem[]
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

const page = usePage()

const { sidebarShoppingDaysMenuItems, sidebarShoppingDaysOwner } =
    useSidebarShoppingDaysMenuItems(
        computed(() => page.props.sidebarShoppingDays)
    )

const productsLink = computed((): NavItem | undefined =>
    sidebarShoppingDaysOwner.value?.id === page.props.auth.user?.id
        ? {
              title: "Productos",
              href: route("users.products.index", {
                  owner: sidebarShoppingDaysOwner.value?.id,
              }),
              icon: ShoppingBasket,
          }
        : undefined
)

const sidebarMainItems = computed((): NavItem[] | undefined => [
    ...(props.sidebarMainItems ?? []),
    ...(productsLink.value ? [productsLink.value] : []),
    ...(sidebarShoppingDaysMenuItems.value ?? []),
])
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :sidebarMainItems="sidebarMainItems">
        <slot />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { usePage } from "@inertiajs/vue3"

import type { BreadcrumbItemType, NavItem } from "@/types"
import AppLayout from "@/layouts/app/AppSidebarLayout.vue"
import { useSidebarProductsMenuItems } from "@/composables/useSidebarProductsMenuItems"
import { useSidebarShoppingDaysMenuItems } from "@/composables/useSidebarShoppingDaysMenuItems"

interface Props {
    breadcrumbs?: BreadcrumbItemType[]
    sidebarMainItems?: NavItem[]
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

const page = usePage()

const { sidebarShoppingDaysMenuItems } = useSidebarShoppingDaysMenuItems(
    computed(() => page.props.sidebarShoppingDays)
)

// Always present: it follows the owner of the sidebar (own or shared),
// not the shopping days, so it shows up before the first shopping day too
const { sidebarProductsMenuItems } = useSidebarProductsMenuItems(
    computed(() => page.props.sidebarStores)
)

// Shopping days first: the app is for shopping. Products (and their store
// orders) below, as the reference to consult or fix.
const sidebarMainItems = computed((): NavItem[] | undefined => [
    ...(props.sidebarMainItems ?? []),
    ...sidebarShoppingDaysMenuItems.value,
    ...sidebarProductsMenuItems.value,
])
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :sidebarMainItems="sidebarMainItems">
        <slot />
    </AppLayout>
</template>

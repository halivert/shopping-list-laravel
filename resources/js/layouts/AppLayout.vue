<script setup lang="ts">
import { computed } from "vue"
import { usePage } from "@inertiajs/vue3"

import type { BreadcrumbItemType, NavItem } from "@/types"
import AppLayout from "@/layouts/app/AppSidebarLayout.vue"
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

const sidebarMainItems = computed(
    (): NavItem[] | undefined =>
        props.sidebarMainItems ?? sidebarShoppingDaysMenuItems.value
)
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :sidebarMainItems="sidebarMainItems">
        <slot />
    </AppLayout>
</template>

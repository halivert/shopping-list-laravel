<script setup lang="ts">
import AppContent from "@/components/AppContent.vue"
import AppShell from "@/components/AppShell.vue"
import AppSidebar from "@/components/AppSidebar.vue"
import AppSidebarHeader from "@/components/AppSidebarHeader.vue"
import type { BreadcrumbItemType, NavItem } from "@/types"

interface Props {
    breadcrumbs?: BreadcrumbItemType[]
    sidebarMainItems?: NavItem[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar :mainItems="sidebarMainItems" />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>

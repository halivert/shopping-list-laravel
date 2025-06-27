<script setup lang="ts">
import { computed } from "vue"
import { Link } from "@inertiajs/vue3"
import { LayoutGrid } from "lucide-vue-next"

import NavFooter from "@/components/NavFooter.vue"
import NavMain from "@/components/NavMain.vue"
import NavUser from "@/components/NavUser.vue"
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar"
import { type NavItem } from "@/types"
import AppLogo from "./AppLogo.vue"

interface Props {
    mainItems?: NavItem[]
}

const props = withDefaults(defineProps<Props>(), {
    mainItems: () => [],
})

const mainNavItems = computed((): NavItem[] => [
    {
        title: "Dashboard",
        href: "/",
        icon: LayoutGrid,
    },
    ...props.mainItems,
])

const footerNavItems: NavItem[] = []
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('home')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

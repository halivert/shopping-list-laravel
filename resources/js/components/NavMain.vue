<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar"
import { type NavItem, type SharedData } from "@/types"
import { Link, usePage } from "@inertiajs/vue3"
import SidebarMenuSubButton from "./ui/sidebar/SidebarMenuSubButton.vue"

defineProps<{
    items: NavItem[]
}>()

const page = usePage<SharedData>()
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in items"
                :key="item.key ?? item.title"
            >
                <template v-if="item.items">
                    <SidebarGroupLabel>{{ item.title }}</SidebarGroupLabel>

                    <SidebarMenuSubButton
                        as-child
                        v-for="subItem in item.items"
                        :key="subItem.key ?? subItem.title"
                        :tooltip="subItem.title"
                    >
                        <Link :href="subItem.href">
                            <component :is="subItem.icon" />
                            <span>{{ subItem.title }}</span>
                        </Link>
                    </SidebarMenuSubButton>

                    <SidebarMenuButton
                        as-child
                        v-if="item.footer"
                        :tooltip="item.title"
                    >
                        <Link :href="item.footer.href">
                            <component :is="item.footer.icon" />
                            <span>{{ item.footer.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </template>
                <SidebarMenuButton
                    v-else-if="item.href"
                    as-child
                    :is-active="item.href === page.url"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

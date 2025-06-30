<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3"

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuSubButton,
    SidebarMenuItem,
    SidebarMenuSubItem,
} from "@/components/ui/sidebar"
import type { NavItem, SharedData } from "@/types"

defineProps<{
    items: NavItem[]
}>()

const page = usePage<SharedData>()
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <template v-for="item in items" :key="item.key ?? item.title">
                <SidebarMenuItem v-if="item.href">
                    <SidebarMenuButton
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

                <SidebarMenuItem v-if="item.items">
                    <SidebarMenuSubItem>
                        <SidebarGroupLabel as="span">
                            {{ item.title }}
                        </SidebarGroupLabel>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubButton
                        as-child
                        v-for="subItem in item.items"
                        :is-active="item.href === page.url"
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
                        :is-active="item.footer.href === page.url"
                        :tooltip="item.title"
                    >
                        <Link :href="item.footer.href">
                            <component :is="item.footer.icon" />
                            <span>{{ item.footer.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

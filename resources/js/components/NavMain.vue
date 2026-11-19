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
    SidebarSeparator,
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
                <!-- Single link -->
                <SidebarMenuItem v-if="!item.items">
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

                <!-- Group -->
                <template v-else>
                    <li v-if="item.separated" role="presentation">
                        <SidebarSeparator class="mx-0 my-2" />
                    </li>

                    <SidebarMenuItem>
                        <!-- Primary title: big and bold, optionally a link -->
                        <template v-if="item.variant === 'primary'">
                            <SidebarMenuButton
                                v-if="item.href"
                                as-child
                                size="lg"
                                class="text-base font-semibold"
                                :is-active="item.href === page.url"
                                :tooltip="item.title"
                            >
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuButton
                                v-else
                                as="div"
                                size="lg"
                                class="text-base font-semibold pointer-events-none"
                                :tooltip="item.title"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </template>

                        <!-- Secondary title: small muted label -->
                        <SidebarMenuSubItem v-else>
                            <SidebarGroupLabel as="span">
                                {{ item.title }}
                            </SidebarGroupLabel>
                        </SidebarMenuSubItem>

                        <SidebarMenuSubButton
                            as-child
                            v-for="subItem in item.items"
                            :is-active="subItem.href === page.url"
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
                            :tooltip="item.footer.title"
                        >
                            <Link :href="item.footer.href">
                                <component :is="item.footer.icon" />
                                <span>{{ item.footer.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </template>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

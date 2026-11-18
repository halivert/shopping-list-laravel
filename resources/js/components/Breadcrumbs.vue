<script setup lang="ts">
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Link } from "@inertiajs/vue3"

interface BreadcrumbItem {
    title: string
    href?: string
}

defineProps<{
    breadcrumbs: BreadcrumbItem[]
}>()
</script>

<template>
    <Breadcrumb class="min-w-0">
        <BreadcrumbList class="flex-nowrap">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <!-- On mobile only the current page is shown -->
                <BreadcrumbItem
                    :class="
                        index === breadcrumbs.length - 1
                            ? 'min-w-0'
                            : 'hidden md:inline-flex'
                    "
                >
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage class="truncate">{{
                            item.title
                        }}</BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link :href="item.href ?? '#'">{{
                                item.title
                            }}</Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    class="hidden md:block"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>

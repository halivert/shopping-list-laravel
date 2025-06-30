<script setup lang="ts">
import { computed } from "vue"
import { Head, InertiaLinkProps, Link } from "@inertiajs/vue3"

import type { BreadcrumbItem, Paginator, User } from "@/types"
import type { ShoppingDay } from "@/types/ShoppingDay"
import type { ShoppingDayItem } from "@/types/ShoppingDayItem"
import AppLayout from "@/layouts/AppLayout.vue"
import { formatCurrency, formatDate } from "@/composables/formatHelpers"

interface Props {
    owner: User
    shoppingDaysPaginator: Paginator<ShoppingDay>
}

interface ExtraLinkProps {
    disabled: boolean
}

const props = defineProps<Props>()

const breadcrumbs = computed((): BreadcrumbItem[] => [
    {
        title: "Inicio",
        href: "/",
    },
    {
        title: `Días de compras de ${props.owner.name}`,
        href: "",
    },
])

function getShoppingDayTotal(items: ShoppingDayItem[] | undefined): number {
    return (
        items?.reduce((total, item) => {
            if (Number.isNaN(item.quantity) || Number.isNaN(item.unitPrice)) {
                return total
            }

            return item.quantity * item.unitPrice + total
        }, 0) ?? 0
    )
}

function getShoppingDayItemCount(items: ShoppingDayItem[] | undefined): number {
    return items?.filter((item) => item.unitPrice && item.quantity).length ?? 0
}

const hasPrevOrNext = computed(
    () =>
        Boolean(props.shoppingDaysPaginator.prev_page_url) ||
        Boolean(props.shoppingDaysPaginator.next_page_url)
)

const prevLinkProps = computed((): InertiaLinkProps & ExtraLinkProps => ({
    as: Boolean(props.shoppingDaysPaginator.prev_page_url) ? "a" : "button",
    href: props.shoppingDaysPaginator.prev_page_url ?? "#",
    disabled: !Boolean(props.shoppingDaysPaginator.prev_page_url),
}))

const nextLinkProps = computed((): InertiaLinkProps & ExtraLinkProps => ({
    as: Boolean(props.shoppingDaysPaginator.next_page_url) ? "a" : "button",
    href: props.shoppingDaysPaginator.next_page_url ?? "#",
    disabled: !Boolean(props.shoppingDaysPaginator.next_page_url),
}))
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Días de compras de ${owner.name}`" />

        <div class="h-full px-3 py-2">
            <table
                class="w-full table-fixed border-collapse border border-primary"
            >
                <thead>
                    <tr>
                        <th class="border border-primary px-1 py-1">Fecha</th>
                        <th class="border border-primary w-1/4 px-1 py-1">
                            Total
                        </th>
                        <th
                            class="border border-primary w-1/4 px-1 py-1 truncate"
                        >
                            Productos
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        class="odd:bg-secondary"
                        v-for="shoppingDay in shoppingDaysPaginator.data"
                        :key="shoppingDay.id"
                    >
                        <td class="px-2 py-1 border border-primary">
                            <Link
                                class="hover:underline"
                                :href="
                                    route('shopping-days.show', {
                                        shoppingDay,
                                    })
                                "
                            >
                                {{ formatDate(shoppingDay.date, "long") }}
                            </Link>
                        </td>
                        <td class="px-2 py-1 border border-primary text-center">
                            {{
                                formatCurrency(
                                    getShoppingDayTotal(shoppingDay.items)
                                )
                            }}
                        </td>
                        <td class="px-2 py-1 border border-primary text-center">
                            {{ getShoppingDayItemCount(shoppingDay.items) }}
                        </td>
                    </tr>
                </tbody>

                <tfoot v-if="hasPrevOrNext">
                    <tr>
                        <td class="px-2 py-1" colspan="3">
                            <div class="flex justify-between">
                                <Link
                                    v-bind="prevLinkProps"
                                    class="disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    &lt; Anterior
                                </Link>

                                <Link
                                    v-bind="nextLinkProps"
                                    class="disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    Siguiente &gt;
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, watchEffect } from "vue"
import { useDebounceFn } from "@vueuse/core"
import { useForm } from "@inertiajs/vue3"
import { useEcho } from "@laravel/echo-vue"

import type { ShoppingDayItem } from "@/types/ShoppingDayItem"
import ShoppingListItem from "./ShoppingListItem.vue"
import { ShoppingDay } from "@/types/ShoppingDay"

const model = defineModel<(ShoppingDayItem & { checked: boolean })[]>()
const total = defineModel<number>("total")

const props = withDefaults(
    defineProps<{
        shoppingDay: ShoppingDay
        hideChecked?: boolean
    }>(),
    { hideChecked: false }
)

const computedItems = computed(
    () =>
        (props.hideChecked
            ? model.value?.filter((item) => !item.checked)
            : model.value) ?? []
)

watchEffect(() => {
    total.value =
        model.value?.reduce((total, item) => {
            if (Number.isNaN(item.unitPrice) || Number.isNaN(item.quantity)) {
                return total
            }

            return total + item.unitPrice * item.quantity
        }, 0) ?? 0
})

const updateForm = useForm({})

const handleUpdateUnitPrice = useDebounceFn(function handleUpdateUnitPrice(
    itemId: string,
    unitPrice: number
) {
    updateForm
        .transform(() => ({ unitPrice }))
        .patch(
            route("shopping-days.items.update", {
                shoppingDay: props.shoppingDay.id,
                shoppingDayItem: itemId,
            }),
            { async: true, preserveScroll: true }
        )
}, 500)

const handleUpdateQuantity = useDebounceFn(function handleUpdateQuantity(
    itemId: string,
    quantity: number
) {
    updateForm
        .transform(() => ({ quantity }))
        .patch(
            route("shopping-days.items.update", {
                shoppingDay: props.shoppingDay.id,
                shoppingDayItem: itemId,
            }),
            { async: true, preserveScroll: true }
        )
}, 500)

const echo = useEcho<{ shoppingDayItem: ShoppingDayItem }>(
    `shopping-day-updated.${props.shoppingDay.id}`,
    "Shopping\\Events\\ShoppingDayItemUpdated",
    ({ shoppingDayItem }) => {
        const index =
            model.value?.findIndex(({ id }) => shoppingDayItem.id === id) ?? -1
        if (model.value && index >= 0) {
            model.value[index].quantity = shoppingDayItem.quantity
            model.value[index].unitPrice = shoppingDayItem.unitPrice
        }
    }
)

onMounted(() => {
    echo.listen()
})

onUnmounted(() => {
    echo.leave()
})
</script>

<template>
    <div class="flex flex-col flex-1 relative">
        <ul
            role="list"
            class="max-w-full overflow-y-scroll space-y-4 mt-4 pt-1 p-1 overscroll-auto flex-1"
        >
            <li v-for="item in computedItems" :key="item.id">
                <ShoppingListItem
                    v-model:unitPrice="item.unitPrice"
                    v-model:quantity="item.quantity"
                    v-model:checked="item.checked"
                    :lastPrice="item.product.lastPrice"
                    @update:unitPrice="handleUpdateUnitPrice(item.id, $event)"
                    @update:quantity="handleUpdateQuantity(item.id, $event)"
                >
                    {{ item.product.name }}
                </ShoppingListItem>
            </li>
        </ul>
    </div>
</template>

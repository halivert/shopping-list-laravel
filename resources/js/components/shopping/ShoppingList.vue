<script setup lang="ts">
import { computed, onMounted, onUnmounted, watchEffect } from "vue"
import { useDebounceFn } from "@vueuse/core"
import { useHttp } from "@inertiajs/vue3"
import { echo, useEcho } from "@laravel/echo-vue"

import type { ShoppingDayItem } from "@/types/ShoppingDayItem"
import ShoppingListItem from "./ShoppingListItem.vue"
import { ShoppingDay } from "@/types/ShoppingDay"

const model = defineModel<(ShoppingDayItem & { checked: boolean })[]>()
const total = defineModel<number>("total")
const calculatedTotal = defineModel<number>("calculatedTotal")

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
    let currentTotal = 0
    let prevTotal = 0

    for (const item of model.value ?? []) {
        if (Number.isNaN(item.unitPrice) || Number.isNaN(item.quantity)) {
            continue
        }

        const hasPrice = Boolean(item.unitPrice)

        currentTotal += item.unitPrice * item.quantity

        if (hasPrice) {
            prevTotal += (item.product.lastPrice ?? item.unitPrice) * item.quantity
        }
    }

    total.value = currentTotal
    calculatedTotal.value = prevTotal
})

// ── Saving item changes ───────────────────────────────────────────────────────
// Changes are accumulated per item and flushed in a single debounced PATCH, so
// setting price and quantity at once (e.g. from the unit price calculator)
// produces one request with both fields.
//
// useHttp is used instead of router visits on purpose: Inertia cancels every
// in-flight async visit when a new visit targets a URL other than the current
// page, which made concurrent item updates cancel each other.

type ItemChanges = { unitPrice?: number; quantity?: number }

const http = useHttp<ItemChanges>({})
const pendingChanges = new Map<string, ItemChanges>()
const itemSavers = new Map<string, () => void>()

function saveItem(itemId: string) {
    const changes = pendingChanges.get(itemId)
    pendingChanges.delete(itemId)

    if (!changes) return

    const socketId = echo().socketId()

    http.transform(() => changes)
        .patch(
            route("shopping-days.items.update", {
                shoppingDay: props.shoppingDay.id,
                shoppingDayItem: itemId,
            }),
            { headers: socketId ? { "X-Socket-ID": socketId } : {} }
        )
        .catch(() => {
            // Keep the unsaved changes (unless newer ones arrived meanwhile)
            // so the next edit of this item retries them.
            pendingChanges.set(itemId, {
                ...changes,
                ...pendingChanges.get(itemId),
            })
        })
}

function queueItemUpdate(itemId: string, changes: ItemChanges) {
    pendingChanges.set(itemId, { ...pendingChanges.get(itemId), ...changes })

    if (!itemSavers.has(itemId)) {
        itemSavers.set(
            itemId,
            useDebounceFn(() => saveItem(itemId), 500)
        )
    }

    itemSavers.get(itemId)!()
}

function handleUpdateUnitPrice(itemId: string, unitPrice: number) {
    queueItemUpdate(itemId, { unitPrice })
}

function handleUpdateQuantity(itemId: string, quantity: number) {
    queueItemUpdate(itemId, { quantity })
}

const echoUpdated = useEcho<{ shoppingDayItem: ShoppingDayItem }>(
    `shopping-day-updated.${props.shoppingDay.id}`,
    "Shopping\\Events\\ShoppingDayItemUpdated",
    ({ shoppingDayItem }) => {
        const index =
            model.value?.findIndex(({ id }) => shoppingDayItem.id === id) ?? -1
        if (model.value && index >= 0) {
            if (model.value[index].unitPrice !== shoppingDayItem.unitPrice) {
                model.value[index].checked = Boolean(shoppingDayItem.unitPrice)
            }

            model.value[index].quantity = shoppingDayItem.quantity
            model.value[index].unitPrice = shoppingDayItem.unitPrice
        }
    }
)

const echoCreated = useEcho<{ shoppingDayItem: ShoppingDayItem }>(
    `shopping-day-updated.${props.shoppingDay.id}`,
    "Shopping\\Events\\ShoppingDayItemCreated",
    ({ shoppingDayItem }) => {
        const alreadyExists = model.value?.some(({ id }) => id === shoppingDayItem.id)
        if (!alreadyExists) {
            model.value?.push({
                ...shoppingDayItem,
                quantity: shoppingDayItem.quantity ?? 1,
                unitPrice: shoppingDayItem.unitPrice ?? 0,
                checked: Boolean(shoppingDayItem.unitPrice),
            })
        }
    }
)

onMounted(() => {
    echoUpdated.listen()
    echoCreated.listen()
})

onUnmounted(() => {
    // Don't lose edits still waiting on their debounce when leaving the page
    for (const itemId of [...pendingChanges.keys()]) {
        saveItem(itemId)
    }

    echoUpdated.leave()
    echoCreated.leave()
})
</script>

<template>
    <div class="flex flex-col flex-1 relative">
        <ul
            role="list"
            class="max-w-full divide-y mt-2 p-1 flex-1 [&>li]:py-3"
        >
            <li v-for="item in computedItems" :key="item.id">
                <ShoppingListItem
                    v-model:unitPrice="item.unitPrice"
                    v-model:quantity="item.quantity"
                    v-model:checked="item.checked"
                    :lastPrice="item.product.lastPrice"
                    :unit="item.product.unit"
                    @update:unitPrice="handleUpdateUnitPrice(item.id, $event)"
                    @update:quantity="handleUpdateQuantity(item.id, $event)"
                >
                    {{ item.product.name }}
                </ShoppingListItem>
            </li>
        </ul>
    </div>
</template>

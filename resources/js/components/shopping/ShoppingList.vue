<script setup lang="ts">
import { computed, watch } from "vue"

import type { ShoppingDayItem } from "@/types/ShoppingDayItem"
import ShoppingListItem from "./ShoppingListItem.vue"

const model = defineModel<(ShoppingDayItem & { checked: boolean })[]>()

const props = withDefaults(
    defineProps<{
        hideChecked?: boolean
    }>(),
    { hideChecked: false }
)

const emit = defineEmits<{
    updateTotal: [total: number, first: boolean]
}>()

const computedItems = computed(
    () =>
        (props.hideChecked
            ? model.value?.filter((item) => !item.checked)
            : model.value) ?? []
)

const total = computed(
    () =>
        model.value?.reduce((total, item) => {
            if (Number.isNaN(item.unitPrice) || Number.isNaN(item.quantity)) {
                return total
            }

            return total + item.unitPrice * item.quantity
        }, 0) ?? 0
)

watch(
    total,
    (total, lastTotal) => emit("updateTotal", total, lastTotal == undefined),
    { immediate: true }
)
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
                >
                    {{ item.product.name }}
                </ShoppingListItem>
            </li>
        </ul>
    </div>
</template>

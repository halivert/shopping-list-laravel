<script setup lang="ts">
import { computed, ref, watch } from "vue"

import ShoppingListItem from "./ShoppingListItem.vue"
import AppButton from "@/components/ui/button/Button.vue"
import { ShoppingDayItem } from "@/types/ShoppingDayItem"
import { ShoppingDay } from "@/types/ShoppingDay";
import { Link } from "@inertiajs/vue3";

const props = withDefaults(
    defineProps<{
        shoppingDay: ShoppingDay
        hideChecked?: boolean
    }>(),
    { hideChecked: false }
)

const emit = defineEmits<{
    updateTotal: [total: number]
    update: [items: ShoppingDayItem[]]
    save: [items: ShoppingDayItem[]]
}>()

const originalItems = ref(
    props.shoppingDay.items?.map((item) => ({
        ...item,
        quantity: item.quantity ?? 1,
        unitPrice: item.unitPrice ?? 0,
        checked: false,
    })) ?? []
)

const computedItems = computed(
    () =>
        (props.hideChecked
            ? originalItems.value?.filter((item) => !item.checked)
            : originalItems.value) ?? []
)

const total = computed(() =>
    originalItems.value.reduce((total, item) => {
        if (Number.isNaN(item.unitPrice) || Number.isNaN(item.quantity)) {
            return total
        }

        return total + item.unitPrice * item.quantity
    }, 0)
)

watch(
    total,
    (total) => {
        emit("updateTotal", total)
        emit("update", originalItems.value)
    },
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

        <div class="flex gap-3 bottom-2 left-1 right-1 sticky mt-2">
            <AppButton
                :as="Link"
                :href="route('shopping-days.edit', { shoppingDay })"
                class="flex-shrink flex-1"
                variant="secondary"
            >
                Editar
            </AppButton>
            <AppButton class="flex-1" @click="emit('save', originalItems)"
                >Guardar</AppButton
            >
        </div>
    </div>
</template>

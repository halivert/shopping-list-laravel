<script setup lang="ts">
import { computed, ref, watch } from "vue"
import { useDragAndDrop } from "@formkit/drag-and-drop/vue"
import { useDebounceFn } from "@vueuse/core"

interface Item {
    id: string
    name: string
}

const props = defineProps<{
    items: Item[]
}>()

const emit = defineEmits<{
    update: [items: Item[]]
    deleteItem: [itemId: string]
}>()

const [parent, items] = useDragAndDrop(props.items)
const selectedItemId = ref<string | undefined>()

const emitUpdate = useDebounceFn(function emitUpdate(items: Item[]) {
    emit("update", items)
}, 500)

watch(items, emitUpdate)
watch(
    () => props.items,
    (newItems) => (items.value = newItems)
)

function handleItemClick(id: string) {
    selectedItemId.value = selectedItemId.value === id ? undefined : id
}

function handleDelete(id: string) {
    emit("deleteItem", id)
}
</script>

<template>
    <ul ref="parent" class="space-y-1">
        <li
            v-for="item in items"
            :key="item.id"
            @click="() => handleItemClick(item.id)"
        >
            <span class="flex gap-1 items-center min-h-6">
                <button
                    v-if="selectedItemId === item.id"
                    @click.stop="() => handleDelete(item.id)"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-6 text-red-500"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25m-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z"
                        />
                    </svg>
                </button>
                <span>
                    {{ item.name }}
                </span>
            </span>
        </li>
    </ul>
</template>

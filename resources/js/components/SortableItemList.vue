<script setup lang="ts">
import { watch } from "vue"
import { useDragAndDrop } from "@formkit/drag-and-drop/vue"

export interface Item {
    id: string
    name: string
}

interface Props {
    group?: string
    sortable?: boolean
}

const model = defineModel<Item[]>()

const props = withDefaults(defineProps<Props>(), {
    sortable: true,
})

const [parent, items] = useDragAndDrop(model.value ?? [], {
    sortable: props.sortable,
    group: props.group,
    draggingClass: "font-bold text-xl",
    dragPlaceholderClass: "bg-secondary",
})

watch(items, (newItems) => (model.value = newItems))
watch(model, (newItems) => newItems && (items.value = newItems))
</script>

<template>
    <ul ref="parent" class="space-y-1">
        <li
            class="px-2 py-1 rounded-sm hover:bg-secondary cursor-move"
            v-for="item in items"
            :key="item.id"
        >
            {{ item.name }}
        </li>
    </ul>
</template>

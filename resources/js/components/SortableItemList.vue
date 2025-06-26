<script setup lang="ts">
import { ref, unref, watch } from "vue"
import { useDragAndDrop } from "@formkit/drag-and-drop/vue"
import { useDebounceFn } from "@vueuse/core"

interface Item {
    id: string
    name: string
}

const props = withDefaults(
    defineProps<{
        items: Item[]
        group?: string
        sortable?: boolean
    }>(),
    {
        sortable: true,
    }
)

const emit = defineEmits<{
    update: [items: Item[]]
    delete: [item: Item, items: Item[]]
}>()

const action = ref<"update" | "delete" | undefined>()
const selectedItem = ref<Item | undefined>()

const emitUpdate = useDebounceFn(function emitUpdate(sortedItems: Item[]) {
    emit("update", sortedItems)
}, 500)

const emitDelete = useDebounceFn(function handleDelete(
    item: Item,
    currentItems: Item[]
) {
    emit("delete", item, currentItems)
}, 500)

const [parent, items] = useDragAndDrop(props.items, {
    sortable: props.sortable,
    group: props.group,
    onDragend: (event) => {
        const items = event.values as Item[]

        if (action.value === "update") return emitUpdate(items)

        if (action.value === "delete") {
            if (selectedItem.value) emitDelete(selectedItem.value, items)
            selectedItem.value = undefined
        }
    },
    onSort: () => {
        action.value = "update"
    },
    onTransfer: (event) => {
        const item = event.draggedNodes.at(0)?.data.value as Item | undefined

        if (item && event.sourceParent.el === parent.value) {
            action.value = "delete"
            selectedItem.value = item
        }
    },
})

watch(
    () => props.items,
    (newItems) => (items.value = newItems)
)
</script>

<template>
    <ul ref="parent" class="space-y-1">
        <li v-for="item in items" :key="item.id">
            {{ item.name }}
        </li>
    </ul>
</template>

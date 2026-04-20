<script setup lang="ts">
import type { Product } from "@/types/Product"
import Checkbox from "@/components/ui/checkbox/Checkbox.vue"
import { formatCurrency } from "@/composables/formatHelpers"

interface Props {
    product: Product
    checked: boolean
    class?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{ (e: "update:checked", value: boolean): void }>()
</script>

<template>
    <label
        class="flex items-center gap-2.5 px-2 py-2 rounded-md transition-colors cursor-pointer select-none"
        :class="checked ? 'bg-primary/10' : 'hover:bg-muted'"
    >
        <Checkbox
            :checked="checked"
            @update:checked="emit('update:checked', $event as boolean)"
        />
        <span
            data-testid="product-name"
            class="flex-1 truncate text-sm"
            :class="[
                checked ? 'font-medium' : '',
                checked && !product.lastPrice ? 'underline' : '',
            ]"
        >
            {{ product.name }}
        </span>
        <span
            v-if="product.lastPrice"
            data-testid="last-price"
            class="text-xs text-muted-foreground tabular-nums shrink-0"
        >
            {{ formatCurrency(product.lastPrice) }}
        </span>
    </label>
</template>

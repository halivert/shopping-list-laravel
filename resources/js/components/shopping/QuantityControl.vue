<script setup lang="ts">
const quantity = defineModel<number>({ default: 1 })

function decrement() {
    if (quantity.value <= 1) return
    quantity.value = Math.ceil(quantity.value - 1)
}

function increment() {
    quantity.value = Math.floor(quantity.value + 1)
}
</script>

<template>
    <div class="flex items-center gap-1">
        <button
            type="button"
            data-testid="decrement"
            class="size-6 rounded bg-secondary flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed shrink-0"
            :disabled="quantity <= 1"
            @click="decrement"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="size-3"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
            </svg>
        </button>

        <span class="w-6 text-center text-sm tabular-nums select-none">
            {{ quantity }}
        </span>

        <button
            type="button"
            data-testid="increment"
            class="size-6 rounded bg-primary text-primary-foreground flex items-center justify-center shrink-0"
            @click="increment"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="size-3"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
            </svg>
        </button>
    </div>
</template>

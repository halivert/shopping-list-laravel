<script setup lang="ts">
import AppInput from "@/components/ui/input/Input.vue"
import AppButton from "@/components/ui/button/Button.vue"
import AppLabel from "@/components/ui/label/Label.vue"

const model = defineModel<string>()

withDefaults(
    defineProps<{
        productsSuggestions: { id: string; name: string }[]
        loading?: boolean
    }>(),
    {
        loading: false,
    }
)
</script>

<template>
    <AppLabel for="name">Nuevo producto</AppLabel>
    <div class="inline-flex w-full h-10 gap-2">
        <AppInput
            id="name"
            class="rounded-e-none h-[unset]"
            v-model="model"
            placeholder="Champú"
            autocomplete="off"
            list="products-list"
            required
        />

        <datalist id="products-list">
            <option v-for="product in productsSuggestions" :key="product.id">
                {{ product.name }}
            </option>
        </datalist>

        <AppButton
            class="rounded-s-none aspect-square h-[unset] w-auto p-0"
            :disabled="loading"
        >
            <!-- Loading spinner -->
            <div
                v-if="loading"
                class="border-2 border-transparent border-b-current border-l-current size-5 block aspect-square rounded-3xl animate-spin"
            />

            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="size-5"
            >
                <path
                    fill-rule="evenodd"
                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                    clip-rule="evenodd"
                />
            </svg>
        </AppButton>
    </div>
</template>

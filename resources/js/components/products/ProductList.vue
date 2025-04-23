<script setup lang="ts">
import { Product } from "@/types/Product"

defineProps<{
    products: Product[]
}>()
</script>

<template>
    <section>
        <ul class="list-disc list-inside">
            <li v-for="product in products">{{ product.name }}</li>
        </ul>
    </section>
</template>

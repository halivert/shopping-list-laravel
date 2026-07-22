<script setup lang="ts">
defineProps<{
    id: string
    names: string[]
}>()
</script>

<template>
    <datalist :id="id">
        <option v-for="name in names" :key="name">{{ name }}</option>
    </datalist>
</template>

<script setup lang="ts">
import { HTMLAttributes } from 'vue';

import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>()

</script>

<template>
    <li :class="cn('group-data-[collapsible=icon]:hidden', props.class)">
        <slot />
    </li>
</template>

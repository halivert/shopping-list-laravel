<script setup lang="ts">
import { Pencil, PencilOff, Save } from "lucide-vue-next"

import AppButton from "@/components/ui/button/Button.vue"
import AppInput from "@/components/ui/input/Input.vue"

const model = defineModel<string>()
const isEditing = defineModel<boolean>("isEditing")
</script>

<template>
    <div>
        <AppInput v-if="isEditing" type="date" v-model="model"></AppInput>
        <slot v-else />
        <AppButton
            v-if="isEditing"
            type="submit"
            class="aspect-square"
            variant="default"
        >
            <Save :size="16" />
        </AppButton>
        <AppButton
            type="button"
            @click="isEditing = !isEditing"
            class="aspect-square"
            variant="ghost"
        >
            <component :is="isEditing ? PencilOff : Pencil" :size="16" />
        </AppButton>
    </div>
</template>

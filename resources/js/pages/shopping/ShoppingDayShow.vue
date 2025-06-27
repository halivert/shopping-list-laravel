<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, Link, useForm } from "@inertiajs/vue3"
import { Save } from "lucide-vue-next"
import { useDebounceFn } from "@vueuse/core"

import type { BreadcrumbItem } from "@/types"
import type { ShoppingDay } from "@/types/ShoppingDay"
import AppLayout from "@/layouts/AppLayout.vue"
import AppButton from "@/components/ui/button/Button.vue"
import ShoppingList from "@/components/shopping/ShoppingList.vue"
import { formatDate, getCurrency } from "@/composables/formatHelpers"

const props = defineProps<{
    shoppingDay: ShoppingDay
}>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: "Dashboard",
        href: "/",
    },
    {
        title: `Día de compras: ${formatDate(props.shoppingDay.date)}`,
        href: "",
    },
])

const total = ref(0)
const hideChecked = ref(false)

const items = ref(
    props.shoppingDay.items?.map((item) => ({
        ...item,
        quantity: item.quantity ?? 1,
        unitPrice: item.unitPrice ?? 0,
        checked: false,
    }))
)

const updateForm = useForm({})

function handleSave() {
    if (updateForm.recentlySuccessful || updateForm.processing) return

    updateForm
        .transform(() => ({
            touch: true,
            items: items.value?.map((item) => ({
                id: item.id,
                unitPrice: item.unitPrice,
                quantity: item.quantity,
            })),
        }))
        .patch(
            route("shopping-days.update", {
                shoppingDay: props.shoppingDay.id,
            }),
            { preserveScroll: true }
        )
}

const autoSaveItems = useDebounceFn(function autoSaveItems() {
    handleSave()
}, 5 * 1000)

function handleUpdateTotal(newTotal: number, first: boolean) {
    total.value = newTotal

    if (!first) autoSaveItems()
}
</script>

<template>
    <Head :title="`Día de compras: ${formatDate(props.shoppingDay.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-1 flex gap-3 justify-between flex-col h-full">
            <div>
                <h2 class="text-lg font-semibold flex justify-between">
                    Lista de compras
                </h2>
                <small v-if="shoppingDay.updatedAt">
                    (Actualizado: {{ formatDate(shoppingDay.updatedAt) }})
                </small>
            </div>
            <header
                class="text-xl sticky top-0 pt-2 pb-1 bg-background flex justify-between z-10 border-b-2 border-white-c -mx-2 px-2 items-center"
            >
                <span>
                    Total: <span class="">{{ getCurrency(total) }}</span>
                </span>
                <label class="text-xs inline-flex items-center gap-1">
                    Ocultar comprados
                    <input type="checkbox" v-model="hideChecked" />
                </label>
            </header>
            <ShoppingList
                v-model="items"
                :hideChecked="hideChecked"
                @updateTotal="handleUpdateTotal"
            />

            <div
                class="flex gap-2 bottom-2 left-0 right-0 sticky mt-2 bg-background"
            >
                <AppButton
                    :as="Link"
                    :href="route('shopping-days.edit', { shoppingDay })"
                    class="flex-1"
                    variant="secondary"
                >
                    Editar
                </AppButton>
                <AppButton
                    :disabled="updateForm.recentlySuccessful"
                    class="flex-[2]"
                    @click="handleSave"
                >
                    <template v-if="updateForm.recentlySuccessful">
                        <Save :size="16" />
                        Guardado...
                    </template>
                    <template v-else> Guardar </template>
                </AppButton>
            </div>
        </div>
    </AppLayout>
</template>

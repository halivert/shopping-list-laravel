<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, useForm } from "@inertiajs/vue3"
import { RotateCcw, Search } from "lucide-vue-next"

import type { BreadcrumbItem, User } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import AppInput from "@/components/ui/input/Input.vue"
import AppButton from "@/components/ui/button/Button.vue"

interface Props {
    owner: User
    products: Product[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Inicio", href: "/" },
    { title: "Productos", href: route("users.products.index", { owner: props.owner.id }) },
    { title: "Eliminados", href: "" },
]

// ── Search ────────────────────────────────────────────────────────────────────

const query = ref("")

const filteredProducts = computed(() => {
    const q = query.value
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .toLowerCase()
        .trim()
    if (!q) return props.products
    return props.products.filter((p) =>
        p.name
            .normalize("NFD")
            .replace(/\p{Diacritic}/gu, "")
            .toLowerCase()
            .includes(q)
    )
})

// ── Restore ───────────────────────────────────────────────────────────────────

const restoringId = ref<string | null>(null)
const restoreForm = useForm({})

function handleRestore(product: Product) {
    restoringId.value = product.id
    restoreForm.post(route("products.restore", product.id), {
        preserveScroll: true,
        onFinish: () => {
            restoringId.value = null
        },
    })
}
</script>

<template>
    <Head title="Productos eliminados" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between px-3 pt-3 pb-1 gap-2">
                <h1 class="text-lg font-semibold">Productos eliminados</h1>
            </div>

            <!-- Search -->
            <div class="px-3 pb-2 relative">
                <Search
                    class="absolute left-5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"
                />
                <AppInput
                    v-model="query"
                    class="pl-8"
                    placeholder="Buscar producto…"
                    autocomplete="off"
                />
            </div>

            <!-- Trashed product list -->
            <ul class="flex-1 overflow-y-auto divide-y px-0">
                <li v-for="product in filteredProducts" :key="product.id">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <span class="flex-1 text-sm text-muted-foreground">{{
                            product.name
                        }}</span>
                        <AppButton
                            variant="outline"
                            size="sm"
                            :disabled="restoringId === product.id"
                            @click="handleRestore(product)"
                        >
                            <div
                                v-if="restoringId === product.id"
                                class="border-2 border-transparent border-b-current border-l-current size-4 block aspect-square rounded-3xl animate-spin"
                            />
                            <RotateCcw v-else class="size-4" />
                            Restaurar
                        </AppButton>
                    </div>
                </li>

                <li
                    v-if="filteredProducts.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    <template v-if="query">
                        Sin resultados para "{{ query }}"
                    </template>
                    <template v-else> No hay productos eliminados. </template>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>

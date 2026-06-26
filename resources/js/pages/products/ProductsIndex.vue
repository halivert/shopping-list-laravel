<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, Link, useForm } from "@inertiajs/vue3"
import { ChevronRight, ListOrdered, Search } from "lucide-vue-next"

import type { BreadcrumbItem, User } from "@/types"
import type { Product } from "@/types/Product"
import AppLayout from "@/layouts/AppLayout.vue"
import AppInput from "@/components/ui/input/Input.vue"
import AppButton from "@/components/ui/button/Button.vue"
import { formatCurrency } from "@/composables/formatHelpers"

interface Props {
    owner: User
    products: Product[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Inicio", href: "/" },
    { title: "Productos", href: "" },
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

// ── Add product ───────────────────────────────────────────────────────────────

const addForm = useForm({ name: "" })

function handleAdd() {
    addForm.post(route("products.store"), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    })
}
</script>

<template>
    <Head title="Productos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-3 pt-3 pb-1 gap-2"
            >
                <h1 class="text-lg font-semibold">Productos</h1>
                <Link
                    :href="route('users.products.sort', { owner: owner.id })"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ListOrdered class="size-4" />
                    Ordenar
                </Link>
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

            <!-- Product list -->
            <ul class="flex-1 overflow-y-auto divide-y px-0">
                <li v-for="product in filteredProducts" :key="product.id">
                    <Link
                        :href="route('products.show', product.id)"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-accent/50 transition-colors"
                    >
                        <span class="flex-1 text-sm">{{ product.name }}</span>
                        <span
                            v-if="product.lastPrice"
                            class="text-sm text-muted-foreground tabular-nums"
                        >
                            {{ formatCurrency(product.lastPrice) }}
                        </span>
                        <span
                            v-else
                            class="text-sm text-muted-foreground/50"
                            >—</span
                        >
                        <ChevronRight class="size-4 text-muted-foreground/50 shrink-0" />
                    </Link>
                </li>

                <li
                    v-if="filteredProducts.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    <template v-if="query">
                        Sin resultados para "{{ query }}"
                    </template>
                    <template v-else> Aún no hay productos. </template>
                </li>
            </ul>

            <!-- Add product -->
            <div class="px-3 py-2 border-t">
                <form
                    class="inline-flex w-full h-10 gap-2"
                    @submit.prevent="handleAdd"
                >
                    <AppInput
                        v-model="addForm.name"
                        class="flex-1 rounded-e-none h-[unset]"
                        placeholder="Nombre del producto"
                        required
                    />
                    <AppButton
                        class="rounded-s-none aspect-square h-[unset] w-auto p-0"
                        :disabled="addForm.processing"
                    >
                        <div
                            v-if="addForm.processing"
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
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from "vue"
import { Head, useForm } from "@inertiajs/vue3"
import { SwitchRoot, SwitchThumb } from "radix-vue"
import { useDebounceFn } from "@vueuse/core"

import type { BreadcrumbItem, User } from "@/types"
import type { Product } from "@/types/Product"
import HeadingSmall from "@/components/HeadingSmall.vue"
import SortableItemList from "@/components/SortableItemList.vue"
import AppLayout from "@/layouts/AppLayout.vue"

interface Props {
    owner: User
    products: Product[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Inicio", href: "/" },
    { title: "Ordenar productos", href: "" },
]

const props = defineProps<Props>()

const updateProductsForm = useForm<{
    products: Pick<Product, "id" | "name">[]
    list: boolean
}>({
    products: props.products.toSorted(
        (a, b) => (a.searchIndex ?? 0) - (b.searchIndex ?? 0)
    ),
    list: false,
})

const handleSort = useDebounceFn(function handleSort() {
    if (updateProductsForm.processing) return

    updateProductsForm
        .transform((data) => ({
            products: data.products.map(({ id }) => id),
            list: data.list ? "shopping_index" : "search_index",
        }))
        .post(route("users.products.store", { owner: props.owner }), {
            async: true,
            only: [],
            preserveScroll: true,
        })
}, 1.5 * 1000)

function setList() {
    const sortFn: (a: Product, b: Product) => number = updateProductsForm.list
        ? (a, b) => (a.shoppingIndex ?? 0) - (b.shoppingIndex ?? 0)
        : (a, b) => (a.searchIndex ?? 0) - (b.searchIndex ?? 0)

    updateProductsForm.products = props.products.toSorted(sortFn)
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Ordenar productos" />

        <section class="flex flex-col h-full px-3 py-2 gap-3">
            <HeadingSmall
                title="Ordenar productos"
                description="Ordena los productos para encontrarlos más fácilmente"
            />

            <div
                class="flex gap-2 items-center justify-center self-stretch px-3 py-2"
            >
                <label
                    class="select-none flex-1 text-right"
                    for="list"
                    :class="{ 'opacity-0': updateProductsForm.list }"
                >
                    Orden de búsqueda
                </label>
                <SwitchRoot
                    id="list"
                    v-model:checked="updateProductsForm.list"
                    @update:checked="setList"
                    class="w-12 h-7 px-0.5 focus-within:outline focus-within:outline-secondary flex bg-primary/50 shadow-sm rounded-full relative cursor-default"
                >
                    <SwitchThumb
                        class="block size-6 my-auto bg-white shadow-sm rounded-full transition-transform duration-100 will-change-transform data-[state=checked]:translate-x-5"
                    />
                </SwitchRoot>
                <label
                    class="select-none flex-1 text-left"
                    for="list"
                    :class="{ 'opacity-0': !updateProductsForm.list }"
                >
                    Orden de compras
                </label>
            </div>

            <form
                class="w-2/3"
                :class="{ 'text-right self-end': updateProductsForm.list }"
            >
                <SortableItemList
                    v-model="updateProductsForm.products"
                    @update:modelValue="handleSort"
                />
            </form>
        </section>
    </AppLayout>
</template>

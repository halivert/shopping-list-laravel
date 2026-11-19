<script setup lang="ts">
import { computed, ref } from "vue"
import { Head, Link, router } from "@inertiajs/vue3"
import { Pencil, Plus, Trash2 } from "lucide-vue-next"

import type { BreadcrumbItem, User } from "@/types"
import type { Product } from "@/types/Product"
import type { StoreWithPositions } from "@/types/Store"
import ConfirmDialog from "@/components/ConfirmDialog.vue"
import HeadingSmall from "@/components/HeadingSmall.vue"
import SortableItemList, { type Item } from "@/components/SortableItemList.vue"
import StoreNameDialog from "@/components/stores/StoreNameDialog.vue"
import AppButton from "@/components/ui/button/Button.vue"
import { useQueuedUpdates } from "@/composables/useQueuedUpdates"
import AppLayout from "@/layouts/AppLayout.vue"

interface Props {
    owner: User
    products: Product[]
    stores: StoreWithPositions[]
}

const props = defineProps<Props>()

const breadcrumbs = computed((): BreadcrumbItem[] => [
    { title: "Inicio", href: "/" },
    {
        title: "Productos",
        href: route("users.products.index", { owner: props.owner.id }, false),
    },
    { title: "Ordenar", href: "#" },
])

// ── Lists ─────────────────────────────────────────────────────────────────────
// "search" is the search order; any other key is a store id (its layout).

const SEARCH = "search"

const selectedList = ref<string>(SEARCH)

const selectedStore = computed(() =>
    props.stores.find((store) => store.id === selectedList.value)
)

/** Order saved in this session, per list, so switching tabs keeps edits. */
const editedOrders = new Map<string, string[]>()

function savedOrder(list: string): Item[] {
    if (list === SEARCH) {
        return props.products.toSorted(
            (a, b) => (a.searchIndex ?? 0) - (b.searchIndex ?? 0)
        )
    }

    const positions =
        props.stores.find((store) => store.id === list)?.positions ?? {}

    // Products without position in this store go to the end, by name
    return props.products.toSorted((a, b) => {
        const posA = positions[a.id] ?? Infinity
        const posB = positions[b.id] ?? Infinity

        if (posA !== posB) return posA - posB

        return a.name.localeCompare(b.name, "es")
    })
}

function orderFor(list: string): Item[] {
    const edited = editedOrders.get(list)
    if (!edited) return savedOrder(list)

    const byId = new Map(props.products.map((p) => [p.id, p]))
    const ordered = edited.flatMap((id) => byId.get(id) ?? [])

    // Products created after the edit go to the end
    const rest = props.products.filter((p) => !edited.includes(p.id))

    return [...ordered, ...rest]
}

const items = ref<Item[]>(orderFor(SEARCH))

function selectList(list: string) {
    selectedList.value = list
    items.value = orderFor(list)
}

// ── Saving ────────────────────────────────────────────────────────────────────

const { queue: queueOrder } = useQueuedUpdates<{
    products: string[]
    list?: string
}>(
    (list) =>
        list === SEARCH
            ? route("users.products.store", { owner: props.owner.id })
            : route("stores.products.store", { store: list }),
    { method: "post", delay: 500 }
)

function handleSort(next: Item[] | undefined) {
    if (!next) return

    const list = selectedList.value
    const ids = next.map(({ id }) => id)
    const current = orderFor(list).map(({ id }) => id)

    // The list also emits when a tab is selected: only save real changes
    const unchanged =
        ids.length === current.length &&
        ids.every((id, i) => id === current[i])

    if (unchanged) return

    editedOrders.set(list, ids)

    queueOrder(
        list,
        list === SEARCH
            ? { products: ids, list: "search_index" }
            : { products: ids }
    )
}

// ── Stores ────────────────────────────────────────────────────────────────────

function selectNewestStore() {
    const newest = props.stores.at(-1)
    if (newest) selectList(newest.id)
}

const deleting = ref(false)

function deleteStore() {
    const store = selectedStore.value
    if (!store) return

    router.delete(route("stores.destroy", { store: store.id }), {
        preserveScroll: true,
        only: ["stores"],
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
        onSuccess: () => {
            editedOrders.delete(store.id)
            selectList(SEARCH)
        },
    })
}
</script>

<template>
    <Head title="Ordenar productos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="flex flex-col h-full px-3 py-2 gap-3">
            <div class="flex items-center gap-3">
                <HeadingSmall
                    title="Ordenar productos"
                    description="Arrastra los productos para ordenarlos y encontrarlos más fácilmente"
                    class="flex-1"
                />
                <Link
                    :href="
                        route('users.products.index', {
                            owner: owner.id,
                        })
                    "
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Volver
                </Link>
            </div>

            <div
                class="flex gap-2 items-center overflow-x-auto -mx-3 px-3 pb-1"
                role="tablist"
                aria-label="Orden"
            >
                <AppButton
                    type="button"
                    role="tab"
                    class="shrink-0"
                    :aria-selected="selectedList === SEARCH"
                    :variant="selectedList === SEARCH ? 'default' : 'outline'"
                    @click="selectList(SEARCH)"
                >
                    Búsqueda
                </AppButton>

                <AppButton
                    v-for="store in stores"
                    :key="store.id"
                    type="button"
                    role="tab"
                    class="shrink-0"
                    :aria-selected="selectedList === store.id"
                    :variant="selectedList === store.id ? 'default' : 'outline'"
                    @click="selectList(store.id)"
                >
                    {{ store.name }}
                </AppButton>

                <StoreNameDialog :owner="owner" @saved="selectNewestStore">
                    <AppButton
                        type="button"
                        variant="ghost"
                        class="shrink-0"
                        aria-label="Nueva tienda"
                    >
                        <Plus class="size-4" />
                        Tienda
                    </AppButton>
                </StoreNameDialog>
            </div>

            <p class="text-sm text-muted-foreground">
                <template v-if="selectedStore">
                    Orden de compras en {{ selectedStore.name }}. Los días de
                    compras en esta tienda siguen este orden.
                </template>
                <template v-else>
                    Orden en que aparecen los productos al buscarlos.
                </template>
            </p>

            <div v-if="selectedStore" class="flex gap-2 justify-end">
                <StoreNameDialog
                    :key="selectedStore.id"
                    :owner="owner"
                    :store="selectedStore"
                >
                    <AppButton type="button" variant="outline">
                        <Pencil class="size-4" />
                        Renombrar
                    </AppButton>
                </StoreNameDialog>

                <ConfirmDialog
                    :title="`¿Eliminar la tienda ${selectedStore.name}?`"
                    description="Se pierde su orden. Los días de compras de esta tienda se conservan, pero sin tienda."
                    :processing="deleting"
                    @confirm="deleteStore"
                >
                    <AppButton type="button" variant="outline">
                        <Trash2 class="size-4" />
                        Eliminar
                    </AppButton>
                </ConfirmDialog>
            </div>

            <section
                class="w-3/4"
                :class="{ 'text-right self-end': selectedStore }"
            >
                <SortableItemList
                    v-model="items"
                    @update:model-value="handleSort"
                />
            </section>
        </section>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from "vue"
import { router, usePage } from "@inertiajs/vue3"
import type { SharedData } from "@/types"

const page = usePage<SharedData>()

// ── State ─────────────────────────────────────────────────────────────────────

interface ToastItem {
    id: string
    name: string
}

const visible = ref(false)
const toast = ref<ToastItem | null>(null)
const progress = ref(100) // 0–100 width for the progress bar

let dismissTimer: ReturnType<typeof setTimeout> | null = null
let progressTimer: ReturnType<typeof setInterval> | null = null
const DURATION_MS = 6000
const TICK_MS = 50

// Track the last flash id we've already handled so re-renders don't re-trigger
const lastHandledId = ref<string | null>(null)

function clearTimers() {
    if (dismissTimer !== null) {
        clearTimeout(dismissTimer)
        dismissTimer = null
    }
    if (progressTimer !== null) {
        clearInterval(progressTimer)
        progressTimer = null
    }
}

function dismiss() {
    clearTimers()
    visible.value = false
    toast.value = null
}

function show(item: ToastItem) {
    clearTimers()
    toast.value = item
    visible.value = true
    progress.value = 100

    const step = (TICK_MS / DURATION_MS) * 100
    progressTimer = setInterval(() => {
        progress.value = Math.max(0, progress.value - step)
    }, TICK_MS)

    dismissTimer = setTimeout(dismiss, DURATION_MS)
}

function undo() {
    if (!toast.value) return
    const id = toast.value.id
    dismiss()
    router.post(route("products.restore", id), {}, { preserveScroll: true })
}

// ── Watch flash ───────────────────────────────────────────────────────────────

const flashProduct = computed(() => page.props.flash?.deletedProduct ?? null)

watch(
    flashProduct,
    (next) => {
        if (!next) return
        // Only fire once per unique deletion
        if (next.id === lastHandledId.value) return
        lastHandledId.value = next.id
        show({ id: next.id, name: next.name })
    },
    { immediate: true },
)

onUnmounted(clearTimers)
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="visible && toast"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-max max-w-sm"
            role="status"
            aria-live="polite"
        >
            <div
                class="relative overflow-hidden rounded-xl border bg-background shadow-lg"
            >
                <!-- Content row -->
                <div class="flex items-center gap-3 px-4 py-3">
                    <!-- Trash icon -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    >
                        <polyline points="3 6 5 6 21 6" />
                        <path
                            d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"
                        />
                        <path d="M10 11v6M14 11v6" />
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                    </svg>

                    <span class="text-sm">
                        «{{ toast.name }}» eliminado
                    </span>

                    <!-- Undo button -->
                    <button
                        type="button"
                        class="ml-1 text-sm font-medium text-primary underline-offset-2 hover:underline shrink-0"
                        @click="undo"
                    >
                        Deshacer
                    </button>

                    <!-- Close button -->
                    <button
                        type="button"
                        class="ml-1 text-muted-foreground hover:text-foreground shrink-0"
                        aria-label="Cerrar"
                        @click="dismiss"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <!-- Progress bar -->
                <div class="h-0.5 bg-muted">
                    <div
                        class="h-full bg-primary transition-none"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref } from "vue"
import { formatCurrency } from "@/composables/formatHelpers"

interface Point {
    date: string
    price: number
}

const props = defineProps<{
    points: Point[]
}>()

// ── Layout constants ──────────────────────────────────────────────────────────

const W = 300
const H = 120
const PAD_X = 8
const PAD_Y = 16
const INNER_W = W - PAD_X * 2
const INNER_H = H - PAD_Y * 2

// ── Scale helpers ─────────────────────────────────────────────────────────────

const minPrice = computed(() => Math.min(...props.points.map((p) => p.price)))
const maxPrice = computed(() => Math.max(...props.points.map((p) => p.price)))

function scaleX(index: number): number {
    if (props.points.length === 1) return PAD_X + INNER_W / 2
    return PAD_X + (index / (props.points.length - 1)) * INNER_W
}

function scaleY(price: number): number {
    const range = maxPrice.value - minPrice.value
    if (range === 0) return PAD_Y + INNER_H / 2
    return PAD_Y + ((maxPrice.value - price) / range) * INNER_H
}

// ── SVG path ──────────────────────────────────────────────────────────────────

const polylinePoints = computed(() =>
    props.points.map((p, i) => `${scaleX(i)},${scaleY(p.price)}`).join(" ")
)

const areaPath = computed(() => {
    if (props.points.length === 0) return ""
    const first = `${scaleX(0)},${scaleY(props.points[0].price)}`
    const last = `${scaleX(props.points.length - 1)},${scaleY(props.points[props.points.length - 1].price)}`
    const bottom = PAD_Y + INNER_H
    return `M ${first} L ${polylinePoints.value.replace(/ /g, " L ")} L ${last.split(",")[0]},${bottom} L ${PAD_X},${bottom} Z`
})

// ── Tooltip ───────────────────────────────────────────────────────────────────

const hoveredIndex = ref<number | null>(null)

const tooltip = computed(() => {
    if (hoveredIndex.value === null) return null
    const p = props.points[hoveredIndex.value]
    return {
        x: scaleX(hoveredIndex.value),
        y: scaleY(p.price),
        label: `${p.date}: ${formatCurrency(p.price)}`,
    }
})

function formatShortDate(dateStr: string): string {
    const d = new Date(dateStr + "T00:00:00")
    return d.toLocaleDateString("es-MX", { month: "short", day: "numeric" })
}
</script>

<template>
    <div class="w-full">
        <svg
            :viewBox="`0 0 ${W} ${H}`"
            class="w-full h-auto overflow-visible"
            role="img"
            :aria-label="`Gráfica de precio con ${points.length} registros`"
        >
            <!-- Area fill -->
            <path
                :d="areaPath"
                fill="currentColor"
                class="text-primary/10"
            />

            <!-- Line -->
            <polyline
                :points="polylinePoints"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linejoin="round"
                stroke-linecap="round"
                class="text-primary"
            />

            <!-- Data points -->
            <circle
                v-for="(p, i) in points"
                :key="i"
                :cx="scaleX(i)"
                :cy="scaleY(p.price)"
                r="3"
                fill="currentColor"
                class="text-primary cursor-pointer"
                @mouseenter="hoveredIndex = i"
                @mouseleave="hoveredIndex = null"
            />

            <!-- Tooltip -->
            <template v-if="tooltip">
                <!-- Background rect -->
                <rect
                    :x="
                        Math.min(tooltip.x - 4, W - 130)
                    "
                    :y="tooltip.y - 24"
                    width="124"
                    height="18"
                    rx="3"
                    fill="currentColor"
                    class="text-popover"
                />
                <text
                    :x="Math.min(tooltip.x, W - 122)"
                    :y="tooltip.y - 11"
                    class="text-popover-foreground"
                    fill="currentColor"
                    font-size="9"
                    font-family="inherit"
                >
                    {{ tooltip.label }}
                </text>
            </template>

            <!-- X-axis labels (first and last) -->
            <text
                v-if="points.length > 0"
                :x="PAD_X"
                :y="H - 2"
                font-size="8"
                fill="currentColor"
                class="text-muted-foreground"
                text-anchor="start"
            >
                {{ formatShortDate(points[0].date) }}
            </text>
            <text
                v-if="points.length > 1"
                :x="W - PAD_X"
                :y="H - 2"
                font-size="8"
                fill="currentColor"
                class="text-muted-foreground"
                text-anchor="end"
            >
                {{ formatShortDate(points[points.length - 1].date) }}
            </text>
        </svg>
    </div>
</template>

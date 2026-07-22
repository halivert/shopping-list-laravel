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

const maxPrice = computed(() => Math.max(...props.points.map((p) => p.price)))
// The Y-axis always starts at 0, with a bit of headroom above the peak so the
// line doesn't sit flush against the top edge.
const chartTop = computed(() => maxPrice.value * 1.1)

function scaleX(index: number): number {
    if (props.points.length === 1) return PAD_X + INNER_W / 2
    return PAD_X + (index / (props.points.length - 1)) * INNER_W
}

function scaleY(price: number): number {
    const top = chartTop.value
    if (top === 0) return PAD_Y + INNER_H
    return PAD_Y + ((top - price) / top) * INNER_H
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
// `activeIndex` is sticky (set by tap/click, for touch devices with no pointer);
// `hoverIndex` is a transient desktop-only preview. Hover wins while active.

const activeIndex = ref<number | null>(null)
const hoverIndex = ref<number | null>(null)
const shownIndex = computed(() => hoverIndex.value ?? activeIndex.value)

function toggle(i: number) {
    activeIndex.value = activeIndex.value === i ? null : i
}

const tooltip = computed(() => {
    if (shownIndex.value === null) return null
    const p = props.points[shownIndex.value]
    return {
        x: scaleX(shownIndex.value),
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
            <!-- Tap-outside-to-dismiss background -->
            <rect
                x="0"
                y="0"
                :width="W"
                :height="H"
                fill="transparent"
                @click="activeIndex = null"
            />

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
            <template v-for="(p, i) in points" :key="i">
                <!-- Visible dot (decorative; grows when its price is shown) -->
                <circle
                    :cx="scaleX(i)"
                    :cy="scaleY(p.price)"
                    :r="shownIndex === i ? 4.5 : 3"
                    fill="currentColor"
                    class="text-primary pointer-events-none transition-[r]"
                />
                <!-- Larger transparent hit target: tap toggles the price on
                     touch devices (no hover), mouse still previews on hover. -->
                <circle
                    :cx="scaleX(i)"
                    :cy="scaleY(p.price)"
                    r="12"
                    fill="transparent"
                    class="cursor-pointer"
                    tabindex="0"
                    role="button"
                    :aria-label="`${p.date}: ${formatCurrency(p.price)}`"
                    @click.stop="toggle(i)"
                    @mouseenter="hoverIndex = i"
                    @mouseleave="hoverIndex = null"
                    @focus="hoverIndex = i"
                    @blur="hoverIndex = null"
                />
            </template>

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

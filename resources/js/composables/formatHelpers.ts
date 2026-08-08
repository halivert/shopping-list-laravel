import { usePage } from "@inertiajs/vue3"

export function formatCurrency(number: number): string {
    const page = usePage()

    return new Intl.NumberFormat(page.props.lang, {
        style: "currency",
        currency: "MXN",
        currencyDisplay: "symbol",
    }).format(number)
}

export function formatDate(
    strDate: string,
    style: "medium" | "full" | "long" | "short" = "medium"
): string {
    const page = usePage()
    const date = parseDate(strDate)

    return new Intl.DateTimeFormat(page.props.lang, {
        dateStyle: style,
    }).format(date)
}

/**
 * Parses a date-only string (e.g. "2026-08-07") as a local calendar date
 * rather than a UTC instant — `new Date("2026-08-07")` is UTC midnight, which
 * renders as the previous day in negative-offset timezones. Full ISO strings
 * (with a time component, e.g. timestamps like `updatedAt`) are left as-is.
 */
function parseDate(strDate: string): Date {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(strDate)

    if (!match) {
        return new Date(strDate)
    }

    const [, year, month, day] = match
    return new Date(Number(year), Number(month) - 1, Number(day))
}

/**
 * Lowercases and strips diacritics so searches match regardless of accents,
 * e.g. "cafe" matches "café".
 */
export function normalizeForSearch(str: string): string {
    return str
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .toLowerCase()
        .trim()
}

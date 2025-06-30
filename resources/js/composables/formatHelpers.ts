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
    const date = new Date(strDate)

    return new Intl.DateTimeFormat(page.props.lang, {
        dateStyle: style,
    }).format(date)
}

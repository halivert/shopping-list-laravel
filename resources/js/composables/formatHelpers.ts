import { usePage } from "@inertiajs/vue3"

export function getCurrency(number: number): string {
    const page = usePage()

    return new Intl.NumberFormat(page.props.lang, {
        style: "currency",
        currency: "MXN",
        currencyDisplay: "symbol",
    }).format(number)
}

export function formatDate(strDate: string): string {
    const page = usePage()
    const date = new Date(strDate)

    return new Intl.DateTimeFormat(page.props.lang, {
        dateStyle: "medium",
    }).format(date)
}

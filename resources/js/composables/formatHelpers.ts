import { usePage } from "@inertiajs/vue3"

export function getCurrency(number: number): string {
    const page = usePage()

    return new Intl.NumberFormat(page.props.lang, {
        style: "currency",
        currency: "MXN",
        currencyDisplay: "symbol",
    }).format(number)
}

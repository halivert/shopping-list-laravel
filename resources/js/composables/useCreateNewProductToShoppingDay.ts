import { MaybeRef, unref } from "vue"
import { useForm } from "@inertiajs/vue3"

export function useCreateNewProductToShoppingDay(
    shoppingDayId: MaybeRef<string>
) {
    const form = useForm({ name: "" })

    function handleSubmit() {
        form.post(
            route("shopping-days.products.create", {
                shoppingDay: unref(shoppingDayId),
            }),
            {
                preserveScroll: true,
                onSuccess: () => (form.name = ""),
            }
        )
    }

    return { form, handleSubmit }
}

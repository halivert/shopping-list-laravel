import { MaybeRef, unref } from "vue"
import { useForm } from "@inertiajs/vue3"

type FormOptions = Parameters<ReturnType<typeof useForm>["post"]>[1]

export function useCreateNewProductToShoppingDay(
    shoppingDayId: MaybeRef<string>,
    { onSuccess }: FormOptions = {}
) {
    const form = useForm({ name: "" })

    function handleSubmit() {
        form.post(
            route("shopping-days.products.create", {
                shoppingDay: unref(shoppingDayId),
            }),
            {
                preserveScroll: true,
                onSuccess: (response) => {
                    form.name = ""

                    onSuccess?.(response)
                },
            }
        )
    }

    return { form, handleSubmit }
}

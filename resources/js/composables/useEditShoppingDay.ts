import { MaybeRef, ref, unref } from "vue"
import { useForm } from "@inertiajs/vue3"

import type { ShoppingDay } from "@/types/ShoppingDay"

export function useEditShoppingDay<AdditionalData = unknown>(
    shoppingDay: MaybeRef<ShoppingDay>,
    additionalData?: MaybeRef<Record<string, AdditionalData>>
) {
    const form = useForm({ date: unref(shoppingDay).date.split("T")[0] })
    const isEditing = ref(false)

    function handleSubmit() {
        form.transform((data) => ({
            date: new Date(data.date + "T00:00"),
            ...unref(additionalData),
        })).patch(
            route("shopping-days.update", {
                shoppingDay: unref(shoppingDay).id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    isEditing.value = false
                },
            }
        )
    }

    return { handleSubmit, form, isEditing }
}

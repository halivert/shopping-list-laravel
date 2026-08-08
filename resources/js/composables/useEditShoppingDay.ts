import { MaybeRef, ref, unref } from "vue"
import { useForm } from "@inertiajs/vue3"

import type { ShoppingDay } from "@/types/ShoppingDay"

export function useEditShoppingDay<AdditionalData = unknown>(
    shoppingDay: MaybeRef<ShoppingDay>,
    additionalData?: MaybeRef<Record<string, AdditionalData>>
) {
    // shoppingDay.date is already a plain "YYYY-MM-DD" string from the
    // backend — matches what an <input type="date"> reads/writes, so no
    // Date round-trip is needed (that used to shift the day in +offset tzs).
    const form = useForm({ date: unref(shoppingDay).date })
    const isEditing = ref(false)

    function handleSubmit() {
        form.transform((data) => ({
            date: data.date,
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

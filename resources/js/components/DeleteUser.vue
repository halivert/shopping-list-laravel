<script setup lang="ts">
import { useForm } from "@inertiajs/vue3"
import { ref } from "vue"

// Components
import HeadingSmall from "@/components/HeadingSmall.vue"
import { Button } from "@/components/ui/button"
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"

const form = useForm({})

const deleteUser = (e: Event) => {
    e.preventDefault()

    form.delete(route("profile.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    })
}

const closeModal = () => {
    form.clearErrors()
    form.reset()
}
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall
            title="Borrar cuenta"
            description="Borra tu cuenta y todos los recursos"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Cuidado</p>
                <p class="text-sm">
                    Por favor ten cuidado, esta acción no se puede deshacer.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Borrar cuenta</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >¿Seguro que deseas borrar tu
                                cuenta?</DialogTitle
                            >
                            <DialogDescription>
                                Una vez que tu cuenta sea borrada, todos los
                                recursos y datos también serán permanentemente
                                borrados.
                                <br />
                                <strong class="underline">
                                    Ninguna compra podrá ser reembolsada.
                                </strong>
                            </DialogDescription>
                        </DialogHeader>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" @click="closeModal">
                                    Cancelar
                                </Button>
                            </DialogClose>

                            <Button
                                variant="destructive"
                                :disabled="form.processing"
                            >
                                <button type="submit">Borrar cuenta</button>
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>

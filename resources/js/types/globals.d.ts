import type { route as routeFn } from "ziggy-js"
import type { Page } from "@inertiajs/core"
import { SharedData } from "."

declare global {
    const route: typeof routeFn
}

declare module "@inertiajs/vue3" {
    export declare function usePage<T = SharedData>(): Page<T>
}

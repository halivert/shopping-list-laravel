import { onUnmounted } from "vue"
import { useHttp } from "@inertiajs/vue3"
import { echo } from "@laravel/echo-vue"
import { useDebounceFn } from "@vueuse/core"

type Changes = Record<string, string | number | boolean | null | string[]>

interface Options {
    method?: "patch" | "put" | "post"
    delay?: number
}

/**
 * Saves edits of many records (list items, products…) in the background.
 *
 * - Changes are accumulated per record and flushed in a single debounced
 *   request per record, so editing several fields at once sends one request
 *   and editing different records never drops the earlier ones.
 * - Requests go through useHttp instead of router visits on purpose: Inertia
 *   cancels in-flight visits when a new one starts (sync visits always, async
 *   ones when the URL differs from the current page), which made concurrent
 *   saves cancel each other. The endpoint must answer JSON with 204.
 * - The Echo socket id is sent so `broadcast(...)->toOthers()` skips this tab.
 * - Pending changes are flushed when the component unmounts. Await
 *   `flushAll()` before an action that depends on them being saved.
 */
export function useQueuedUpdates<T extends Changes>(
    url: (id: string) => string,
    { method = "patch", delay = 500 }: Options = {}
) {
    // The form data itself is unused: each request sends its changes through
    // transform(), so the instance can be shared by all records.
    const http = useHttp<Changes>({})
    const pending = new Map<string, Partial<T>>()
    const savers = new Map<string, () => void>()

    function flush(id: string): Promise<unknown> {
        const changes = pending.get(id)
        pending.delete(id)

        if (!changes) return Promise.resolve()

        const socketId = echo().socketId()

        return http
            .transform(() => changes as Changes)
            [method](url(id), {
                headers: socketId ? { "X-Socket-ID": socketId } : {},
            })
            .catch(() => {
                // Keep the unsaved changes (unless newer ones arrived
                // meanwhile) so the next edit of this record retries them.
                pending.set(id, { ...changes, ...pending.get(id) })
            })
    }

    function queue(id: string, changes: Partial<T>) {
        pending.set(id, { ...pending.get(id), ...changes })

        if (!savers.has(id)) {
            savers.set(
                id,
                useDebounceFn(() => flush(id), delay)
            )
        }

        savers.get(id)!()
    }

    /** Saves every pending change now; resolves once all requests settle. */
    function flushAll(): Promise<unknown> {
        return Promise.allSettled([...pending.keys()].map(flush))
    }

    onUnmounted(() => {
        flushAll()
    })

    return { queue, flushAll }
}

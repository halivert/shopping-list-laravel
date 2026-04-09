import { createInertiaApp } from "@inertiajs/vue3"
import createServer from "@inertiajs/vue3/server"
import { renderToString } from "@vue/server-renderer"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import type { DefineComponent } from "vue"
import { createSSRApp, h } from "vue"
import { route as ziggyRoute } from "ziggy-js"
import type { Config } from "ziggy-js"

interface RawZiggyConfig {
    location: string
    [key: string]: unknown
}

function isRawZiggyConfig(value: unknown): value is RawZiggyConfig {
    if (typeof value !== "object" || value === null) return false
    const obj = value as Record<string, unknown>
    return typeof obj.location === "string"
}

// SSR bridge: Inertia page props are untyped at the createServer boundary.
// The spread below is safe at runtime — PHP sends a valid Ziggy Config shape.
function toZiggyConfig(raw: RawZiggyConfig): Config {
    return { ...(raw as unknown as Config), location: new URL(raw.location) }
}

const appName = import.meta.env.VITE_APP_NAME || "Laravel"

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.vue`,
                import.meta.glob<DefineComponent>("./pages/**/*.vue")
            ),
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) })

            const ziggyProp = page.props.ziggy
            if (!isRawZiggyConfig(ziggyProp)) {
                throw new Error("Ziggy config is missing from page props")
            }

            const ziggyConfig = toZiggyConfig(ziggyProp)

            const route = (name: string, params?: any, absolute?: boolean) =>
                ziggyRoute(name, params, absolute, ziggyConfig)

            Object.assign(app.config.globalProperties, { route })

            if (typeof window === "undefined") {
                Object.assign(globalThis, { route })
            }

            app.use(plugin)

            return app
        },
    })
)

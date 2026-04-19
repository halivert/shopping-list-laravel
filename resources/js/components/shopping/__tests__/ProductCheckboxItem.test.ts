import { describe, it, expect, vi, beforeEach } from "vitest"
import { mount } from "@vue/test-utils"
import type { Product } from "@/types/Product"

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({ props: { lang: "es-MX" } }),
}))

// Import after mock is set up
const { default: ProductCheckboxItem } = await import(
    "../ProductCheckboxItem.vue"
)

const productWithPrice: Product = {
    id: "1",
    name: "Leche",
    lastPrice: 25.5,
}

const productWithoutPrice: Product = {
    id: "2",
    name: "Champú",
}

function mountItem(product: Product, checked: boolean) {
    return mount(ProductCheckboxItem, {
        props: { product, checked },
        global: {
            stubs: { Checkbox: true },
        },
    })
}

describe("ProductCheckboxItem", () => {
    describe("rendering", () => {
        it("renders the product name", () => {
            const wrapper = mountItem(productWithPrice, false)
            expect(wrapper.text()).toContain("Leche")
        })

        it("renders lastPrice when product has one", () => {
            const wrapper = mountItem(productWithPrice, false)
            expect(wrapper.text()).toMatch(/\$|MXN|25/)
        })

        it("does not render price when product has no lastPrice", () => {
            const wrapper = mountItem(productWithoutPrice, false)
            expect(wrapper.find("[data-testid='last-price']").exists()).toBe(
                false
            )
        })
    })

    describe("checked state styling", () => {
        it("applies highlight class when checked", () => {
            const wrapper = mountItem(productWithPrice, true)
            expect(wrapper.classes()).toContain("bg-primary/10")
        })

        it("does not apply highlight class when unchecked", () => {
            const wrapper = mountItem(productWithPrice, false)
            expect(wrapper.classes()).not.toContain("bg-primary/10")
        })

        it("applies font-medium to name when checked", () => {
            const wrapper = mountItem(productWithPrice, true)
            const nameSpan = wrapper.find("[data-testid='product-name']")
            expect(nameSpan.classes()).toContain("font-medium")
        })

        it("does not apply font-medium to name when unchecked", () => {
            const wrapper = mountItem(productWithPrice, false)
            const nameSpan = wrapper.find("[data-testid='product-name']")
            expect(nameSpan.classes()).not.toContain("font-medium")
        })

        it("underlines name when checked and product has no lastPrice", () => {
            const wrapper = mountItem(productWithoutPrice, true)
            const nameSpan = wrapper.find("[data-testid='product-name']")
            expect(nameSpan.classes()).toContain("underline")
        })

        it("does not underline name when checked and product has a lastPrice", () => {
            const wrapper = mountItem(productWithPrice, true)
            const nameSpan = wrapper.find("[data-testid='product-name']")
            expect(nameSpan.classes()).not.toContain("underline")
        })
    })

    describe("events", () => {
        it("emits update:checked with true when Checkbox emits update:checked true", async () => {
            const wrapper = mountItem(productWithPrice, false)
            await wrapper
                .findComponent({ name: "Checkbox" })
                .vm.$emit("update:checked", true)
            expect(wrapper.emitted("update:checked")).toBeTruthy()
            expect(wrapper.emitted("update:checked")![0]).toEqual([true])
        })

        it("emits update:checked with false when Checkbox emits update:checked false", async () => {
            const wrapper = mountItem(productWithPrice, true)
            await wrapper
                .findComponent({ name: "Checkbox" })
                .vm.$emit("update:checked", false)
            expect(wrapper.emitted("update:checked")).toBeTruthy()
            expect(wrapper.emitted("update:checked")![0]).toEqual([false])
        })
    })
})

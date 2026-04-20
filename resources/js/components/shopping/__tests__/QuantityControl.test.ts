import { describe, it, expect } from "vitest"
import { mount } from "@vue/test-utils"
import QuantityControl from "../QuantityControl.vue"

function mountControl(quantity: number) {
    return mount(QuantityControl, {
        props: { modelValue: quantity },
    })
}

describe("QuantityControl", () => {
    describe("rendering", () => {
        it("displays the current quantity", () => {
            const wrapper = mountControl(3)
            expect(wrapper.text()).toContain("3")
        })

        it("displays quantity of 1", () => {
            const wrapper = mountControl(1)
            expect(wrapper.text()).toContain("1")
        })
    })

    describe("increment", () => {
        it("emits update:modelValue with incremented value on plus click", async () => {
            const wrapper = mountControl(2)
            await wrapper
                .find("[data-testid='increment']")
                .trigger("click")
            expect(wrapper.emitted("update:modelValue")![0]).toEqual([3])
        })
    })

    describe("decrement", () => {
        it("emits update:modelValue with decremented value on minus click", async () => {
            const wrapper = mountControl(3)
            await wrapper
                .find("[data-testid='decrement']")
                .trigger("click")
            expect(wrapper.emitted("update:modelValue")![0]).toEqual([2])
        })

        it("disables minus button when quantity is 1", () => {
            const wrapper = mountControl(1)
            const button = wrapper.find("[data-testid='decrement']")
            expect(button.attributes("disabled")).toBeDefined()
        })

        it("does not emit when minus is clicked at quantity 1", async () => {
            const wrapper = mountControl(1)
            await wrapper
                .find("[data-testid='decrement']")
                .trigger("click")
            expect(wrapper.emitted("update:modelValue")).toBeUndefined()
        })
    })
})

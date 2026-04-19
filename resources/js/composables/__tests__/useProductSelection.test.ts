import { describe, it, expect } from "vitest"
import { ref } from "vue"
import type { Product } from "@/types/Product"
import type { ShoppingDayItem } from "@/types/ShoppingDayItem"
import { useProductSelection } from "@/composables/useProductSelection"

const products: Product[] = [
    { id: "a", name: "Leche", searchIndex: 2, shoppingIndex: 1 },
    { id: "b", name: "Pan", searchIndex: 1, shoppingIndex: 3, lastPrice: 20 },
    { id: "c", name: "Agua-6", searchIndex: 3, shoppingIndex: 2 },
    { id: "d", name: "Champú", searchIndex: 4, shoppingIndex: 4 },
]

describe("useProductSelection", () => {
    describe("filteredProducts", () => {
        it("returns all products when query is empty", () => {
            const { filteredProducts } = useProductSelection(ref(products), [])
            expect(filteredProducts.value).toHaveLength(4)
        })

        it("filters products case-insensitively by name", () => {
            const { filteredProducts, searchQuery } = useProductSelection(
                ref(products),
                []
            )
            searchQuery.value = "le"
            expect(filteredProducts.value).toHaveLength(1)
            expect(filteredProducts.value[0].id).toBe("a")
        })

        it("returns empty array when no products match", () => {
            const { filteredProducts, searchQuery } = useProductSelection(
                ref(products),
                []
            )
            searchQuery.value = "xyz"
            expect(filteredProducts.value).toHaveLength(0)
        })

        it("ignores leading/trailing whitespace in query", () => {
            const { filteredProducts, searchQuery } = useProductSelection(
                ref(products),
                []
            )
            searchQuery.value = "  pan  "
            expect(filteredProducts.value).toHaveLength(1)
            expect(filteredProducts.value[0].id).toBe("b")
        })
    })

    describe("selectedProducts", () => {
        it("returns only products whose ids are selected", () => {
            const { selectedProducts } = useProductSelection(
                ref(products),
                ["a", "c"]
            )
            expect(selectedProducts.value.map((p) => p.id)).toEqual(
                expect.arrayContaining(["a", "c"])
            )
            expect(selectedProducts.value).toHaveLength(2)
        })

        it("is empty when no products are selected", () => {
            const { selectedProducts } = useProductSelection(ref(products), [])
            expect(selectedProducts.value).toHaveLength(0)
        })

        it("is sorted by shoppingIndex ascending", () => {
            const { selectedProducts } = useProductSelection(
                ref(products),
                ["a", "b", "c"]
            )
            const indices = selectedProducts.value.map(
                (p) => p.shoppingIndex ?? 0
            )
            expect(indices).toEqual([...indices].sort((x, y) => x - y))
        })

        it("only includes filtered products when search is active", () => {
            const { selectedProducts, searchQuery } = useProductSelection(
                ref(products),
                ["a", "b"]
            )
            searchQuery.value = "pan"
            expect(selectedProducts.value.map((p) => p.id)).toEqual(["b"])
        })
    })

    describe("availableProducts", () => {
        it("returns products not in the selected list", () => {
            const { availableProducts } = useProductSelection(
                ref(products),
                ["a"]
            )
            expect(availableProducts.value.map((p) => p.id)).not.toContain("a")
            expect(availableProducts.value).toHaveLength(3)
        })

        it("is sorted by searchIndex ascending", () => {
            const { availableProducts } = useProductSelection(
                ref(products),
                []
            )
            const indices = availableProducts.value.map(
                (p) => p.searchIndex ?? 0
            )
            expect(indices).toEqual([...indices].sort((x, y) => x - y))
        })

        it("only includes filtered products when search is active", () => {
            const { availableProducts, searchQuery } = useProductSelection(
                ref(products),
                []
            )
            searchQuery.value = "agua"
            expect(availableProducts.value.map((p) => p.id)).toEqual(["c"])
        })
    })

    describe("toggleProduct", () => {
        it("adds a product id to selected when checked", () => {
            const { selectedIds, toggleProduct } = useProductSelection(
                ref(products),
                []
            )
            toggleProduct("b", true)
            expect(selectedIds.value).toContain("b")
        })

        it("removes a product id from selected when unchecked", () => {
            const { selectedIds, toggleProduct } = useProductSelection(
                ref(products),
                ["a", "b"]
            )
            toggleProduct("a", false)
            expect(selectedIds.value).not.toContain("a")
            expect(selectedIds.value).toContain("b")
        })

        it("does not duplicate ids when toggling an already-selected product", () => {
            const { selectedIds, toggleProduct } = useProductSelection(
                ref(products),
                ["b"]
            )
            toggleProduct("b", true)
            expect(selectedIds.value.filter((id) => id === "b")).toHaveLength(1)
        })
    })

    describe("item quantities", () => {
        const existingItems: ShoppingDayItem[] = [
            {
                id: "item-1",
                product: products[0],
                index: 1,
                unitPrice: 0,
                quantity: 2,
            },
            {
                id: "item-2",
                product: products[2],
                index: 2,
                unitPrice: 0,
                quantity: 6,
            },
        ]

        it("initialises quantities from existing items", () => {
            const { getQuantity } = useProductSelection(
                ref(products),
                ["a", "c"],
                existingItems
            )
            expect(getQuantity("item-1")).toBe(2)
            expect(getQuantity("item-2")).toBe(6)
        })

        it("returns 1 as default quantity for items with no record", () => {
            const { getQuantity } = useProductSelection(ref(products), [])
            expect(getQuantity("unknown-item")).toBe(1)
        })

        it("setQuantity updates the quantity for a given item id", () => {
            const { getQuantity, setQuantity } = useProductSelection(
                ref(products),
                ["a"],
                existingItems
            )
            setQuantity("item-1", 5)
            expect(getQuantity("item-1")).toBe(5)
        })

        it("setQuantity ignores values less than or equal to zero", () => {
            const { getQuantity, setQuantity } = useProductSelection(
                ref(products),
                ["a"],
                existingItems
            )
            setQuantity("item-1", 0)
            setQuantity("item-1", -3)
            expect(getQuantity("item-1")).toBe(2)
        })

        it("getItemsPayload returns array of id/quantity pairs for all tracked items", () => {
            const { setQuantity, getItemsPayload } = useProductSelection(
                ref(products),
                ["a", "c"],
                existingItems
            )
            setQuantity("item-2", 3)
            const payload = getItemsPayload()
            expect(payload).toContainEqual({ id: "item-1", quantity: 2 })
            expect(payload).toContainEqual({ id: "item-2", quantity: 3 })
        })
    })
})

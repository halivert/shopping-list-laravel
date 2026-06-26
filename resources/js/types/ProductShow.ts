export interface ProductPurchase {
    shoppingDayId: string
    date: string
    quantity: number | null
    unitPrice: number | null
}

export interface ProductStats {
    timesBought: number
    averagePrice: number | null
    minPrice: number | null
    maxPrice: number | null
    averageQuantity: number | null
    avgDaysBetween: number | null
    daysPerUnit: number | null
    estimatedDuration: number | null
    totalSpent: number
}

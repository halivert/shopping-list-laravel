/** A store where the owner shops; each one has its own shopping order. */
export interface Store {
    id: string
    name: string
}

/** Store with its order: position of each product, keyed by product id. */
export interface StoreWithPositions extends Store {
    positions: Record<string, number>
}

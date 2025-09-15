export interface LineItem {
    sku: number;
    name: string;
    price: number;
    quantity: number;
}

export function displayShoppingCart(items: LineItem[]) {
    const coalescedItems = new Map<number, LineItem>();
    
    for (const item of items) {
        if (coalescedItems.has(item.sku)) {
            const existing = coalescedItems.get(item.sku)!;
            existing.quantity += item.quantity;
        } else {
            coalescedItems.set(item.sku, { ...item });
        }
    }
    
    return Array.from(coalescedItems.values());
}
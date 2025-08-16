export interface PromoCode {
    code: string;
    discountType: "percentage" | "fixed";
    discountValue: number;
    validFrom: string;
    validTo: string;
    maxUses: number;
    currentUses: number;
}

// Mock database - in-memory array
export const promoCodes: PromoCode[] = [
    {
        code: "SAVE20",
        discountType: "percentage",
        discountValue: 0.20,
        validFrom: "2024-01-01",
        validTo: "2074-12-31",
        maxUses: 1000,
        currentUses: 150
    },
    {
        code: "FLAT50",
        discountType: "fixed",
        discountValue: 50.00,
        validFrom: "2024-01-01",
        validTo: "2074-12-31",
        maxUses: 500,
        currentUses: 75
    }
];

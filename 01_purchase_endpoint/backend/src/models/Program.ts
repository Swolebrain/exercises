export interface Program {
    id: string;
    name: string;
    basePrice: number;
    duration_months: string;
}

export interface ProgramWithCalculations extends Program {
    taxRate?: number;
    taxAmount?: number;
    discountAmount?: number;
    finalPrice?: number;
}

// Mock database - in-memory array
export const programs: Program[] = [
    {
        id: "prog_001",
        name: "App Membership",
        basePrice: 99.99,
        duration_months: "1"
    },
    {
        id: "prog_002",
        name: "App Membership",
        basePrice: 199.99,
        duration_months: "3"
    },
    {
        id: "prog_003",
        name: "App Membership",
        basePrice: 349.99,
        duration_months: "6"
    }
];

export interface TaxRate {
    zipCode: string;
    taxRate: number;
}

// Utility function to calculate tax rate from zip code
// Returns the last digit of the zip code as a percentage (e.g., 12345 -> 0.05)
export function getTaxRateFromZipCode(zipCode: string): number {
    if (!zipCode || zipCode.length !== 5 || !/^\d{5}$/.test(zipCode)) {
        throw new Error("Invalid ZIP code format");
    }
    
    const lastDigit = parseInt(zipCode.charAt(4));
    return lastDigit / 100; // Convert to decimal (e.g., 5 -> 0.05)
}

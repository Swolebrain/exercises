import { TaxRate, getTaxRateFromZipCode } from '../models/TaxRate';

export class TaxRatesRepository {
    /**
     * Get tax rate for a specific zip code
     * Returns the last digit of the zip code as a percentage
     */
    getTaxRateByZipCode(zipCode: string): TaxRate {
        const taxRate = getTaxRateFromZipCode(zipCode);
        return {
            zipCode,
            taxRate
        };
    }

    /**
     * Check if a zip code is valid
     */
    isValidZipCode(zipCode: string): boolean {
        try {
            getTaxRateFromZipCode(zipCode);
            return true;
        } catch {
            return false;
        }
    }
}

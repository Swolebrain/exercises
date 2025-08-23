import { TaxRate } from '../models/TaxRate';
import { TaxResponse } from '../models/TaxResponse';

export class TaxCalculationService {
    /**
     * Calculate tax on a given price and return new price
     */
    calculateTaxOnPrice(price: number, taxRate: TaxRate): TaxResponse {
        const taxAmount = price * taxRate.taxRate;
        const newPrice = price + taxAmount;

        return {
            originalPrice: price,
            taxRate: taxRate.taxRate,
            taxAmount: Math.round(taxAmount * 100) / 100,
            newPrice: Math.round(newPrice * 100) / 100
        };
    }
}

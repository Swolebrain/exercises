import { Program, ProgramWithCalculations } from '../models/Program';
import { PromoCode } from '../models/PromoCode';
import { TaxRate } from '../models/TaxRate';

export class PriceCalculationService {
    /**
     * Calculate final price with all modifications applied
     */
    calculateFinalPrice(
        program: Program,
        promoCode?: PromoCode,
        taxRate?: TaxRate
    ): ProgramWithCalculations {
        let finalPrice = program.basePrice;
        let discountAmount = 0;

        // Apply promo code discount first
        if (promoCode) {
            if (promoCode.discountType === "percentage") {
                discountAmount = program.basePrice * promoCode.discountValue;
            } else {
                discountAmount = promoCode.discountValue;
            }
            
            // Ensure discount doesn't exceed base price
            discountAmount = Math.min(discountAmount, program.basePrice);
            finalPrice = program.basePrice - discountAmount;
        }

        // Apply tax on the discounted price
        let taxAmount = 0;
        if (taxRate) {
            taxAmount = finalPrice * taxRate.taxRate;
            finalPrice += taxAmount;
        }

        // Round all monetary values to 2 decimal places
        discountAmount = Math.round(discountAmount * 100) / 100;
        taxAmount = Math.round(taxAmount * 100) / 100;
        finalPrice = Math.round(finalPrice * 100) / 100;

        return {
            ...program,
            taxRate: taxRate?.taxRate,
            taxAmount,
            discountAmount,
            finalPrice
        };
    }

    /**
     * Calculate tax amount for a given price and tax rate
     */
    calculateTaxAmount(price: number, taxRate: number): number {
        const taxAmount = price * taxRate;
        return Math.round(taxAmount * 100) / 100;
    }

    /**
     * Calculate discount amount for a given price and promo code
     */
    calculateDiscountAmount(price: number, promoCode: PromoCode): number {
        let discountAmount: number;
        
        if (promoCode.discountType === "percentage") {
            discountAmount = price * promoCode.discountValue;
        } else {
            discountAmount = promoCode.discountValue;
        }

        // Ensure discount doesn't exceed price
        discountAmount = Math.min(discountAmount, price);
        return Math.round(discountAmount * 100) / 100;
    }
}

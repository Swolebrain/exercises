import { Program } from '../types';
import { TaxResponse } from '../types';
import { PromoCodeValidation } from '../types';

export interface CalculatedPrice {
    basePrice: number;
    discountAmount: number;
    priceAfterDiscount: number;
    taxRate: number;
    taxAmount: number;
    finalPrice: number;
}

export class PriceCalculationService {
    /**
     * Calculate final price with hardcoded business logic:
     * 1. Apply discount first
     * 2. Apply tax to discounted price
     */
    calculatePrice(
        program: Program,
        promoCode?: PromoCodeValidation,
        taxResponse?: TaxResponse
    ): CalculatedPrice {
        let discountAmount = 0;
        let priceAfterDiscount = program.basePrice;
        let taxRate = 0;
        let taxAmount = 0;
        let finalPrice = program.basePrice;

        // Step 1: Apply discount first (hardcoded sequence)
        if (promoCode && promoCode.isValid) {
            if (promoCode.discountType === "percentage") {
                discountAmount = program.basePrice * promoCode.discountValue;
            } else {
                discountAmount = promoCode.discountValue;
            }
            
            // Ensure discount doesn't exceed base price
            discountAmount = Math.min(discountAmount, program.basePrice);
            priceAfterDiscount = program.basePrice - discountAmount;
            finalPrice = priceAfterDiscount;
        }

        // Step 2: Apply tax to the discounted price (hardcoded sequence)
        if (taxResponse) {
            taxRate = taxResponse.taxRate;
            // Calculate tax on the discounted price, not the original price
            taxAmount = priceAfterDiscount * taxRate;
            finalPrice = priceAfterDiscount + taxAmount;
        }

        // Round all monetary values to 2 decimal places
        discountAmount = Math.round(discountAmount * 100) / 100;
        priceAfterDiscount = Math.round(priceAfterDiscount * 100) / 100;
        taxAmount = Math.round(taxAmount * 100) / 100;
        finalPrice = Math.round(finalPrice * 100) / 100;

        return {
            basePrice: program.basePrice,
            discountAmount,
            priceAfterDiscount,
            taxRate,
            taxAmount,
            finalPrice
        };
    }

    /**
     * Calculate discount amount for a given price and promo code
     */
    calculateDiscountAmount(price: number, promoCode: PromoCodeValidation): number {
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

    /**
     * Calculate tax amount for a given price and tax rate
     */
    calculateTaxAmount(price: number, taxRate: number): number {
        const taxAmount = price * taxRate;
        return Math.round(taxAmount * 100) / 100;
    }
}

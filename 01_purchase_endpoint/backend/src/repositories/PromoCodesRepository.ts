import { PromoCode } from '../models/PromoCode';

export class PromoCodesRepository {
    /**
     * Get a promo code by its actual code
     */
    getPromoCodeByCode(code: string): PromoCode | undefined {
        return promoCodes.find(promoCode => promoCode.code === code);
    }

    /**
     * Check if a promo code is valid and active
     */
    isPromoCodeValid(code: string): boolean {
        const promoCode = this.getPromoCodeByCode(code);
        if (!promoCode) return false;

        const now = new Date();
        const validFrom = new Date(promoCode.validFrom);
        const validTo = new Date(promoCode.validTo);

        return now >= validFrom && now <= validTo && promoCode.currentUses < promoCode.maxUses;
    }

    /**
     * Increment the usage count of a promo code
     */
    incrementUsage(code: string): boolean {
        const promoCode = this.getPromoCodeByCode(code);
        if (!promoCode || promoCode.currentUses >= promoCode.maxUses) {
            return false;
        }

        promoCode.currentUses++;
        return true;
    }
}

// Import the mock data
import { promoCodes } from '../models/PromoCode';

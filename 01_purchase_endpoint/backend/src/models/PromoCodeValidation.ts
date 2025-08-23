export interface PromoCodeValidation {
    code: string;
    discountType: "percentage" | "fixed";
    discountValue: number;
    isValid: boolean;
}

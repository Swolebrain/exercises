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

export interface CustomerInfo {
    name: string;
    email: string;
    billingAddress: {
        street: string;
        city: string;
        state: string;
        zipCode: string;
        country: string;
    };
}

export interface PurchaseRequest {
    programId: string;
    promoCode?: string;
    customerInfo: CustomerInfo;
}

export interface PurchaseResponse {
    orderId: string;
    program: ProgramWithCalculations;
    total: number;
    timestamp: string;
}

export interface ApiResponse<T> {
    success: boolean;
    data: T;
    message?: string;
}

export interface TaxRequest {
    zipCode: string;
    programId: string;
    promoCode?: string;
}

export interface PromoCodeRequest {
    promoCode: string;
    programId: string;
    zipCode?: string;
}

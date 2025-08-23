import { 
    Program, 
    TaxRequest, 
    TaxResponse, 
    PromoCodeValidationRequest, 
    PromoCodeValidation,
    PurchaseRequest, 
    PurchaseResponse, 
    ApiResponse 
} from '../types';

const API_BASE_URL = '/api';

class ApiService {
    private async makeRequest<T>(endpoint: string, options: RequestInit = {}): Promise<ApiResponse<T>> {
        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers,
                },
                ...options,
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    /**
     * Get all available programs
     */
    async getPrograms(): Promise<Program[]> {
        const response = await this.makeRequest<Program[]>('/list-programs');
        return response.data;
    }

    /**
     * Apply sales tax based on price and zip code
     */
    async applySalesTax(request: TaxRequest): Promise<TaxResponse> {
        const response = await this.makeRequest<TaxResponse>('/apply-sales-tax', {
            method: 'POST',
            body: JSON.stringify(request),
        });
        return response.data;
    }

    /**
     * Validate promotional code
     */
    async validatePromoCode(request: PromoCodeValidationRequest): Promise<PromoCodeValidation> {
        const response = await this.makeRequest<PromoCodeValidation>('/validate-promo-code', {
            method: 'POST',
            body: JSON.stringify(request),
        });
        return response.data;
    }

    /**
     * Purchase a program
     */
    async purchaseProgram(request: PurchaseRequest): Promise<PurchaseResponse> {
        const response = await this.makeRequest<PurchaseResponse>('/purchase-program', {
            method: 'POST',
            body: JSON.stringify(request),
        });
        return response.data;
    }
}

export const apiService = new ApiService();

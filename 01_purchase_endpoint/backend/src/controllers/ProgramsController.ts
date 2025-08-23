import { Request, Response } from 'express';
import { ProgramsRepository } from '../repositories/ProgramsRepository';
import { PromoCodesRepository } from '../repositories/PromoCodesRepository';
import { TaxRatesRepository } from '../repositories/TaxRatesRepository';
import { TaxCalculationService } from '../services/TaxCalculationService';

export class ProgramsController {
    private programsRepository: ProgramsRepository;
    private promoCodesRepository: PromoCodesRepository;
    private taxRatesRepository: TaxRatesRepository;
    private taxCalculationService: TaxCalculationService;

    constructor() {
        this.programsRepository = new ProgramsRepository();
        this.promoCodesRepository = new PromoCodesRepository();
        this.taxRatesRepository = new TaxRatesRepository();
        this.taxCalculationService = new TaxCalculationService();
    }

    /**
     * GET /list-programs
     * Retrieve available programs for purchase
     */
    listPrograms = (req: Request, res: Response): void => {
        try {
            const programs = this.programsRepository.getAllPrograms();
            res.json({
                success: true,
                data: programs
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: "Failed to retrieve programs"
            });
        }
    };

    /**
     * POST /purchase-program
     * Process program purchase
     */
    purchaseProgram = (req: Request, res: Response): void => {
        try {
            const { programId, promoCode, customerInfo } = req.body;

            if (!programId || !customerInfo) {
                res.status(400).json({
                    success: false,
                    message: "Program ID and customer info are required"
                });
                return;
            }

            const program = this.programsRepository.getProgramById(programId);
            if (!program) {
                res.status(404).json({
                    success: false,
                    message: "Program not found"
                });
                return;
            }

            // Validate promo code if provided
            if (promoCode) {
                if (!this.promoCodesRepository.isPromoCodeValid(promoCode)) {
                    res.status(400).json({
                        success: false,
                        message: "Invalid promo code"
                    });
                    return;
                }
                // Increment promo code usage
                this.promoCodesRepository.incrementUsage(promoCode);
            }

            // Generate order ID
            const orderId = `ord_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

            // Note: Frontend handles all price calculations
            res.json({
                success: true,
                data: {
                    orderId,
                    program: program,
                    total: program.basePrice, // Frontend will calculate final price
                    timestamp: new Date().toISOString()
                }
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: "Failed to process purchase"
            });
        }
    };

    /**
     * POST /apply-sales-tax
     * Calculate and apply sales tax based on zip code and price
     */
    applySalesTax = (req: Request, res: Response): void => {
        try {
            const { price, zipCode } = req.body;

            if (!price || !zipCode) {
                res.status(400).json({
                    success: false,
                    message: "Price and ZIP code are required"
                });
                return;
            }

            // Validate zip code
            if (!this.taxRatesRepository.isValidZipCode(zipCode)) {
                res.status(400).json({
                    success: false,
                    message: "Invalid ZIP code"
                });
                return;
            }

            // Get tax rate
            const taxRate = this.taxRatesRepository.getTaxRateByZipCode(zipCode);

            // Calculate tax on the provided price
            const taxResponse = this.taxCalculationService.calculateTaxOnPrice(price, taxRate);

            res.json({
                success: true,
                data: taxResponse
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: "Failed to apply sales tax"
            });
        }
    };

    /**
     * POST /validate-promo-code
     * Validate promotional discount code
     */
    validatePromoCode = (req: Request, res: Response): void => {
        try {
            const { promoCode } = req.body;

            if (!promoCode) {
                res.status(400).json({
                    success: false,
                    message: "Promo code is required"
                });
                return;
            }

            // Validate promo code
            if (!this.promoCodesRepository.isPromoCodeValid(promoCode)) {
                res.status(400).json({
                    success: false,
                    message: "Invalid promo code"
                });
                return;
            }

            // Get promo code object
            const promoCodeObj = this.promoCodesRepository.getPromoCodeByCode(promoCode);

            if (!promoCodeObj) {
                res.status(400).json({
                    success: false,
                    message: "Promo code not found"
                });
                return;
            }

            res.json({
                success: true,
                data: {
                    code: promoCodeObj.code,
                    discountType: promoCodeObj.discountType,
                    discountValue: promoCodeObj.discountValue,
                    isValid: true
                }
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: "Failed to validate promo code"
            });
        }
    };
}

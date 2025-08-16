import { Request, Response } from 'express';
import { ProgramsRepository } from '../repositories/ProgramsRepository';
import { PromoCodesRepository } from '../repositories/PromoCodesRepository';
import { TaxRatesRepository } from '../repositories/TaxRatesRepository';
import { PriceCalculationService } from '../services/PriceCalculationService';

export class ProgramsController {
    private programsRepository: ProgramsRepository;
    private promoCodesRepository: PromoCodesRepository;
    private taxRatesRepository: TaxRatesRepository;
    private priceCalculationService: PriceCalculationService;

    constructor() {
        this.programsRepository = new ProgramsRepository();
        this.promoCodesRepository = new PromoCodesRepository();
        this.taxRatesRepository = new TaxRatesRepository();
        this.priceCalculationService = new PriceCalculationService();
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

            // Get promo code if provided
            let promoCodeObj = undefined;
            if (promoCode) {
                promoCodeObj = this.promoCodesRepository.getPromoCodeByCode(promoCode);
                if (!promoCodeObj || !this.promoCodesRepository.isPromoCodeValid(promoCode)) {
                    res.status(400).json({
                        success: false,
                        message: "Invalid promo code"
                    });
                    return;
                }
            }

            // Calculate final price
            const finalProgram = this.priceCalculationService.calculateFinalPrice(program, promoCodeObj);

            // Generate order ID
            const orderId = `ord_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

            // Increment promo code usage if used
            if (promoCodeObj) {
                this.promoCodesRepository.incrementUsage(promoCode);
            }

            res.json({
                success: true,
                data: {
                    orderId,
                    program: finalProgram,
                    total: finalProgram.finalPrice,
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
     * Calculate and apply sales tax based on zip code
     */
    applySalesTax = (req: Request, res: Response): void => {
        try {
            const { zipCode, programId, promoCode } = req.body;

            if (!zipCode || !programId) {
                res.status(400).json({
                    success: false,
                    message: "ZIP code and program ID are required"
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

            // Get program
            const program = this.programsRepository.getProgramById(programId);
            if (!program) {
                res.status(404).json({
                    success: false,
                    message: "Program not found"
                });
                return;
            }

            // Get tax rate
            const taxRate = this.taxRatesRepository.getTaxRateByZipCode(zipCode);

            // Get promo code if provided
            let promoCodeObj = undefined;
            if (promoCode) {
                promoCodeObj = this.promoCodesRepository.getPromoCodeByCode(promoCode);
                if (!promoCodeObj || !this.promoCodesRepository.isPromoCodeValid(promoCode)) {
                    res.status(400).json({
                        success: false,
                        message: "Invalid promo code"
                    });
                    return;
                }
            }

            // Calculate final price with tax
            const finalProgram = this.priceCalculationService.calculateFinalPrice(program, promoCodeObj, taxRate);

            res.json({
                success: true,
                data: finalProgram
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: "Failed to apply sales tax"
            });
        }
    };

    /**
     * POST /apply-promo-code
     * Apply promotional discount to program
     */
    applyPromoCode = (req: Request, res: Response): void => {
        try {
            const { promoCode, programId, zipCode } = req.body;

            if (!promoCode || !programId) {
                res.status(400).json({
                    success: false,
                    message: "Promo code and program ID are required"
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

            // Get program
            const program = this.programsRepository.getProgramById(programId);
            if (!program) {
                res.status(404).json({
                    success: false,
                    message: "Program not found"
                });
                return;
            }

            // Get promo code object
            const promoCodeObj = this.promoCodesRepository.getPromoCodeByCode(promoCode);

            // Get tax rate if zip code provided
            let taxRate = undefined;
            if (zipCode) {
                if (!this.taxRatesRepository.isValidZipCode(zipCode)) {
                    res.status(400).json({
                        success: false,
                        message: "Invalid ZIP code"
                    });
                    return;
                }
                taxRate = this.taxRatesRepository.getTaxRateByZipCode(zipCode);
            }

            // Calculate final price with promo code
            const finalProgram = this.priceCalculationService.calculateFinalPrice(program, promoCodeObj, taxRate);

            res.json({
                success: true,
                data: finalProgram
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: "Failed to apply promo code"
            });
        }
    };
}

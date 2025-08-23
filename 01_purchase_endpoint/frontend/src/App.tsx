import React, { useState, useEffect, useCallback } from 'react';
import { Program, CustomerInfo, TaxResponse, PromoCodeValidation } from './types';
import { apiService } from './services/api';
import { PriceCalculationService, CalculatedPrice } from './services/PriceCalculationService';
import { ProgramSelection } from './components/ProgramSelection';
import { BillingAddress } from './components/BillingAddress';
import { PromoCodeInput } from './components/PromoCodeInput';
import { PriceDisplay } from './components/PriceDisplay';
import { PurchaseButton } from './components/PurchaseButton';
import './App.css';

const initialCustomerInfo: CustomerInfo = {
    name: '',
    email: '',
    billingAddress: {
        street: '',
        city: '',
        state: '',
        zipCode: '',
        country: ''
    }
};

function App() {
    // State management
    const [programs, setPrograms] = useState<Program[]>([]);
    const [selectedProgramId, setSelectedProgramId] = useState<string>('');
    const [customerInfo, setCustomerInfo] = useState<CustomerInfo>(initialCustomerInfo);
    const [promoCode, setPromoCode] = useState<string>('');
    const [currentProgram, setCurrentProgram] = useState<Program | null>(null);
    
    // New state for the "worse" architecture
    const [promoCodeValidation, setPromoCodeValidation] = useState<PromoCodeValidation | null>(null);
    const [taxResponse, setTaxResponse] = useState<TaxResponse | null>(null);
    const [calculatedPrice, setCalculatedPrice] = useState<CalculatedPrice | null>(null);
    
    // Loading and error states
    const [isLoadingPrograms, setIsLoadingPrograms] = useState(true);
    const [isLoadingCalculations, setIsLoadingCalculations] = useState(false);
    const [isLoadingPurchase, setIsLoadingPurchase] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    // Initialize price calculation service
    const priceCalculationService = new PriceCalculationService();

    // Load programs on component mount
    useEffect(() => {
        loadPrograms();
    }, []);

    // Recalculate price whenever relevant data changes
    useEffect(() => {
        if (currentProgram) {
            recalculatePrice();
        }
    }, [currentProgram, promoCodeValidation, taxResponse]);

    // Load programs from backend
    const loadPrograms = async () => {
        try {
            setIsLoadingPrograms(true);
            const programsData = await apiService.getPrograms();
            setPrograms(programsData);
            if (programsData.length > 0) {
                setSelectedProgramId(programsData[0].id);
                setCurrentProgram(programsData[0]);
            }
        } catch (err) {
            setError('Failed to load programs');
            console.error('Error loading programs:', err);
        } finally {
            setIsLoadingPrograms(false);
        }
    };

    // Handle program selection
    const handleProgramSelect = useCallback((programId: string) => {
        setSelectedProgramId(programId);
        const program = programs.find(p => p.id === programId);
        if (program) {
            // Reset calculations when program changes
            setCurrentProgram(program);
            setPromoCodeValidation(null);
            setTaxResponse(null);
            setCalculatedPrice(null);
            setError(null);
            setSuccess(null);
        }
    }, [programs]);

    // Handle customer info changes
    const handleCustomerInfoChange = useCallback((newCustomerInfo: CustomerInfo) => {
        setCustomerInfo(newCustomerInfo);
        
        // Auto-apply tax when zip code changes
        if (newCustomerInfo.billingAddress.zipCode.length === 5 && currentProgram) {
            applySalesTax(currentProgram.basePrice, newCustomerInfo.billingAddress.zipCode);
        }
    }, [currentProgram]);

    // Apply sales tax using the new API
    const applySalesTax = async (price: number, zipCode: string) => {
        try {
            setIsLoadingCalculations(true);
            setError(null);
            
            const request = {
                price,
                zipCode
            };
            
            const response = await apiService.applySalesTax(request);
            setTaxResponse(response);
        } catch (err) {
            setError('Failed to calculate tax');
            console.error('Error applying sales tax:', err);
        } finally {
            setIsLoadingCalculations(false);
        }
    };

    // Validate promo code using the new API
    const handleValidatePromoCode = async () => {
        if (!promoCode.trim()) return;
        
        try {
            setIsLoadingCalculations(true);
            setError(null);
            
            const request = {
                promoCode: promoCode.trim()
            };
            
            const response = await apiService.validatePromoCode(request);
            setPromoCodeValidation(response);
            setSuccess('Promo code validated successfully!');
        } catch (err) {
            setError('Invalid promo code');
            console.error('Error validating promo code:', err);
        } finally {
            setIsLoadingCalculations(false);
        }
    };

    // Recalculate price using hardcoded business logic
    const recalculatePrice = () => {
        if (!currentProgram) return;
        
        const calculated = priceCalculationService.calculatePrice(
            currentProgram,
            promoCodeValidation,
            taxResponse
        );
        
        setCalculatedPrice(calculated);
    };

    // Handle purchase
    const handlePurchase = async () => {
        if (!selectedProgramId || !currentProgram) return;
        
        try {
            setIsLoadingPurchase(true);
            setError(null);
            setSuccess(null);
            
            const request = {
                programId: selectedProgramId,
                promoCode: promoCode || undefined,
                customerInfo
            };
            
            const purchaseResponse = await apiService.purchaseProgram(request);
            setSuccess(`Purchase successful! Order ID: ${purchaseResponse.orderId}`);
            
            // Reset form
            setPromoCode('');
            setCustomerInfo(initialCustomerInfo);
            setCurrentProgram(programs.find(p => p.id === selectedProgramId) || null);
            setPromoCodeValidation(null);
            setTaxResponse(null);
            setCalculatedPrice(null);
        } catch (err) {
            setError('Purchase failed. Please try again.');
            console.error('Error processing purchase:', err);
        } finally {
            setIsLoadingPurchase(false);
        }
    };

    // Check if purchase button should be disabled
    const isPurchaseDisabled = !selectedProgramId || 
        !customerInfo.name || 
        !customerInfo.email || 
        !customerInfo.billingAddress.street || 
        !customerInfo.billingAddress.city || 
        !customerInfo.billingAddress.state || 
        !customerInfo.billingAddress.zipCode || 
        !customerInfo.billingAddress.country;

    if (isLoadingPrograms) {
        return (
            <div className="app">
                <div className="loading-container">
                    <h1>Loading Programs...</h1>
                </div>
            </div>
        );
    }

    return (
        <div className="app">
            <header className="app-header">
                <h1>Program Purchase Application</h1>
                <p>Select your program and complete your purchase</p>
            </header>
            
            <main className="app-main">
                <ProgramSelection
                    programs={programs}
                    selectedProgramId={selectedProgramId}
                    onProgramSelect={handleProgramSelect}
                />
                
                <BillingAddress
                    customerInfo={customerInfo}
                    onCustomerInfoChange={handleCustomerInfoChange}
                />
                
                <PromoCodeInput
                    promoCode={promoCode}
                    onPromoCodeChange={setPromoCode}
                    onApplyPromoCode={handleValidatePromoCode}
                    isLoading={isLoadingCalculations}
                    error={error}
                />
                
                <PriceDisplay
                    program={currentProgram}
                    calculatedPrice={calculatedPrice}
                    isLoading={isLoadingCalculations}
                />
                
                <PurchaseButton
                    onPurchase={handlePurchase}
                    disabled={isPurchaseDisabled}
                    isLoading={isLoadingPurchase}
                    error={error}
                    success={success}
                />
            </main>
        </div>
    );
}

export default App;

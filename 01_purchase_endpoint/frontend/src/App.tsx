import React, { useState, useEffect, useCallback } from 'react';
import { Program, ProgramWithCalculations, CustomerInfo } from './types';
import { apiService } from './services/api';
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
    const [currentProgram, setCurrentProgram] = useState<ProgramWithCalculations | null>(null);
    
    // Loading and error states
    const [isLoadingPrograms, setIsLoadingPrograms] = useState(true);
    const [isLoadingCalculations, setIsLoadingCalculations] = useState(false);
    const [isLoadingPurchase, setIsLoadingPurchase] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    // Load programs on component mount
    useEffect(() => {
        loadPrograms();
    }, []);

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
            setError(null);
            setSuccess(null);
        }
    }, [programs]);

    // Handle customer info changes
    const handleCustomerInfoChange = useCallback((newCustomerInfo: CustomerInfo) => {
        setCustomerInfo(newCustomerInfo);
        
        // Auto-apply tax when zip code changes
        if (newCustomerInfo.billingAddress.zipCode.length === 5 && selectedProgramId) {
            applySalesTax(newCustomerInfo.billingAddress.zipCode);
        }
    }, [selectedProgramId]);

    // Apply sales tax
    const applySalesTax = async (zipCode: string) => {
        if (!selectedProgramId) return;
        
        try {
            setIsLoadingCalculations(true);
            setError(null);
            
            const request = {
                zipCode,
                programId: selectedProgramId,
                promoCode: promoCode || undefined
            };
            
            const updatedProgram = await apiService.applySalesTax(request);
            setCurrentProgram(updatedProgram);
        } catch (err) {
            setError('Failed to calculate tax');
            console.error('Error applying sales tax:', err);
        } finally {
            setIsLoadingCalculations(false);
        }
    };

    // Apply promo code
    const handleApplyPromoCode = async () => {
        if (!selectedProgramId || !promoCode.trim()) return;
        
        try {
            setIsLoadingCalculations(true);
            setError(null);
            
            const request = {
                promoCode: promoCode.trim(),
                programId: selectedProgramId,
                zipCode: customerInfo.billingAddress.zipCode || undefined
            };
            
            const updatedProgram = await apiService.applyPromoCode(request);
            setCurrentProgram(updatedProgram);
            setSuccess('Promo code applied successfully!');
        } catch (err) {
            setError('Invalid promo code');
            console.error('Error applying promo code:', err);
        } finally {
            setIsLoadingCalculations(false);
        }
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
                    onApplyPromoCode={handleApplyPromoCode}
                    isLoading={isLoadingCalculations}
                    error={error}
                />
                
                <PriceDisplay
                    program={currentProgram}
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

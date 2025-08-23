import React from 'react';
import { Program } from '../types';
import { CalculatedPrice } from '../services/PriceCalculationService';
import './PriceDisplay.css';

interface PriceDisplayProps {
    program: Program | null;
    calculatedPrice: CalculatedPrice | null;
    isLoading: boolean;
}

export const PriceDisplay: React.FC<PriceDisplayProps> = ({ program, calculatedPrice, isLoading }) => {
    if (!program) {
        return (
            <div className="price-display">
                <h2>Price Summary</h2>
                <div className="price-placeholder">
                    Select a program to see pricing
                </div>
            </div>
        );
    }

    if (!calculatedPrice) {
        return (
            <div className="price-display">
                <h2>Price Summary</h2>
                <div className="price-breakdown">
                    <div className="price-row">
                        <span>Subtotal:</span>
                        <span>${program.basePrice.toFixed(2)}</span>
                    </div>
                    <div className="price-placeholder">
                        Enter ZIP code or promo code to see calculations
                    </div>
                </div>
            </div>
        );
    }

    const hasDiscount = calculatedPrice.discountAmount > 0;
    const hasTax = calculatedPrice.taxAmount > 0;

    return (
        <div className="price-display">
            <h2>Price Summary</h2>
            <div className="price-breakdown">
                <div className="price-row">
                    <span>Subtotal:</span>
                    <span>${calculatedPrice.basePrice.toFixed(2)}</span>
                </div>
                
                {hasDiscount && (
                    <div className="price-row discount">
                        <span>Discount:</span>
                        <span>-${calculatedPrice.discountAmount.toFixed(2)}</span>
                    </div>
                )}
                
                {hasDiscount && (
                    <div className="price-row">
                        <span>Price after discount:</span>
                        <span>${calculatedPrice.priceAfterDiscount.toFixed(2)}</span>
                    </div>
                )}
                
                {hasTax && (
                    <div className="price-row tax">
                        <span>Tax ({(calculatedPrice.taxRate * 100).toFixed(1)}%):</span>
                        <span>${calculatedPrice.taxAmount.toFixed(2)}</span>
                    </div>
                )}
                
                <div className="price-row total">
                    <span>Total:</span>
                    <span>${calculatedPrice.finalPrice.toFixed(2)}</span>
                </div>
            </div>
            
            {isLoading && (
                <div className="loading-indicator">
                    Calculating...
                </div>
            )}
        </div>
    );
};

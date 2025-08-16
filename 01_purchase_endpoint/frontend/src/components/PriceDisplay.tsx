import React from 'react';
import { ProgramWithCalculations } from '../types';
import './PriceDisplay.css';

interface PriceDisplayProps {
    program: ProgramWithCalculations | null;
    isLoading: boolean;
}

export const PriceDisplay: React.FC<PriceDisplayProps> = ({ program, isLoading }) => {
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

    const hasTax = program.taxAmount && program.taxAmount > 0;
    const hasDiscount = program.discountAmount && program.discountAmount > 0;

    return (
        <div className="price-display">
            <h2>Price Summary</h2>
            <div className="price-breakdown">
                <div className="price-row">
                    <span>Subtotal:</span>
                    <span>${program.basePrice.toFixed(2)}</span>
                </div>
                
                {hasDiscount && (
                    <div className="price-row discount">
                        <span>Discount:</span>
                        <span>-${program.discountAmount!.toFixed(2)}</span>
                    </div>
                )}
                
                {hasTax && (
                    <div className="price-row tax">
                        <span>Tax ({((program.taxRate || 0) * 100).toFixed(1)}%):</span>
                        <span>${program.taxAmount!.toFixed(2)}</span>
                    </div>
                )}
                
                <div className="price-row total">
                    <span>Total:</span>
                    <span>${(program.finalPrice || program.basePrice).toFixed(2)}</span>
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

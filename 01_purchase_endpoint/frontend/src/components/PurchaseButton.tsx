import React from 'react';
import './PurchaseButton.css';

interface PurchaseButtonProps {
    onPurchase: () => void;
    disabled: boolean;
    isLoading: boolean;
    error: string | null;
    success: string | null;
}

export const PurchaseButton: React.FC<PurchaseButtonProps> = ({
    onPurchase,
    disabled,
    isLoading,
    error,
    success
}) => {
    return (
        <div className="purchase-section">
            <button
                className="purchase-button"
                onClick={onPurchase}
                disabled={disabled || isLoading}
            >
                {isLoading ? 'Processing...' : 'Complete Purchase'}
            </button>
            
            {error && (
                <div className="error-message">
                    {error}
                </div>
            )}
            
            {success && (
                <div className="success-message">
                    {success}
                </div>
            )}
        </div>
    );
};

import React, { useState } from 'react';
import './PromoCodeInput.css';

interface PromoCodeInputProps {
    promoCode: string;
    onPromoCodeChange: (promoCode: string) => void;
    onApplyPromoCode: () => void;
    isLoading: boolean;
    error: string | null;
}

export const PromoCodeInput: React.FC<PromoCodeInputProps> = ({
    promoCode,
    onPromoCodeChange,
    onApplyPromoCode,
    isLoading,
    error
}) => {
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (promoCode.trim()) {
            onApplyPromoCode();
        }
    };

    return (
        <div className="promo-code-input">
            <h2>Promotional Code</h2>
            <form onSubmit={handleSubmit}>
                <div className="promo-input-group">
                    <input
                        type="text"
                        value={promoCode}
                        onChange={(e) => onPromoCodeChange(e.target.value)}
                        placeholder="Enter promo code"
                        disabled={isLoading}
                    />
                    <button
                        type="submit"
                        disabled={!promoCode.trim() || isLoading}
                        className="apply-button"
                    >
                        {isLoading ? 'Applying...' : 'Apply'}
                    </button>
                </div>
                {error && <div className="error-message">{error}</div>}
            </form>
        </div>
    );
};

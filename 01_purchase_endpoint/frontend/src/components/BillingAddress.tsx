import React from 'react';
import { CustomerInfo } from '../types';
import './BillingAddress.css';

interface BillingAddressProps {
    customerInfo: CustomerInfo;
    onCustomerInfoChange: (customerInfo: CustomerInfo) => void;
}

export const BillingAddress: React.FC<BillingAddressProps> = ({
    customerInfo,
    onCustomerInfoChange
}) => {
    const handleChange = (field: keyof CustomerInfo, value: string) => {
        onCustomerInfoChange({
            ...customerInfo,
            [field]: value
        });
    };

    const handleAddressChange = (field: keyof CustomerInfo['billingAddress'], value: string) => {
        onCustomerInfoChange({
            ...customerInfo,
            billingAddress: {
                ...customerInfo.billingAddress,
                [field]: value
            }
        });
    };

    return (
        <div className="billing-address">
            <h2>Billing Information</h2>
            <div className="form-row">
                <div className="form-group">
                    <label htmlFor="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        value={customerInfo.name}
                        onChange={(e) => handleChange('name', e.target.value)}
                        placeholder="Enter your full name"
                        required
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        value={customerInfo.email}
                        onChange={(e) => handleChange('email', e.target.value)}
                        placeholder="Enter your email"
                        required
                    />
                </div>
            </div>
            <div className="form-group">
                <label htmlFor="street">Street Address</label>
                <input
                    type="text"
                    id="street"
                    value={customerInfo.billingAddress.street}
                    onChange={(e) => handleAddressChange('street', e.target.value)}
                    placeholder="Enter street address"
                    required
                />
            </div>
            <div className="form-row">
                <div className="form-group">
                    <label htmlFor="city">City</label>
                    <input
                        type="text"
                        id="city"
                        value={customerInfo.billingAddress.city}
                        onChange={(e) => handleAddressChange('city', e.target.value)}
                        placeholder="Enter city"
                        required
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="state">State</label>
                    <input
                        type="text"
                        id="state"
                        value={customerInfo.billingAddress.state}
                        onChange={(e) => handleAddressChange('state', e.target.value)}
                        placeholder="Enter state"
                        required
                    />
                </div>
            </div>
            <div className="form-row">
                <div className="form-group">
                    <label htmlFor="zipCode">ZIP Code</label>
                    <input
                        type="text"
                        id="zipCode"
                        value={customerInfo.billingAddress.zipCode}
                        onChange={(e) => handleAddressChange('zipCode', e.target.value)}
                        placeholder="Enter ZIP code"
                        pattern="[0-9]{5}"
                        maxLength={5}
                        required
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="country">Country</label>
                    <input
                        type="text"
                        id="country"
                        value={customerInfo.billingAddress.country}
                        onChange={(e) => handleAddressChange('country', e.target.value)}
                        placeholder="Enter country"
                        required
                    />
                </div>
            </div>
        </div>
    );
};

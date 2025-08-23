# Specification for mini-application for teaching

## Overview
This application demonstrates frontend-backend integration with real-time pricing calculations, AJAX calls, and mock database operations. It's designed for educational purposes to teach web development concepts including API design, frontend state management, and backend business logic.

## Application Purpose
A single-page web application where users can purchase programs with dynamic pricing that updates in real-time based on zip code (sales tax) and promotional codes (discounts).

## Technical Stack Requirements

### Frontend
- **Framework**: React, using typescript
- **Styling**: CSS3, no libraries like styled components, no tailwind. Keep all styles in css files, out of jsx files
- **HTTP Client**: Fetch API
- **State Management**: Local state management (no external state libraries required)

### Backend
- **Runtime**: Node.js with Express.js
- **Language**: TypeScript
- **Database**: In-memory array (mock database)
- **Port**: 3000 (configurable)

## Application Architecture

### Frontend Structure
```
Single Page Application, only one route:
-  Program Selection (Radio buttons) - the programs are all the same but different durations 1 month, 3 months, or 6 months
- Billing address input
- Promo Code Input
- Price Display
   ├── Subtotal
   ├── Tax (if applicable)
   ├── Discount (if applicable)
   └── Total
- Purchase Button
```

### Backend Structure
```
Express Server
├── REST API Endpoints
├── Business Logic Services
|   ├── Tax Calculation
|   ├── Promo Code Validation
|   └── Price Calculation
├── Repositories
|   ├── Programs repository - interacts with the Programs model
|   ├── Tax rates repository - interacts with the TaxRate model
└── Models
    ├── Program - pulls program by id from an in memory array (mock db)
    ├── PromoCodes - in memory array of promocodes, you can fetch them by the actual promocode (eg SAVE10)
    └── Tax rate - tax rates can be looked up by zip code, return the last numerical digit of the zip code as the tax rate, converted to percentage
```

## API Specification

### 1. GET /list-programs
**Purpose**: Retrieve available programs for purchase

**Response Format**:
```json
{
  "success": true,
  "data": [
    {
      "id": "prog_001",
      "name": "App Membership",
      "basePrice": 99.99,
      "duration_months": "1"
    },
    {
      "id": "prog_002",
      "name": "App Membership",
      "basePrice": 199.99,
      "duration_months": "2"
    },
    {
      "id": "prog_003",
      "name": "App Membership",
      "basePrice": 349.99,
      "duration_months": "6"
    }
  ]
}
```

### 2. POST /purchase-program
**Purpose**: Process program purchase

**Request Body**:
```json
{
  "programId": "prog_001",
  "promoCode": "SAVE20",
  "customerInfo": {
    "name": "John Doe",
    "email": "john@example.com",
    "billingAddress": {...}
  }
}
```

**Response Format**:
```json
{
  "success": true,
  "data": {
    "orderId": "ord_12345",
    "program": {
      "id": "prog_001",
      "name": "Web Development Bootcamp",
      "basePrice": 299.99,
      "finalPrice": 287.99,
      "taxAmount": 23.99,
      "discountAmount": 36.00
    },
    "total": 287.99,
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

### 3. POST /apply-sales-tax
**Purpose**: Calculate and apply sales tax based on zip code and price

**Request Body**:
```json
{
  "price": 299.99,
  "zipCode": "10001"
}
```

**Response Format**:
```json
{
  "success": true,
  "data": {
    "originalPrice": 299.99,
    "taxRate": 0.08,
    "taxAmount": 23.99,
    "newPrice": 323.98
  }
}
```

**Business Logic**:
- Tax rates vary by zip code (generated mock data, just return the last numerical digit of the zip code as the tax rate, converted to percentage)
- Return the new price after tax calculation

### 4. POST /validate-promo-code
**Purpose**: Validate promotional discount code

**Request Body**:
```json
{
  "promoCode": "SAVE20"
}
```

**Response Format**:
```json
{
  "success": true,
  "data": {
    "code": "SAVE20",
    "discountType": "percentage",
    "discountValue": 0.20,
    "isValid": true
  }
}
```

**Business Logic**:
- Validate promo code against mock database
- Return promo code details if valid
- Return error if invalid

## Frontend Business Logic (Hardcoded)

The frontend will handle the sequence of applying discounts and taxes in a hardcoded manner:

1. **Discount First**: Always apply promo code discount to base price first
2. **Tax Second**: Always apply tax to the discounted price
3. **Calculation Order**: Base Price → Discount → Tax → Final Price

This logic is hardcoded in the frontend and cannot be changed without modifying the frontend code.

## Mock Database Structure

### Programs Table
```javascript
const programs = [
  //same programs from above
];
```

### Promo Codes Table
```javascript
const promoCodes = [
  {
    code: "SAVE20",
    discountType: "percentage",
    discountValue: 0.20,
    validFrom: "2024-01-01",
    validTo: "2024-12-31",
    maxUses: 1000,
    currentUses: 150
  },
  {
    code: "FLAT50",
    discountType: "fixed",
    discountValue: 50.00,
    validFrom: "2024-01-01",
    validTo: "2024-12-31",
    maxUses: 500,
    currentUses: 75
  }
];
```

### Tax Rates Table
Don't use a table, just pick the last digit of the postal/zip code and return that as the tax rate

## Frontend Requirements

### User Interface Elements
1. **Program Selection Section**
   - Radio button list populated from `/list-programs`
   - Display program name, description, and base price
   - Auto-select first program by default

2. **Zip Code Input**
   - Text input with placeholder "Enter ZIP code"
   - Real-time validation (5 digits)
   - AJAX call to `/apply-sales-tax` on blur/change

3. **Promo Code Input**
   - Text input with placeholder "Enter promo code"
   - Apply button
   - AJAX call to `/validate-promo-code` on button click

4. **Price Breakdown**
   - Subtotal (base price)
   - Tax amount (if zip code entered)
   - Discount amount (if promo code applied)
   - Total (final price after all calculations)

5. **Purchase Button**
   - Disabled until program selected
   - AJAX call to `/purchase-program` on click
   - Show success/error messages

### Real-time Updates
- **Zip Code Changes**: Immediately call `/apply-sales-tax` and update display
- **Promo Code Application**: Call `/validate-promo-code` and update display
- **Program Selection**: Reset all calculations to base price
- **Error Handling**: Display user-friendly error messages for invalid inputs

### State Management
- Track selected program
- Track entered zip code
- Track applied promo code
- Track current pricing calculations
- Handle loading states during AJAX calls

## Backend Requirements

### Server Setup
- Express.js server on port 3000
- CORS enabled for frontend communication
- JSON body parsing middleware
- Error handling middleware

### Business Logic Services

#### Tax Calculation Service
- Lookup tax rate by zip code
- Calculate tax on the provided price
- Return new price with tax applied

#### Promo Code Service
- Validate promo code exists and is active
- Check usage limits
- Return promo code details for frontend calculation


### Error Handling
- Invalid program IDs: 404 with descriptive message
- Invalid zip codes: 400 with "Invalid ZIP code" message
- Invalid promo codes: 400 with "Invalid promo code" message
- Server errors: 500 with generic error message

## Implementation Guidelines

### Code Quality
- Use meaningful variable and function names
- Implement proper error handling
- Add input validation on both frontend and backend
- Use consistent code formatting (spaces, not tabs)
- Add comments for complex business logic

### Testing Considerations
- Test all API endpoints with valid and invalid data
- Test edge cases (zero prices, maximum discounts)
- Test concurrent requests
- Test error scenarios

### Performance Considerations
- Debounce zip code input (300ms delay)
- Cache tax rates and promo codes in memory
- Optimize database lookups
- Handle multiple simultaneous requests

## Expected User Experience

1. **Page Load**: Programs load automatically, first program selected
2. **Program Selection**: User can change selection, prices reset to base
3. **Zip Code Entry**: Tax calculated and applied in real-time
4. **Promo Code**: Discount applied and total recalculated
5. **Purchase**: Order processed with confirmation message

## Success Criteria
- [ ] Frontend loads and displays programs from backend
- [ ] Real-time price updates work for zip code changes
- [ ] Real-time price updates work for promo code application
- [ ] All API endpoints return correct data structures
- [ ] Error handling works for invalid inputs
- [ ] Code is well-structured and commented
- [ ] Application handles edge cases gracefully
- [ ] Frontend hardcodes the discount-then-tax calculation sequence

This specification provides a comprehensive foundation for building an educational application that demonstrates modern web development concepts while maintaining simplicity for learning purposes.

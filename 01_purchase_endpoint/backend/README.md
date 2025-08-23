# Backend - Program Purchase Application

This is the backend server for the Program Purchase Application, built with Node.js, Express, and TypeScript.

## Features

- **RESTful API** with 4 endpoints as specified
- **Mock Database** using in-memory arrays
- **Simplified Business Logic** - frontend handles complex calculations
- **Repository Pattern** for data access
- **TypeScript** for type safety
- **Error Handling** with proper HTTP status codes

## API Endpoints

### 1. GET /list-programs
Retrieves all available programs for purchase.

### 2. POST /purchase-program
Processes a program purchase with customer information.

### 3. POST /apply-sales-tax
Calculates and applies sales tax based on price and ZIP code.

**Request Body**:
```json
{
  "price": 299.99,
  "zipCode": "10001"
}
```

**Response**:
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

### 4. POST /validate-promo-code
Validates promotional discount codes.

**Request Body**:
```json
{
  "promoCode": "SAVE20"
}
```

**Response**:
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

## Installation

1. Navigate to the backend directory:
   ```bash
   cd backend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Build the TypeScript code:
   ```bash
   npm run build
   ```

## Running the Application

### Development Mode
```bash
npm run dev
```

### Production Mode
```bash
npm run build
npm start
```

The server will start on port 3000 (configurable via PORT environment variable).

## Project Structure

```
src/
├── controllers/     # API endpoint handlers
├── models/         # Data models and mock data
├── repositories/   # Data access layer
├── services/       # Simplified business logic
└── index.ts        # Server entry point
```

## Mock Data

- **Programs**: 3 membership options (1, 3, and 6 months)
- **Promo Codes**: SAVE20 (20% off) and FLAT50 ($50 off)
- **Tax Rates**: Generated from ZIP code last digit (e.g., 12345 → 5% tax)

## Business Logic

**Note**: This backend is intentionally simplified for educational purposes. The frontend now handles the complex business logic including:
- Discount application sequence
- Tax calculation on discounted prices
- Final price calculations

The backend only:
- Provides tax rates based on ZIP codes
- Validates promo codes
- Stores program data
- Processes purchases

## Testing

Test the API endpoints using tools like Postman or curl:

```bash
# Get programs
curl http://localhost:3000/list-programs

# Apply sales tax
curl -X POST http://localhost:3000/apply-sales-tax \
  -H "Content-Type: application/json" \
  -d '{"price":99.99,"zipCode":"12345"}'

# Validate promo code
curl -X POST http://localhost:3000/validate-promo-code \
  -H "Content-Type: application/json" \
  -d '{"promoCode":"SAVE20"}'
```

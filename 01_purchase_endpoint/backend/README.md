# Backend - Program Purchase Application

This is the backend server for the Program Purchase Application, built with Node.js, Express, and TypeScript.

## Features

- **RESTful API** with 4 endpoints as specified
- **Mock Database** using in-memory arrays
- **Business Logic Services** for tax calculation, promo code validation, and price calculation
- **Repository Pattern** for data access
- **TypeScript** for type safety
- **Error Handling** with proper HTTP status codes

## API Endpoints

### 1. GET /list-programs
Retrieves all available programs for purchase.

### 2. POST /purchase-program
Processes a program purchase with customer information.

### 3. POST /apply-sales-tax
Calculates and applies sales tax based on ZIP code.

### 4. POST /apply-promo-code
Applies promotional discounts to programs.

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
├── services/       # Business logic
└── index.ts        # Server entry point
```

## Mock Data

- **Programs**: 3 membership options (1, 3, and 6 months)
- **Promo Codes**: SAVE20 (20% off) and FLAT50 ($50 off)
- **Tax Rates**: Generated from ZIP code last digit (e.g., 12345 → 5% tax)

## Business Logic

- Tax is calculated on the discounted price if a promo code is applied
- Promo codes are validated for expiration and usage limits
- All monetary values are rounded to 2 decimal places
- Discounts cannot exceed the base price

## Testing

Test the API endpoints using tools like Postman or curl:

```bash
# Get programs
curl http://localhost:3000/list-programs

# Apply sales tax
curl -X POST http://localhost:3000/apply-sales-tax \
  -H "Content-Type: application/json" \
  -d '{"zipCode":"12345","programId":"prog_001"}'

# Apply promo code
curl -X POST http://localhost:3000/apply-promo-code \
  -H "Content-Type: application/json" \
  -d '{"promoCode":"SAVE20","programId":"prog_001"}'
```

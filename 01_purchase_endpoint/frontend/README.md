# Frontend - Program Purchase Application

This is the frontend application for the Program Purchase Application, built with React, TypeScript, and Vite.

## Features

- **Single Page Application** with real-time price updates
- **Program Selection** via radio buttons (1, 3, and 6 month options)
- **Billing Address Form** with validation
- **Promo Code Input** with real-time application
- **Dynamic Pricing** that updates based on ZIP code and promo codes
- **Responsive Design** that works on all device sizes
- **TypeScript** for type safety and better development experience
- **Vite** for fast development and building

## Components

- **ProgramSelection**: Radio button list for program selection
- **BillingAddress**: Form for customer billing information
- **PromoCodeInput**: Input field for promotional codes
- **PriceDisplay**: Real-time price breakdown display
- **PurchaseButton**: Final purchase action button

## Installation

1. Navigate to the frontend directory:
   ```bash
   cd frontend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

## Running the Application

### Development Mode
```bash
npm run dev
```

The application will open in your browser at `http://localhost:5173` (Vite's default port).

### Production Build
```bash
npm run build
```

### Preview Production Build
```bash
npm run preview
```

## Features in Action

### Real-time Price Updates
- **ZIP Code Changes**: Automatically calculates and applies sales tax
- **Promo Code Application**: Immediately applies discounts and recalculates totals
- **Program Selection**: Resets all calculations to base pricing

### Form Validation
- Required field validation for all billing information
- ZIP code format validation (5 digits)
- Email format validation
- Real-time visual feedback for valid/invalid inputs

### User Experience
- Loading states during API calls
- Error messages for invalid inputs
- Success messages for successful actions
- Responsive design for mobile and desktop

## API Integration

The frontend communicates with the backend through the following endpoints:

- `GET /list-programs` - Loads available programs
- `POST /apply-sales-tax` - Calculates tax when ZIP code changes
- `POST /apply-promo-code` - Applies promotional discounts
- `POST /purchase-program` - Processes final purchase

**Note**: The frontend uses Vite's proxy configuration to forward API requests to the backend at `http://localhost:3000`.

## State Management

The application uses React's built-in state management:

- **Programs**: List of available programs from backend
- **Selected Program**: Currently selected program
- **Customer Info**: Billing address and contact information
- **Promo Code**: Applied promotional code
- **Current Program**: Program with all calculations applied
- **Loading States**: For various API operations
- **Error/Success Messages**: User feedback

## Styling

- **CSS-only styling** (no external libraries like Tailwind or styled-components)
- **Responsive design** with mobile-first approach
- **Modern UI** with hover effects and transitions
- **Accessible** with proper contrast and focus states

## Browser Support

- Modern browsers with ES6+ support
- React 18+ features
- CSS Grid and Flexbox for layout

## Development

The application is set up with:

- **TypeScript** for type safety
- **Vite** for fast development and building
- **ESLint** for code quality
- **Proxy configuration** for backend communication
- **Hot module replacement** for fast development

## Vite Benefits

- **Fast Development**: Instant hot module replacement
- **Fast Building**: Optimized build process
- **Modern Tooling**: ES modules and modern JavaScript features
- **Plugin System**: Rich ecosystem of plugins
- **TypeScript Support**: First-class TypeScript support

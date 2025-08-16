# Program Purchase Application

A complete full-stack web application demonstrating frontend-backend integration with real-time pricing calculations, AJAX calls, and mock database operations. Built for educational purposes to teach web development concepts.

## 🚀 Features

- **Frontend**: React + TypeScript single-page application with Vite
- **Backend**: Node.js + Express + TypeScript REST API
- **Real-time Updates**: Dynamic pricing based on ZIP code and promo codes
- **Mock Database**: In-memory data storage for demonstration
- **Responsive Design**: Works on all device sizes
- **Type Safety**: Full TypeScript implementation

## 📁 Project Structure

```
01_purchase_endpoint/
├── backend/                 # Node.js + Express backend
│   ├── src/
│   │   ├── controllers/    # API endpoint handlers
│   │   ├── models/         # Data models and mock data
│   │   ├── repositories/   # Data access layer
│   │   ├── services/       # Business logic
│   │   └── index.ts        # Server entry point
│   ├── package.json
│   └── README.md
├── frontend/                # React + TypeScript frontend with Vite
│   ├── src/
│   │   ├── components/     # React components
│   │   ├── services/       # API service layer
│   │   ├── types/          # TypeScript type definitions
│   │   ├── App.tsx         # Main application component
│   │   └── index.tsx       # Application entry point
│   ├── public/             # Static assets
│   ├── package.json
│   ├── vite.config.ts      # Vite configuration
│   └── README.md
└── vibes.md                 # Original specification
```

## 🛠️ Quick Start

### Prerequisites
- Node.js (v16 or higher)
- npm or yarn

### 1. Start the Backend

```bash
cd backend
npm install
npm run dev
```

The backend will start on `http://localhost:3000`

### 2. Start the Frontend

In a new terminal:

```bash
cd frontend
npm install
npm run dev
```

The frontend will open in your browser at `http://localhost:5173` (Vite's default port).

## 🔌 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/list-programs` | Get all available programs |
| POST | `/purchase-program` | Process program purchase |
| POST | `/apply-sales-tax` | Calculate tax based on ZIP code |
| POST | `/apply-promo-code` | Apply promotional discount |

## 💰 How It Works

1. **Program Selection**: Choose from 1, 3, or 6-month membership options
2. **Billing Information**: Enter customer details and billing address
3. **Real-time Tax Calculation**: Tax is automatically calculated when ZIP code is entered
4. **Promo Code Application**: Apply promotional codes for instant discounts
5. **Dynamic Pricing**: See real-time updates to subtotal, tax, discount, and total
6. **Purchase Completion**: Complete the purchase with all information

## 🎯 Educational Value

This application demonstrates:

- **API Design**: RESTful endpoints with proper HTTP methods
- **Frontend-Backend Integration**: AJAX calls and real-time updates
- **State Management**: React hooks for application state
- **Business Logic**: Tax calculations, discount applications, and validation
- **Error Handling**: Proper error responses and user feedback
- **Type Safety**: TypeScript interfaces and type checking
- **Code Organization**: Separation of concerns with controllers, services, and repositories
- **Modern Build Tools**: Vite for fast development and building

## 🧪 Testing the Application

### Test Promo Codes
- `SAVE20` - 20% discount
- `FLAT50` - $50 flat discount

### Test ZIP Codes
- Any 5-digit ZIP code (last digit becomes tax rate percentage)
- Example: `12345` = 5% tax, `90210` = 0% tax

## 📱 Responsive Design

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🔧 Development

### Backend Development
```bash
cd backend
npm run dev          # Start with hot reload
npm run build        # Build for production
npm run watch        # Watch mode for TypeScript
```

### Frontend Development
```bash
cd frontend
npm run dev          # Start development server with Vite
npm run build        # Build for production
npm run preview      # Preview production build
npm run lint         # Run ESLint
```

## 🚀 Deployment

### Backend Deployment
1. Build the TypeScript: `npm run build`
2. Start the server: `npm start`
3. Set environment variables as needed

### Frontend Deployment
1. Build the application: `npm run build`
2. Deploy the `dist` folder to your hosting service
3. Update API endpoint URLs if needed

## 📚 Learning Resources

This application covers:
- Modern JavaScript/TypeScript
- React hooks and functional components
- Express.js server development
- RESTful API design
- Frontend-backend communication
- State management patterns
- CSS styling and responsive design
- Vite build tooling and configuration

## 🤝 Contributing

This is an educational project. Feel free to:
- Experiment with the code
- Add new features
- Improve the UI/UX
- Enhance the business logic
- Add tests and validation

## 📄 License

This project is for educational purposes. Use it to learn and improve your web development skills!

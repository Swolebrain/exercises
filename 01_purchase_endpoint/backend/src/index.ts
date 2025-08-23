import express from 'express';
import cors from 'cors';
import { ProgramsController } from './controllers/ProgramsController';

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Initialize controller
const programsController = new ProgramsController();

// Routes
app.get('/list-programs', programsController.listPrograms);
app.post('/purchase-program', programsController.purchaseProgram);
app.post('/apply-sales-tax', programsController.applySalesTax);
app.post('/validate-promo-code', programsController.validatePromoCode);

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'OK', timestamp: new Date().toISOString() });
});

// Error handling middleware
app.use((err: Error, req: express.Request, res: express.Response, next: express.NextFunction) => {
    console.error('Error:', err);
    res.status(500).json({
        success: false,
        message: 'Internal server error'
    });
});

// 404 handler
app.use('*', (req, res) => {
    res.status(404).json({
        success: false,
        message: 'Endpoint not found'
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
    console.log(`Health check: http://localhost:${PORT}/health`);
    console.log(`Available endpoints:`);
    console.log(`  GET  /list-programs`);
    console.log(`  POST /purchase-program`);
    console.log(`  POST /apply-sales-tax`);
    console.log(`  POST /validate-promo-code`);
});

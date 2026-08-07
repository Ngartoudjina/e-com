import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import rateLimit from 'express-rate-limit';

import authRoutes from './src/routes/auth.routes.js';
import productRoutes from './src/routes/product.routes.js';
import adminRoutes from './src/routes/admin.routes.js';
import affiliateRoutes from './src/routes/affiliate.routes.js';
import userRoutes from './src/routes/user.routes.js';
import likeRoutes from './src/routes/like.routes.js';
import newsletterRoutes from './src/routes/newsletter.routes.js';
import { authenticateAdmin } from './src/middleware/auth.js';
import { sendBulkEmail, handleUpload } from './src/controllers/admin.controller.js';
import { uploadSingle } from './src/middleware/upload.js';

const app = express();

app.use(helmet());

const corsOptions = {
  origin: [
    'http://localhost:5000',
    'http://localhost:5173',
    'https://e-com-front-b6o1.onrender.com',
  ],
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'x-requested-with'],
};
app.use(cors(corsOptions));

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

const apiLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 300,
  standardHeaders: 'draft-7',
  legacyHeaders: false,
  message: { error: 'Trop de requêtes. Réessayez plus tard.' },
});
app.use('/api', apiLimiter);

const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 30,
  standardHeaders: 'draft-7',
  legacyHeaders: false,
  message: { error: 'Trop de tentatives. Réessayez plus tard.' },
});
app.use('/api/auth', authLimiter);

app.get('/', (req, res) => {
  res.json({ status: 'ok', service: 'E-com API', time: new Date().toISOString() });
});

app.use('/api/auth', authRoutes);
app.use('/api', productRoutes);
app.use('/api/admin', adminRoutes);
app.use('/api', affiliateRoutes);
app.use('/api', userRoutes);
app.use('/api', likeRoutes);
app.use('/api', newsletterRoutes);

app.post('/api/send-bulk-email', authenticateAdmin, sendBulkEmail);
app.post('/api/upload', authenticateAdmin, uploadSingle('image'), handleUpload);

app.use((req, res) => {
  res.status(404).json({ error: 'Route non trouvée' });
});

app.use((error, req, res, next) => {
  console.error('Erreur serveur:', error.message);
  res.status(error.status || 500).json({ error: error.message || 'Erreur interne du serveur' });
});

export default app;

import { Router } from 'express';
import { authenticateAdmin } from '../middleware/auth.js';
import { uploadSingle } from '../middleware/upload.js';
import {
  createProduct,
  listAdminProducts,
  updateProduct,
  deleteProduct,
  listAdminUsers,
  updateUserRole,
  getAnalytics,
  sendBulkEmail,
  handleUpload,
} from '../controllers/admin.controller.js';

const router = Router();

router.use(authenticateAdmin);

router.post('/products', uploadSingle('image'), createProduct);
router.get('/products', listAdminProducts);
router.put('/products/:id', uploadSingle('image'), updateProduct);
router.delete('/products/:id', deleteProduct);

router.get('/users', listAdminUsers);
router.patch('/users/:id/role', updateUserRole);

router.get('/analytics', getAnalytics);

export default router;

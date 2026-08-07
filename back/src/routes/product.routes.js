import { Router } from 'express';
import { listProducts, getProductById, listAllSimple, countProducts } from '../controllers/product.controller.js';

const router = Router();

router.get('/', listProducts);
router.get('/simple-all', listAllSimple);
router.get('/count', countProducts);
router.get('/:id', getProductById);

export default router;

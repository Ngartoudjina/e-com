import { Router } from 'express';
import { authenticateToken } from '../middleware/auth.js';
import { toggleLike, listLikedProducts } from '../controllers/like.controller.js';

const router = Router();

router.post('/likes/toggle', authenticateToken, toggleLike);
router.get('/likes', authenticateToken, listLikedProducts);

export default router;

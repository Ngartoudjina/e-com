import { Router } from 'express';
import { authenticateToken } from '../middleware/auth.js';
import { getProfile, updateProfile } from '../controllers/user.controller.js';

const router = Router();

router.get('/profile', authenticateToken, getProfile);
router.put('/profile', authenticateToken, updateProfile);

export default router;

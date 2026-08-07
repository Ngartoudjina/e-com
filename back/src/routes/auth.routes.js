import { Router } from 'express';
import {
  register,
  login,
  googleLogin,
  verifyEmail,
  resendVerification,
  resetPassword,
  confirmResetPassword,
} from '../controllers/auth.controller.js';

const router = Router();

router.post('/register', register);
router.post('/login', login);
router.post('/google-login', googleLogin);
router.post('/verify-email', verifyEmail);
router.post('/resend-verification', resendVerification);
router.post('/reset-password', resetPassword);
router.post('/confirm-reset-password', confirmResetPassword);

export default router;

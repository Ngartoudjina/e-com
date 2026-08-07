import express from 'express';
import { signup, signin, signinWithGoogle } from '../controllers/authControllers.js';

const router = express.Router();

// Route pour l'inscription
router.post('/signup', signup);

// Route pour la connexion
router.post('/signin', signin);

// Route pour la connexion avec Google
router.post('/signin-google', signinWithGoogle);

export default router;
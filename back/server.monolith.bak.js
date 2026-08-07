import express from 'express';
import multer from 'multer';
import FormData from 'form-data';
import sharp from 'sharp';
import { initializeApp, cert } from 'firebase-admin/app';
import { getAuth } from 'firebase-admin/auth';
import { getFirestore } from 'firebase-admin/firestore';
import dotenv from 'dotenv';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import cors from 'cors';
import { OAuth2Client } from 'google-auth-library';
import { v2 as cloudinary } from 'cloudinary';
import nodemailer from 'nodemailer';
import crypto from 'crypto';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegStatic from 'ffmpeg-static';

// Charger les variables d'environnement
dotenv.config({ path: '.env.local' });

// Vérifier les variables d'environnement requises
const requiredEnvVars = [
  'JWT_SECRET', 
  'CLOUDINARY_CLOUD_NAME', 
  'CLOUDINARY_API_KEY',     
  'CLOUDINARY_API_SECRET',  
  'CLOUDINARY_UPLOAD_PRESET'
];
const missingEnvVars = requiredEnvVars.filter(varName => !process.env[varName]);

if (missingEnvVars.length > 0) {
  console.error('Variables d\'environnement manquantes:', missingEnvVars);
  process.exit(1);
}

// Configuration Cloudinary - AJOUTEZ CETTE SECTION
cloudinary.config({
  cloud_name: process.env.CLOUDINARY_CLOUD_NAME,
  api_key: process.env.CLOUDINARY_API_KEY,
  api_secret: process.env.CLOUDINARY_API_SECRET,
  secure: true
});

// Test de la configuration Cloudinary
console.log('Cloudinary configuré avec:');
console.log('- Cloud Name:', process.env.CLOUDINARY_CLOUD_NAME);
console.log('- API Key:', process.env.CLOUDINARY_API_KEY ? '✓ Défini' : '✗ Manquant');
console.log('- API Secret:', process.env.CLOUDINARY_API_SECRET ? '✓ Défini' : '✗ Manquant');

// Validation email
const validateEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

const app = express();

// Configuration Firebase
let firebaseConfig;

if (process.env.GOOGLE_APPLICATION_CREDENTIALS) {
  // Utiliser le fichier JSON de service account
  firebaseConfig = {
    credential: cert(process.env.GOOGLE_APPLICATION_CREDENTIALS)
  };
} else {
  // Utiliser les variables d'environnement
  firebaseConfig = {
    credential: cert({
      projectId: process.env.FIREBASE_PROJECT_ID,
      privateKey: process.env.FIREBASE_PRIVATE_KEY?.replace(/\\n/g, '\n'),
      clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
    })
  };
}

initializeApp(firebaseConfig);

const db = getFirestore();
const auth = getAuth();

// Tester la connexion Firebase
try {
  console.log('Firebase initialisé avec succès');
  console.log('Project ID:', process.env.FIREBASE_PROJECT_ID || 'Utilisation du fichier JSON');
} catch (error) {
  console.error('Erreur d\'initialisation Firebase:', error);
  process.exit(1);
}

// Configuration Multer pour les uploads d'image
const storage = multer.memoryStorage();
const upload = multer({
  storage: storage,
  limits: { fileSize: 10 * 1024 * 1024 }, // Limite de taille de fichier à 10 Mo
  fileFilter: (req, file, cb) => {
    const allowedTypes = [
      'image/jpeg', 'image/png', 'image/gif', 'image/webp',
      'video/mp4', 'video/mpeg', 'video/quicktime'
    ];
    if (!allowedTypes.includes(file.mimetype)) {
      return cb(new Error('Type de fichier non supporté.'), false);
    }
    cb(null, true);
  }
});

const transporter = nodemailer.createTransport({
  service: 'gmail', // Utilisez un service email (ex. Gmail, ou configurez SMTP)
  auth: {
    user: process.env.EMAIL_USER, // Ajoutez ces variables dans .env.local
    pass: process.env.EMAIL_PASS, // Mot de passe d'application pour Gmail
  },
});

// Configuration Google OAuth
const googleClient = new OAuth2Client(process.env.GOOGLE_CLIENT_ID, process.env.GOOGLE_CLIENT_SECRET);

// Middleware
app.use(express.json());
app.use(cors({
  origin: ['http://localhost:5000', 'http://localhost:5173','https://e-com-front-b6o1.onrender.com',],
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'DELETE'],
  allowedHeaders: ['Content-Type', 'Authorization', 'x-requested-with']
}));

// Middleware d'authentification
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Token d\'accès requis' });
  }

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Token invalide' });
    }
    req.user = user;
    next();
  });
};

// OPTION 1: Système d'authentification avec stockage des mots de passe hashés
// Inscription avec stockage du mot de passe hashé
// Fonction pour générer le template HTML de l'email
function generateEmailTemplate(verificationLink, userName) {
  return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email - E-com</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }
        
        .header-subtitle {
            opacity: 0.9;
            font-size: 16px;
            font-weight: 300;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .welcome-text {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .verification-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .verification-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .verification-text {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .info-section {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .info-title {
            color: #0369a1;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-text {
            color: #0284c7;
            font-size: 14px;
        }
        
        .security-note {
            background-color: #fef7f0;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .security-title {
            color: #ea580c;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .security-text {
            color: #c2410c;
            font-size: 14px;
        }
        
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-text {
            color: #718096;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }
        
        @media (max-width: 640px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .welcome-title {
                font-size: 24px;
            }
            
            .verification-card {
                padding: 20px;
            }
            
            .verify-button {
                padding: 14px 28px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">E-com</div>
            <div class="header-subtitle">Votre marketplace de confiance</div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <h1 class="welcome-title">Bienvenue ${userName} ! 🎉</h1>
            <p class="welcome-text">
                Merci de nous avoir rejoint ! Nous sommes ravis de vous compter parmi nos membres.
                Pour finaliser votre inscription, veuillez vérifier votre adresse email.
            </p>
            
            <div class="verification-card">
                <div class="verification-icon">✉️</div>
                <div class="verification-text">Vérifiez votre adresse email</div>
                <a href="${verificationLink}" class="verify-button">
                    Vérifier mon email
                </a>
            </div>
            
            <div class="info-section">
                <div class="info-title">📋 Pourquoi vérifier votre email ?</div>
                <div class="info-text">
                    La vérification garantit la sécurité de votre compte et vous permet de recevoir 
                    des notifications importantes concernant vos commandes et votre compte.
                </div>
            </div>
            
            <div class="security-note">
                <div class="security-title">🔒 Note de sécurité</div>
                <div class="security-text">
                    Si vous n'avez pas créé de compte sur E-com, vous pouvez ignorer cet email en toute sécurité. 
                    Aucun compte ne sera créé sans votre confirmation.
                </div>
            </div>
            
            <div class="divider"></div>
            
            <p style="text-align: center; color: #718096; font-size: 14px;">
                Ce lien de vérification expire dans 24 heures pour votre sécurité.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Cet email a été envoyé par E-com<br>
                Vous recevez cet email car vous vous êtes inscrit sur notre plateforme.
            </p>
            
            <p style="color: #a0aec0; font-size: 12px; margin-top: 20px;">
                © 2025 E-com. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
  `;
}

// Inscription avec vérification d'email
// Route d'inscription - MODIFIÉE
app.post('/api/register', async (req, res) => {
  try {
    const { email, password, name, firstName, lastName, phone, address } = req.body;
    
    // Validation des données
    if (!email || !password || !name || !firstName || !lastName || !phone || !address) {
      return res.status(400).json({ error: 'Tous les champs sont requis' });
    }
    
    if (!validateEmail(email)) {
      return res.status(400).json({ error: 'Email invalide' });
    }
    
    if (password.length < 6) {
      return res.status(400).json({ error: 'Le mot de passe doit contenir au moins 6 caractères' });
    }
    
    // Vérifier si l'utilisateur existe déjà
    const userQuery = await db.collection('users').where('email', '==', email).get();
    if (!userQuery.empty) {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }
    
    // Hasher le mot de passe
    const hashedPassword = await bcrypt.hash(password, 10);
    
    // Créer l'utilisateur avec Firebase Auth
    const userRecord = await auth.createUser({
      email,
      password,
      displayName: name,
      emailVerified: false,
    });
    
    // Générer un lien de vérification d'email
    const verificationLink = await auth.generateEmailVerificationLink(email);
    
    // Générer le HTML de l'email
    const emailHtml = generateEmailTemplate(verificationLink, `${firstName} ${lastName}`);
    
    // Envoyer l'email de vérification
    await transporter.sendMail({
      from: `"E-com Team 🛍️" <${process.env.EMAIL_USER}>`,
      to: email,
      subject: '✨ Vérifiez votre email pour rejoindre E-com',
      html: emailHtml,
      text: `
        Bonjour ${firstName} ${lastName},
        
        Merci de vous être inscrit sur E-com !
        
        Pour compléter votre inscription, veuillez vérifier votre adresse email en cliquant sur ce lien :
        ${verificationLink}
        
        Ce lien expire dans 24 heures pour votre sécurité.
        
        Si vous n'avez pas créé de compte, ignorez cet email.
        
        Cordialement,
        L'équipe E-com
      `
    });
    
    // ⭐ MODIFICATION ICI : Ajouter isAdmin à pending_users
    await db.collection('pending_users').doc(userRecord.uid).set({
      email,
      name,
      firstName,
      lastName,
      phone,
      address,
      hashedPassword,
      isAdmin: false, // ⭐ PAR DÉFAUT, TOUS LES NOUVEAUX UTILISATEURS NE SONT PAS ADMIN
      createdAt: new Date().toISOString(),
      verificationStatus: 'pending',
    });
    
    // Générer un token JWT temporaire
    const tempToken = jwt.sign(
      { uid: userRecord.uid, emailVerified: false }, 
      process.env.JWT_SECRET, 
      { expiresIn: '24h' }
    );
    
    res.status(200).json({
      message: 'Un email de vérification avec un design amélioré a été envoyé. Veuillez vérifier votre email pour compléter l\'inscription.',
      tempToken,
      uid: userRecord.uid,
    });
    
  } catch (error) {
    console.error('Registration error:', error);
    if (error.code === 'auth/email-already-exists') {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }
    res.status(400).json({ error: error.message });
  }
});

// Connexion avec vérification du mot de passe hashé stocké
app.post('/api/login', async (req, res) => {
  try {
    const { email, password, idToken } = req.body;

    if (idToken) {
      const decodedToken = await auth.verifyIdToken(idToken);
      const uid = decodedToken.uid;

      const userDoc = await db.collection('users').doc(uid).get();
      let userData = userDoc.data();

      if (!userData) {
        const userRecord = await auth.getUser(uid);
        userData = {
          email: userRecord.email,
          name: userRecord.displayName || 'Utilisateur',
          isAdmin: false, // ⭐ AJOUTEZ CETTE LIGNE
          createdAt: new Date().toISOString(),
        };
        await db.collection('users').doc(uid).set(userData);
      }

      const token = jwt.sign(
        { uid, isAdmin: userData.isAdmin || false }, // Inclure isAdmin dans le JWT
        process.env.JWT_SECRET, 
        { expiresIn: '24h' }
      );
      
      return res.status(200).json({
        token,
        user: { 
          uid, 
          email: userData.email, 
          name: userData.name,
          isAdmin: userData.isAdmin || false // ⭐ AJOUTEZ CETTE LIGNE
        },
      });
    } else if (email && password) {
      const userQuery = await db.collection('users').where('email', '==', email).get();
      
      if (userQuery.empty) {
        return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
      }

      const userDoc = userQuery.docs[0];
      const userData = userDoc.data();
      
      const isPasswordValid = await bcrypt.compare(password, userData.password);
      
      if (!isPasswordValid) {
        return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
      }

      const token = jwt.sign(
        { uid: userDoc.id, isAdmin: userData.isAdmin || false }, // Inclure isAdmin
        process.env.JWT_SECRET, 
        { expiresIn: '24h' }
      );
      
      return res.status(200).json({
        token,
        user: { 
          uid: userDoc.id, 
          email: userData.email, 
          name: userData.name,
          isAdmin: userData.isAdmin || false // ⭐ AJOUTEZ CETTE LIGNE
        },
      });
    } else {
      return res.status(400).json({ error: 'Email, mot de passe ou token requis' });
    }
  } catch (error) {
    console.error('Login error:', error);
    if (error.code === 'auth/id-token-expired' || error.code === 'auth/invalid-id-token') {
      return res.status(401).json({ error: 'Token invalide ou expiré' });
    }
    res.status(400).json({ error: 'Erreur de connexion' });
  }
});

// Connexion Google
app.post('/api/google-login', async (req, res) => {
  try {
    const { idToken } = req.body;

    if (!idToken) {
      return res.status(400).json({ error: 'Token Google requis' });
    }

    console.log('Received idToken:', idToken);
    
    const ticket = await googleClient.verifyIdToken({
      idToken,
      audience: process.env.GOOGLE_CLIENT_ID,
    });

    const payload = ticket.getPayload();
    const { email, name, sub: googleId } = payload;

    // Vérifier si l'utilisateur existe
    let userRecord;
    try {
      userRecord = await auth.getUserByEmail(email);
    } catch (error) {
      if (error.code === 'auth/user-not-found') {
        // Créer un nouvel utilisateur
        userRecord = await auth.createUser({
          email,
          displayName: name,
        });
        
        await db.collection('users').doc(userRecord.uid).set({
          email,
          name,
          googleId,
          createdAt: new Date().toISOString(),
        });
      } else {
        throw error;
      }
    }

    const token = jwt.sign({ uid: userRecord.uid }, process.env.JWT_SECRET, { expiresIn: '24h' });
    
    res.status(200).json({ 
      token, 
      user: { uid: userRecord.uid, email, name }
    });
  } catch (error) {
    console.error('Google login backend error:', error);
    res.status(400).json({ error: 'Erreur de connexion Google' });
  }
});

const authenticateAdmin3 = async (req, res, next) => {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
      return res.status(401).json({ error: 'Token d\'accès requis' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const userDoc = await db.collection('users').doc(decoded.uid).get();
    const userData = userDoc.data();

    if (!userData || !userData.isAdmin) {
      return res.status(403).json({ error: 'Accès admin requis' });
    }

    req.user = { uid: decoded.uid, ...userData };
    next();
  } catch (error) {
    console.error('Admin auth error:', error);
    return res.status(403).json({ error: 'Token invalide' });
  }
};

// Middleware pour récupérer les abonnés
const getSubscribers = async (req, res, next) => {
  try {
    const subscribersSnapshot = await db.collection('subscribers').get();
    const subscribers = subscribersSnapshot.docs.map(doc => doc.data().email);
    req.subscribers = subscribers;
    next();
  } catch (error) {
    console.error('Erreur lors de la récupération des abonnés:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération des abonnés' });
  }
};

app.post('/api/verify-email', async (req, res) => {
  try {
    const { uid } = req.body;
    
    if (!uid) {
      return res.status(400).json({ error: 'UID requis' });
    }
    
    // Vérifier que l'utilisateur existe dans Firebase Auth
    const userRecord = await auth.getUser(uid);
    
    if (!userRecord.emailVerified) {
      return res.status(400).json({ error: 'Email non vérifié' });
    }
    
    // Récupérer les données de pending_users
    const pendingUserDoc = await db.collection('pending_users').doc(uid).get();
    
    if (!pendingUserDoc.exists) {
      return res.status(404).json({ error: 'Utilisateur en attente non trouvé' });
    }
    
    const pendingData = pendingUserDoc.data();
    
    // ⭐ Transférer les données vers users avec isAdmin
    await db.collection('users').doc(uid).set({
      email: pendingData.email,
      name: pendingData.name,
      firstName: pendingData.firstName,
      lastName: pendingData.lastName,
      phone: pendingData.phone,
      address: pendingData.address,
      password: pendingData.hashedPassword,
      isAdmin: pendingData.isAdmin || false, // ⭐ IMPORTANT : Transférer isAdmin
      createdAt: pendingData.createdAt,
      emailVerified: true,
    });
    
    // Supprimer de pending_users
    await db.collection('pending_users').doc(uid).delete();
    
    // Générer un token JWT complet
    const token = jwt.sign(
      { uid, emailVerified: true, isAdmin: pendingData.isAdmin || false }, 
      process.env.JWT_SECRET, 
      { expiresIn: '24h' }
    );
    
    res.status(200).json({
      message: 'Email vérifié avec succès',
      token,
      user: {
        uid,
        email: pendingData.email,
        name: pendingData.name,
        isAdmin: pendingData.isAdmin || false,
      }
    });
    
  } catch (error) {
    console.error('Email verification error:', error);
    res.status(400).json({ error: 'Erreur lors de la vérification de l\'email' });
  }
});

// Resend email verification
app.post('/api/resend-verification', async (req, res) => {
  try {
    const { uid } = req.body;

    if (!uid) {
      return res.status(400).json({ error: 'UID is required' });
    }

    // Get user record from Firebase Auth
    const userRecord = await auth.getUser(uid);
    if (userRecord.emailVerified) {
      return res.status(400).json({ error: 'Email is already verified' });
    }

    // Generate a new email verification link
    const verificationLink = await auth.generateEmailVerificationLink(userRecord.email);

    // Generate email template with the user's first name and last name
    const pendingUserDoc = await db.collection('pending_users').doc(uid).get();
    if (!pendingUserDoc.exists) {
      return res.status(404).json({ error: 'No pending user found for this UID' });
    }
    const userData = pendingUserDoc.data();
    const emailHtml = generateEmailTemplate(verificationLink, `${userData.firstName} ${userData.lastName}`);

    // Send the verification email
    await transporter.sendMail({
      from: `E-com Team 🛍️ <${process.env.EMAIL_USER}>`,
      to: userRecord.email,
      subject: '✨ Re-vérifiez votre email pour rejoindre E-com',
      html: emailHtml,
      text: `
        Bonjour ${userData.firstName} ${userData.lastName},

        Nous avons reçu une demande pour renvoyer le lien de vérification de votre email. Cliquez sur ce lien pour vérifier votre adresse :

        ${verificationLink}

        Ce lien expire dans 24 heures pour votre sécurité. Si vous n'avez pas demandé cela, ignorez cet email.

        Cordialement,
        L'équipe E-com
      `,
    });

    res.status(200).json({ message: 'Un nouvel email de vérification a été envoyé.' });
  } catch (error) {
    console.error('Resend verification error:', error);
    if (error.code === 'auth/user-not-found') {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }
    res.status(500).json({ error: 'Erreur lors de l\'envoi de l\'email de vérification.' });
  }
});

// Route pour envoyer un message à tous les abonnés (Admin uniquement)
app.post('/api/send-bulk-email', authenticateAdmin3, getSubscribers, async (req, res) => {
  try {
    const { subject, message } = req.body;

    if (!subject || !message) {
      return res.status(400).json({ error: 'Sujet et message sont requis' });
    }

    const subscribers = req.subscribers;

    if (!subscribers || subscribers.length === 0) {
      return res.status(404).json({ error: 'Aucun abonné trouvé' });
    }

    // Template HTML professionnel
    const createEmailTemplate = (message, subject) => {
      return `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>${subject}</title>
          <style>
            body {
              margin: 0;
              padding: 0;
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
              background-color: #f8fafc;
              line-height: 1.6;
            }
            .email-container {
              max-width: 600px;
              margin: 0 auto;
              background-color: #ffffff;
              box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              padding: 40px 30px;
              text-align: center;
            }
            .header h1 {
              color: #ffffff;
              margin: 0;
              font-size: 28px;
              font-weight: 600;
              text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .header p {
              color: #e2e8f0;
              margin: 10px 0 0 0;
              font-size: 16px;
            }
            .content {
              padding: 40px 30px;
            }
            .message-box {
              background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
              border-left: 4px solid #667eea;
              padding: 25px;
              margin: 20px 0;
              border-radius: 0 8px 8px 0;
              box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            .message-content {
              color: #334155;
              font-size: 16px;
              line-height: 1.7;
              margin: 0;
            }
            .cta-section {
              text-align: center;
              margin: 40px 0;
            }
            .cta-button {
              display: inline-block;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              color: #ffffff;
              text-decoration: none;
              padding: 15px 30px;
              border-radius: 50px;
              font-weight: 600;
              font-size: 16px;
              box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
              transition: all 0.3s ease;
            }
            .cta-button:hover {
              transform: translateY(-2px);
              box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            }
            .footer {
              background-color: #1e293b;
              padding: 30px;
              text-align: center;
            }
            .footer p {
              color: #94a3b8;
              margin: 5px 0;
              font-size: 14px;
            }
            .footer a {
              color: #667eea;
              text-decoration: none;
            }
            .footer a:hover {
              text-decoration: underline;
            }
            .social-links {
              margin: 20px 0;
            }
            .social-links a {
              display: inline-block;
              margin: 0 10px;
              color: #667eea;
              font-size: 18px;
              text-decoration: none;
            }
            .divider {
              height: 2px;
              background: linear-gradient(90deg, transparent, #667eea, transparent);
              margin: 30px 0;
              border: none;
            }
            @media (max-width: 600px) {
              .email-container {
                margin: 0;
                box-shadow: none;
              }
              .header, .content, .footer {
                padding: 20px;
              }
              .header h1 {
                font-size: 24px;
              }
              .message-box {
                padding: 20px;
              }
              .cta-button {
                padding: 12px 25px;
                font-size: 14px;
              }
            }
          </style>
        </head>
        <body>
          <div class="email-container">
            <!-- Header -->
            <div class="header">
              <h1>📧 Notification Importante</h1>
              <p>Message de votre équipe</p>
            </div>
            
            <!-- Content -->
            <div class="content">
              <div class="message-box">
                <div class="message-content">
                  ${message.replace(/\n/g, '<br>')}
                </div>
              </div>
              
              <hr class="divider">
              
              <div class="cta-section">
                <p style="color: #64748b; margin-bottom: 20px;">
                  Découvrez nos dernières nouveautés
                </p>
                <a href="https://e-com-front-b6o1.onrender.com" class="cta-button">
                  🚀 Visiter notre site
                </a>
              </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
              <div class="social-links">
                <a href="#">📘 Facebook</a>
                <a href="#">📷 Instagram</a>
                <a href="#">🐦 Twitter</a>
                <a href="#">💼 LinkedIn</a>
              </div>
              <p>
                Vous recevez cet email car vous êtes abonné à nos notifications.
              </p>
              <p>
                <a href="#">Se désabonner</a> | 
                <a href="#">Préférences email</a> | 
                <a href="#">Nous contacter</a>
              </p>
              <p style="margin-top: 20px; font-size: 12px; color: #64748b;">
                © 2024 Votre Entreprise. Tous droits réservés.<br>
                123 Rue de la Technologie, 75001 Paris, France
              </p>
            </div>
          </div>
        </body>
        </html>
      `;
    };

    // Envoyer un email à chaque abonné avec le nouveau template
    const emailPromises = subscribers.map(async (email) => {
      await transporter.sendMail({
        from: `"Votre Équipe 👋" <${process.env.EMAIL_USER}>`,
        to: email,
        subject: `🎯 ${subject}`,
        text: message, // Version texte pour les clients qui ne supportent pas HTML
        html: createEmailTemplate(message, subject), // Utilisez createSimpleTemplate() pour la version simple
      });
    });

    await Promise.all(emailPromises);

    res.status(200).json({
      message: `🎉 Emails envoyés avec succès à ${subscribers.length} abonnés`,
      subscriberCount: subscribers.length,
      details: "Template HTML professionnel appliqué"
    });
  } catch (error) {
    console.error('Erreur lors de l\'envoi des emails:', error);
    res.status(500).json({ 
      error: 'Erreur lors de l\'envoi des emails',
      details: error.message 
    });
  }
});

// Envoi d'image sur Cloudinary - VERSION CORRIGÉE
app.post('/api/upload', authenticateToken, upload.single('file'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'Aucun fichier envoyé' });
    }

    let optimizedBuffer;
    if (req.file.mimetype.startsWith('image/')) {
      // Optimize image with sharp
      optimizedBuffer = await sharp(req.file.buffer)
        .resize(800, 600, { fit: 'inside', withoutEnlargement: true })
        .toFormat('webp', { quality: 80 })
        .toBuffer();
    } else if (req.file.mimetype.startsWith('video/')) {
      // Handle video (no optimization with sharp, use existing ffmpeg logic)
      const uploadStream = cloudinary.uploader.upload_stream(
        { folder: 'products', resource_type: 'auto' },
        (error, result) => {
          if (error) {
            console.error('Erreur Cloudinary (vidéo):', error);
            return res.status(500).json({ error: 'Erreur lors de l\'upload de la vidéo.' });
          }
          res.status(200).json({ secure_url: result.secure_url, public_id: result.public_id });
        }
      );
      uploadStream.end(req.file.buffer);
      return;
    } else {
      return res.status(400).json({ error: 'Type de fichier non supporté pour optimisation.' });
    }

    // Upload optimized image to Cloudinary
    const uploadStream = cloudinary.uploader.upload_stream(
      {
        folder: 'products',
        resource_type: 'image',
        format: 'webp',
      },
      (error, result) => {
        if (error) {
          console.error('Erreur Cloudinary (image):', error);
          return res.status(500).json({ error: 'Erreur lors de l\'upload de l\'image.' });
        }
        res.status(200).json({ secure_url: result.secure_url, public_id: result.public_id });
      }
    );
    uploadStream.end(optimizedBuffer);
  } catch (error) {
    console.error('Erreur inattendue:', error);
    res.status(500).json({ error: error.message });
  }
});

// Réinitialisation de mot de passe
app.post('/api/reset-password', async (req, res) => {
  try {
    const { email } = req.body;
    
    if (!email || !validateEmail(email)) {
      return res.status(400).json({ error: 'Veuillez entrer un email valide' });
    }

    // Vérifier si l'utilisateur existe dans Firestore
    const userQuery = await db.collection('users').where('email', '==', email).get();
    
    if (userQuery.empty) {
      return res.status(400).json({ error: 'Aucun utilisateur trouvé avec cet email' });
    }

    // Générer un token de réinitialisation
    const resetToken = jwt.sign({ email }, process.env.JWT_SECRET, { expiresIn: '1h' });
    
    // Stocker le token dans Firestore
    const userDoc = userQuery.docs[0];
    await db.collection('users').doc(userDoc.id).update({
      resetToken,
      resetTokenExpiry: new Date(Date.now() + 3600000).toISOString() // 1 heure
    });

    // Ici, vous devriez envoyer un email avec le lien de réinitialisation
    // Pour l'instant, on retourne juste un message de succès
    res.status(200).json({ 
      message: 'Email de réinitialisation envoyé avec succès',
      // En développement, vous pouvez retourner le token pour tester
      ...(process.env.NODE_ENV === 'development' && { resetToken })
    });
  } catch (error) {
    console.error('Reset password error:', error);
    res.status(500).json({ 
      error: error.message || 'Erreur lors de l\'envoi de l\'email de réinitialisation' 
    });
  }
});

// Route pour confirmer la réinitialisation de mot de passe
app.post('/api/confirm-reset-password', async (req, res) => {
  try {
    const { token, newPassword } = req.body;
    
    if (!token || !newPassword) {
      return res.status(400).json({ error: 'Token et nouveau mot de passe requis' });
    }

    if (newPassword.length < 6) {
      return res.status(400).json({ error: 'Le mot de passe doit contenir au moins 6 caractères' });
    }

    // Vérifier le token
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const { email } = decoded;

    // Trouver l'utilisateur
    const userQuery = await db.collection('users').where('email', '==', email).get();
    
    if (userQuery.empty) {
      return res.status(400).json({ error: 'Token invalide' });
    }

    const userDoc = userQuery.docs[0];
    const userData = userDoc.data();

    // Vérifier si le token correspond et n'est pas expiré
    if (userData.resetToken !== token || new Date() > new Date(userData.resetTokenExpiry)) {
      return res.status(400).json({ error: 'Token invalide ou expiré' });
    }

    // Hasher le nouveau mot de passe
    const hashedPassword = await bcrypt.hash(newPassword, 10);

    // Mettre à jour le mot de passe et supprimer le token de réinitialisation
    await db.collection('users').doc(userDoc.id).update({
      password: hashedPassword,
      resetToken: null,
      resetTokenExpiry: null
    });

    res.status(200).json({ message: 'Mot de passe réinitialisé avec succès' });
  } catch (error) {
    console.error('Confirm reset password error:', error);
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      return res.status(400).json({ error: 'Token invalide ou expiré' });
    }
    res.status(500).json({ error: 'Erreur lors de la réinitialisation du mot de passe' });
  }
});

// Middleware pour vérifier les droits d'admin
const authenticateAdmin = async (req, res, next) => {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    console.log('🔑 Token reçu:', token ? 'Présent' : 'Absent');

    if (!token) {
      return res.status(401).json({ error: 'Token d\'accès requis' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    console.log('✅ Token décodé:', decoded);

    const userDoc = await db.collection('users').doc(decoded.uid).get();
    console.log('📄 Document existe:', userDoc.exists);
    
    const userData = userDoc.data();
    console.log('👤 Données utilisateur:', userData);
    console.log('🔐 isAdmin:', userData?.isAdmin);

    if (!userData || !userData.isAdmin) {
      console.log('❌ REFUSÉ - userData:', !!userData, 'isAdmin:', userData?.isAdmin);
      return res.status(403).json({ error: 'Accès admin requis' });
    }

    console.log('✅ ACCÈS ACCORDÉ');
    req.user = { uid: decoded.uid, ...userData };
    next();
  } catch (error) {
    console.error('❌ Erreur admin auth:', error);
    return res.status(403).json({ error: 'Token invalide' });
  }
};


// ROUTES ADMIN POUR LA GESTION DES PRODUITS

// 1. Ajouter un produit - VERSION CORRIGÉE
app.post('/api/admin/products', authenticateAdmin, upload.single('file'), async (req, res) => {
  try {
    const { name, price, description, rating, stock, mediaUrl, category } = req.body;

    if (!name || !price || !description || !category) {
      return res.status(400).json({ error: 'Nom, prix, description et catégorie sont requis' });
    }

    if (isNaN(price) || price <= 0) {
      return res.status(400).json({ error: 'Le prix doit être un nombre positif' });
    }

    let mediaPublicId = null;
    let finalMediaUrl = mediaUrl;

    if (req.file) {
      try {
        // Optimize image with sharp
        const optimizedBuffer = await sharp(req.file.buffer)
          .resize(800, 600, { fit: 'inside', withoutEnlargement: true })
          .toFormat('webp', { quality: 80 })
          .toBuffer();

        const result = await new Promise((resolve, reject) => {
          const uploadStream = cloudinary.uploader.upload_stream(
            {
              folder: 'products',
              resource_type: 'image',
              format: 'webp',
            },
            (error, result) => (error ? reject(error) : resolve(result))
          );
          uploadStream.end(optimizedBuffer);
        });

        finalMediaUrl = result.secure_url;
        mediaPublicId = result.public_id;
      } catch (uploadError) {
        console.error('Erreur upload Cloudinary:', uploadError);
        return res.status(500).json({ error: 'Erreur lors de l\'upload du média' });
      }
    } else if (!mediaUrl) {
      return res.status(400).json({ error: 'Un média est requis (fichier ou URL)' });
    }

    if (!finalMediaUrl) {
      return res.status(400).json({ error: 'URL du média manquante' });
    }

    const productData = {
      name: name.trim(),
      price: parseFloat(price),
      description: description.trim(),
      rating: parseFloat(rating) || 0,
      stock: parseInt(stock) || 0,
      category: category.trim().toUpperCase(),
      soldCount: 0,
      mediaUrl: finalMediaUrl,
      mediaPublicId,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      createdBy: req.user.uid,
    };

    console.log('Données du produit à sauvegarder:', productData);
    const productRef = await db.collection('products').add(productData);
    const createdProduct = await productRef.get();
    const createdProductData = createdProduct.data();

    res.status(201).json({
      message: 'Produit créé avec succès',
      productId: productRef.id,
      product: { id: productRef.id, ...createdProductData },
    });
  } catch (error) {
    console.error('Create product error:', error);
    res.status(500).json({ error: error.message });
  }
});

// 2. Récupérer tous les produits (Admin)
app.get('/api/admin/products', authenticateAdmin, async (req, res) => {
  try {
    const productsSnapshot = await db.collection('products').get();
    const products = [];

    productsSnapshot.forEach(doc => {
      const productData = doc.data();
      products.push({
        id: doc.id,
        ...productData
      });
    });

    console.log('Produits récupérés:', products.length);
    
    products.forEach(product => {
      console.log(`Produit ${product.id}:`, {
        name: product.name,
        mediaUrl: product.mediaUrl,
        mediaPublicId: product.mediaPublicId
      });
    });

    res.status(200).json({
      success: true,
      count: products.length,
      products
    });
  } catch (error) {
    console.error('Get products error:', error);
    res.status(500).json({ error: error.message });
  }
});

// 3. Récupérer un produit par ID
app.get('/api/admin/products/:id', authenticateAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    const productDoc = await db.collection('products').doc(id).get();

    if (!productDoc.exists) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    const productData = productDoc.data();
    const product = {
      id: productDoc.id,
      ...productData
    };

    console.log('Produit récupéré:', product);

    res.status(200).json({
      success: true,
      product
    });
  } catch (error) {
    console.error('Get product error:', error);
    res.status(500).json({ error: error.message });
  }
});

// 4. Mettre à jour un produit
app.put('/api/admin/products/:id', authenticateAdmin, upload.single('file'), async (req, res) => {
  try {
    const { id } = req.params;
    const { name, price, description, rating, stock } = req.body;

    const productDoc = await db.collection('products').doc(id).get();
    if (!productDoc.exists) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    const currentProduct = productDoc.data();
    const updateData = { updatedAt: new Date().toISOString() };

    if (name) updateData.name = name.trim();
    if (price) {
      if (isNaN(price) || price <= 0) {
        return res.status(400).json({ error: 'Le prix doit être un nombre positif' });
      }
      updateData.price = parseFloat(price);
    }
    if (description) updateData.description = description.trim();
    if (rating !== undefined) updateData.rating = parseFloat(rating);
    if (stock !== undefined) updateData.stock = parseInt(stock);

    if (req.file) {
      console.log('Upload d\'un nouveau média pour le produit...');

      if (currentProduct.mediaPublicId) {
        try {
          await cloudinary.uploader.destroy(currentProduct.mediaPublicId);
          console.log('Ancien média supprimé de Cloudinary');
        } catch (deleteError) {
          console.error('Erreur lors de la suppression de l\'ancien média:', deleteError);
        }
      }

      const optimizedBuffer = await sharp(req.file.buffer)
        .resize(800, 600, { fit: 'inside', withoutEnlargement: true })
        .toFormat('webp', { quality: 80 })
        .toBuffer();

      const uploadResult = await new Promise((resolve, reject) => {
        const uploadStream = cloudinary.uploader.upload_stream(
          { folder: 'products', resource_type: 'image', format: 'webp' },
          (error, result) => (error ? reject(error) : resolve(result))
        );
        uploadStream.end(optimizedBuffer);
      });

      updateData.mediaUrl = uploadResult.secure_url;
      updateData.mediaPublicId = uploadResult.public_id;
    }

    await db.collection('products').doc(id).update(updateData);
    const updatedDoc = await db.collection('products').doc(id).get();
    const updatedProduct = { id: updatedDoc.id, ...updatedDoc.data() };

    res.json({
      message: 'Produit mis à jour avec succès',
      product: updatedProduct,
    });
  } catch (error) {
    console.error('Update product error:', error);
    res.status(500).json({ error: error.message });
  }
});

// 5. Supprimer un produit
app.delete('/api/admin/products/:id', authenticateAdmin, async (req, res) => {
  try {
    const { id } = req.params;

    // Vérifier si le produit existe
    const productDoc = await db.collection('products').doc(id).get();
    if (!productDoc.exists) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    const productData = productDoc.data();

    // Supprimer le média de Cloudinary si il existe
    if (productData.mediaPublicId) { // Changé de imagePublicId à mediaPublicId
      try {
        await cloudinary.uploader.destroy(productData.mediaPublicId); // Changé de imagePublicId à mediaPublicId
        console.log('Média supprimé de Cloudinary');
      } catch (deleteError) {
        console.error('Erreur lors de la suppression du média:', deleteError);
      }
    }

    // Supprimer le produit de Firestore
    await db.collection('products').doc(id).delete();

    res.json({ message: 'Produit supprimé avec succès' });
  } catch (error) {
    console.error('Delete product error:', error);
    res.status(500).json({ error: 'Erreur lors de la suppression du produit' });
  }
});

// 6. Mettre à jour le nombre de ventes d'un produit
app.patch('/api/admin/products/:id/sales', authenticateAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    const { soldCount } = req.body;

    if (soldCount === undefined || isNaN(soldCount) || soldCount < 0) {
      return res.status(400).json({ error: 'Le nombre de ventes doit être un nombre positif' });
    }

    const productDoc = await db.collection('products').doc(id).get();
    if (!productDoc.exists) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    await db.collection('products').doc(id).update({
      soldCount: parseInt(soldCount),
      updatedAt: new Date().toISOString()
    });

    res.json({ message: 'Nombre de ventes mis à jour avec succès' });
  } catch (error) {
    console.error('Update sales error:', error);
    res.status(500).json({ error: 'Erreur lors de la mise à jour des ventes' });
  }
});

// 9. Récupérer un produit par ID (Public)
app.get('/api/products/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const productDoc = await db.collection('products').doc(id).get();

    if (!productDoc.exists) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    const data = productDoc.data();
    const { createdBy, ...publicData } = data;
    
    res.json({ id: productDoc.id, ...publicData });
  } catch (error) {
    console.error('Get public product error:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération du produit' });
  }
});

// Route pour créer un admin (à utiliser une seule fois)
app.post('/api/create-admin', async (req, res) => {
  try {
    const { email, password } = req.body;
    
    // Vérifier les identifiants (vous pouvez changer ces valeurs)
    if (email !== 'admin@example.com' || password !== 'admin123') {
      return res.status(401).json({ error: 'Identifiants incorrects' });
    }

    // Rechercher l'utilisateur par email
    const userQuery = await db.collection('users').where('email', '==', email).get();
    
    if (userQuery.empty) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    const userDoc = userQuery.docs[0];
    
    // Mettre à jour l'utilisateur pour le rendre admin
    await db.collection('users').doc(userDoc.id).update({
      isAdmin: true,
      updatedAt: new Date().toISOString()
    });

    res.json({ message: 'Utilisateur promu admin avec succès' });
  } catch (error) {
    console.error('Create admin error:', error);
    res.status(500).json({ error: 'Erreur lors de la création de l\'admin' });
  }
});

app.get('/api/products', async (req, res) => {
  try {
    console.log('Paramètres reçus:', req.query);
    
    const { 
      page = 1, 
      limit = 46, 
      category = '', 
      search = '', 
      sortBy = 'createdAt', 
      order = 'desc',
      all = 'false'
    } = req.query;
    
    console.log('Paramètre all:', all, 'Type:', typeof all);

    const validSortFields = ['createdAt', 'name', 'price', 'rating', 'stock'];
    if (!validSortFields.includes(sortBy)) {
      return res.status(400).json({ error: 'Champ de tri invalide. Utilisez : createdAt, name, price, rating, ou stock' });
    }
    if (order !== 'asc' && order !== 'desc') {
      return res.status(400).json({ error: 'Ordre invalide. Utilisez : asc ou desc' });
    }
    
    if (all.toLowerCase() !== 'true') {
      const parsedLimit = parseInt(limit);
      if (isNaN(parsedLimit) || parsedLimit < 1 || parsedLimit > 100) {
        return res.status(400).json({ error: 'Limite invalide. Doit être entre 1 et 100' });
      }
      const parsedPage = parseInt(page);
      if (isNaN(parsedPage) || parsedPage < 1) {
        return res.status(400).json({ error: 'Page invalide. Doit être un nombre positif' });
      }
    }

    const buildBaseQuery = () => {
      let query = db.collection('products');
      
      if (category) {
        const categoriesArray = category.split(',').map(cat => cat.trim()).filter(cat => cat);
        if (categoriesArray.length > 0) {
          query = query.where('category', 'in', categoriesArray);
        }
      }
      
      if (search) {
        query = query.where('name', '>=', search).where('name', '<=', search + '\uf8ff');
      }
      
      return query;
    };

    const applySorting = (query) => {
      if (search) {
        query = query.orderBy('name');
        if (sortBy !== 'name') {
          query = query.orderBy(sortBy, order);
        }
      } else {
        console.log('MODE PAGINATION - Page:', page, 'Limit:', limit);
        query = query.orderBy(sortBy, order);
      }
      return query;
    };

    let snapshot;
    let totalItems;

    if (all.toLowerCase() === 'true' || all === true || all === 'true') {
      console.log('MODE ALL ACTIVÉ - Récupération de TOUS les produits...');
      
      let allProductsQuery = buildBaseQuery();
      allProductsQuery = applySorting(allProductsQuery);
      
      snapshot = await allProductsQuery.get();
      totalItems = snapshot.size;
      
      console.log(`Total de ${totalItems} produits récupérés`);
    } else {
      const parsedLimit = parseInt(limit);
      const parsedPage = parseInt(page);
      
      console.log(`Récupération page ${parsedPage} avec ${parsedLimit} produits par page`);
      
      let paginatedQuery = buildBaseQuery();
      paginatedQuery = applySorting(paginatedQuery);
      
      const startIndex = (parsedPage - 1) * parsedLimit;
      if (startIndex > 0) {
        const offsetQuery = buildBaseQuery();
        const offsetQuerySorted = applySorting(offsetQuery);
        const offsetSnapshot = await offsetQuerySorted.limit(startIndex).get();
        
        if (offsetSnapshot.docs.length > 0) {
          const lastVisible = offsetSnapshot.docs[offsetSnapshot.docs.length - 1];
          paginatedQuery = paginatedQuery.startAfter(lastVisible);
        }
      }
      
      snapshot = await paginatedQuery.limit(parsedLimit).get();
      
      const countQuery = buildBaseQuery();
      const totalSnapshot = await countQuery.get();
      totalItems = totalSnapshot.size;
    }

    const products = snapshot.docs.map(doc => {
      const data = doc.data();
      const { createdBy, ...publicData } = data;
      return { id: doc.id, ...publicData };
    });

    const isAllMode = all.toLowerCase() === 'true' || all === true || all === 'true';
    console.log('isAllMode:', isAllMode);
    
    const response = {
      products,
      pagination: isAllMode ? {
        currentPage: 1,
        itemsPerPage: products.length,
        totalItems,
        totalPages: 1,
        isComplete: true
      } : {
        currentPage: parseInt(page),
        itemsPerPage: parseInt(limit),
        totalItems,
        totalPages: Math.ceil(totalItems / parseInt(limit)),
        hasNextPage: parseInt(page) < Math.ceil(totalItems / parseInt(limit)),
        hasPreviousPage: parseInt(page) > 1
      }
    };

    response.meta = {
      timestamp: new Date().toISOString(),
      filters: { category: category || null, search: search || null, sortBy, order },
      retrievedAll: isAllMode,
      count: products.length
    };

    console.log(`Réponse envoyée: ${products.length} produits`);
    res.json(response);
  } catch (error) {
    console.error('Get public products error:', {
      message: error.message,
      stack: error.stack,
      timestamp: new Date().toISOString(),
      query: req.query
    });
    
    if (error.code === 'resource-exhausted') {
      return res.status(429).json({ error: 'Trop de requêtes. Veuillez réessayer plus tard.' });
    }
    
    if (error.code === 'invalid-argument') {
      return res.status(400).json({ error: 'Paramètres de requête invalides' });
    }
    
    res.status(500).json({ error: 'Erreur lors de la récupération des produits' });
  }
});

const authenticateToken1 = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN
  if (!token) return res.status(401).json({ error: 'Token requis' });

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      console.error('Erreur vérification token:', err);
      return res.status(403).json({ error: 'Token invalide' });
    }
    req.user = user;
    next();
  });
};

// GET /api/likes?uid={uid} - Récupérer les likes d'un utilisateur
// GET /api/likes - Récupérer les likes d'un utilisateur
app.get('/api/likes', authenticateToken1, async (req, res) => {
  try {
    const { uid } = req.query;
    console.log('Requête /api/likes reçue avec uid:', uid); // Log UID
    if (!uid) return res.status(400).json({ error: 'UID requis' });

    const snapshot = await db.collection('likes')
      .where('uid', '==', uid)
      .get();
    
    console.log('Nombre de likes trouvés:', snapshot.size); // Log nombre de likes
    const likes = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    res.json({ likes });
  } catch (error) {
    console.error('Erreur récupération likes:', {
      message: error.message,
      stack: error.stack,
      query: req.query
    });
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// POST /api/likes - Ajouter un like
app.post('/api/likes', authenticateToken1, async (req, res) => {
  try {
    const { uid, productId } = req.body;
    if (!uid || !productId) return res.status(400).json({ error: 'UID et productId requis' });

    const likeRef = db.collection('likes').doc(`${uid}_${productId}`);
    const doc = await likeRef.get();

    if (!doc.exists) {
      await likeRef.set({ uid, productId, createdAt: new Date().toISOString() });
      res.status(201).json({ message: 'Like ajouté', productId });
    } else {
      res.status(400).json({ error: 'Like déjà existant' });
    }
  } catch (error) {
    console.error('Erreur ajout like:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// DELETE /api/likes/:uid/:productId - Supprimer un like
app.delete('/api/likes/:uid/:productId', authenticateToken1, async (req, res) => {
  try {
    const { uid, productId } = req.params;
    if (!uid || !productId) return res.status(400).json({ error: 'UID et productId requis' });

    const likeRef = db.collection('likes').doc(`${uid}_${productId}`);
    const doc = await likeRef.get();

    if (doc.exists) {
      await likeRef.delete();
      res.json({ message: 'Like supprimé', productId });
    } else {
      res.status(404).json({ error: 'Like non trouvé' });
    }
  } catch (error) {
    console.error('Erreur suppression like:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Alternative pour DELETE avec query params
app.delete('/api/likes', authenticateToken, async (req, res) => {
  try {
    const { uid, productId } = req.query;
    if (!uid || !productId) return res.status(400).json({ error: 'UID et productId requis' });

    const likeRef = db.collection('likes').doc(`${uid}_${productId}`);
    const doc = await likeRef.get();

    if (doc.exists) {
      await likeRef.delete();
      res.json({ message: 'Like supprimé', productId });
    } else {
      res.status(404).json({ error: 'Like non trouvé' });
    }
  } catch (error) {
    console.error('Erreur suppression like:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Middleware alternatif plus simple (sans filtres)
app.get('/api/products/simple-all', async (req, res) => {
  try {
    console.log('Récupération simple de TOUS les produits');
    
    // Requête la plus simple possible
    const snapshot = await db.collection('products').get();
    
    console.log(`${snapshot.size} documents trouvés`);
    
    const products = [];
    snapshot.forEach(doc => {
      const data = doc.data();
      const { createdBy, ...publicData } = data;
      products.push({ 
        id: doc.id, 
        ...publicData 
      });
    });
    
    console.log(`${products.length} produits traités`);
    
    res.json({
      success: true,
      products,
      count: products.length,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('Erreur dans /api/products/simple-all:', error);
    res.status(500).json({ 
      error: 'Erreur lors de la récupération des produits',
      success: false 
    });
  }
});

app.post('/api/subscribe', async (req, res) => {
  try {
    const { email } = req.body;
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return res.status(400).json({ error: 'Adresse email invalide' });
    }

    // Vérifier si l'email est déjà abonné (optionnel, via Firestore)
    const subscriberRef = db.collection('subscribers').doc(email);
    const doc = await subscriberRef.get();
    if (doc.exists) {
      return res.status(400).json({ error: 'Vous êtes déjà abonné' });
    }

    // Envoyer l'email
    await transporter.sendMail({
      from: process.env.EMAIL_USER,
      to: email,
      subject: 'Bienvenue chez E-com !',
      text: `Merci de vous être abonné à notre newsletter. Profitez de 15% de réduction sur votre première commande !\n\nVous pouvez vous désabonner à tout moment en nous contactant à abelbeingar@gmail.com.\n\nMeilleures salutations,\nL'équipe TechTrend Shop`,
      html: `<h1>Bienvenue chez E-com !</h1>
             <p>Merci de vous être abonné à notre newsletter. Profitez de <strong>15% de réduction</strong> sur votre première commande !</p>
             <p>Vous pouvez vous désabonner à tout moment en nous contactant à <a href="mailto:abelbeingar@gmail.com">abelbeingar@gmail.com</a>.</p>
             <p>Meilleures salutations,<br>L'équipe E-com</p>`,
    });

    // Enregistrer l'abonnement dans Firestore (optionnel)
    await subscriberRef.set({
      email,
      subscribedAt: new Date().toISOString(),
    });

    res.status(200).json({ message: 'Abonnement réussi ! Un email de confirmation vous a été envoyé.' });
  } catch (error) {
    console.error('Erreur lors de l\'abonnement:', error);
    res.status(500).json({ error: 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.' });
  }
});

// Middleware pour obtenir le nombre total de produits
app.get('/api/products/count', async (req, res) => {
  try {
    console.log('Comptage des produits');
    
    const { category = '', search = '' } = req.query;
    
    let query = db.collection('products');
    
    if (category) {
      query = query.where('category', '==', category);
    }
    
    if (search) {
      query = query.where('name', '>=', search).where('name', '<=', search + '\uf8ff');
    }
    
    const snapshot = await query.get();
    
    res.json({
      success: true,
      count: snapshot.size,
      filters: { category, search },
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('Erreur dans /api/products/count:', error);
    res.status(500).json({ 
      error: 'Erreur lors du comptage des produits',
      success: false 
    });
  }
});

// Fonction pour générer un code unique
const generateUniqueAffiliateCode = () => {
  const timestamp = Date.now().toString(36);
  const randomBytes = crypto.randomBytes(4).toString('hex').toUpperCase();
  return `AFF-${timestamp}-${randomBytes}`;
};

// Devenir affilié
app.post('/become-affiliate', async (req, res) => {
  try {
    const { uid } = req.body;

    // Vérifier si l'utilisateur est déjà affilié
    const existingAffiliate = await db.collection('affiliates')
      .where('uid', '==', uid)
      .get();

    if (!existingAffiliate.empty) {
      return res.status(400).json({ error: 'Utilisateur déjà affilié' });
    }

    // Générer un code unique
    const affiliateCode = generateUniqueAffiliateCode();

    const affiliateData = {
      uid,
      affiliateCode,
      referralLink: `${process.env.FRONTEND_URL}/ref/${affiliateCode}`,
      commissionRate: 0.05,
      totalEarnings: 0,
      totalReferrals: 0,
      isActive: true,
      createdAt: new Date(),
      updatedAt: new Date()
    };

    await db.collection('affiliates').add(affiliateData);

    res.json({
      success: true,
      affiliate: affiliateData
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Traquer les clics sur liens d'affiliation
app.post('/track-click', async (req, res) => {
  try {
    const { affiliateCode, visitorId } = req.body;

    // Trouver l'affilié
    const affiliateSnapshot = await db.collection('affiliates')
      .where('affiliateCode', '==', affiliateCode)
      .get();

    if (affiliateSnapshot.empty) {
      return res.status(404).json({ error: 'Code d\'affiliation invalide' });
    }

    const affiliate = affiliateSnapshot.docs[0];

    // Vérifier si ce visiteur a déjà été référé
    const existingReferral = await db.collection('referrals')
      .where('affiliateId', '==', affiliate.data().uid)
      .where('referredUserId', '==', visitorId)
      .get();

    if (!existingReferral.empty) {
      // Mettre à jour le dernier clic
      await existingReferral.docs[0].ref.update({
        lastClickAt: new Date()
      });
    } else {
      // Créer nouvelle référence
      await db.collection('referrals').add({
        affiliateId: affiliate.data().uid,
        referredUserId: visitorId,
        affiliateCode,
        firstClickAt: new Date(),
        lastClickAt: new Date(),
        conversionAt: null,
        status: 'pending',
        orders: [],
        totalValue: 0
      });
    }

    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/affiliate/request', authenticateToken, upload.single('identityCard'), async (req, res) => {
  try {
    const { uid } = req.user;
    const { reason } = req.body;

    if (!reason) {
      return res.status(400).json({ error: 'La raison est requise' });
    }

    if (!req.file) {
      return res.status(400).json({ error: 'La photo de la carte d\'identité est requise' });
    }

    // Téléverser la photo sur Cloudinary
    const uploadResult = await new Promise((resolve, reject) => {
      const uploadStream = cloudinary.uploader.upload_stream(
        {
          folder: 'affiliate_requests',
          resource_type: 'image',
          transformation: [
            { width: 800, height: 600, crop: 'limit' },
            { quality: 'auto' }
          ]
        },
        (error, result) => {
          if (error) reject(error);
          else resolve(result);
        }
      );
      uploadStream.end(req.file.buffer);
    });

    const identityCardUrl = uploadResult.secure_url;
    const identityCardPublicId = uploadResult.public_id;

    // Stocker la demande dans Firestore
    await db.collection('demande').add({
      uid,
      reason,
      identityCardUrl,
      identityCardPublicId,
      status: 'pending',
      createdAt: new Date().toISOString()
    });

    res.status(201).json({ message: 'Demande d\'affiliation soumise avec succès' });
  } catch (error) {
    console.error('Erreur soumission demande affiliation:', error);
    res.status(500).json({ error: 'Erreur lors de la soumission de la demande' });
  }
});

app.get('/api/affiliate/status', authenticateToken, async (req, res) => {
  try {
    const { uid } = req.user;
    
    // Vérifier d'abord si l'utilisateur est déjà affilié
    const userDoc = await db.collection('users').doc(uid).get();
    const userData = userDoc.data();

    if (userData?.isAffiliate) {
      // Récupérer les données de l'affilié
      const affiliateSnapshot = await db.collection('affiliates').where('uid', '==', uid).get();
      if (!affiliateSnapshot.empty) {
        const affiliateData = affiliateSnapshot.docs[0].data();
        const referralCount = await db.collection('referrals')
          .where('affiliateId', '==', uid)
          .where('status', '==', 'converted')
          .get()
          .then(snapshot => snapshot.size);

        return res.json({
          isAffiliate: true,
          affiliateData: {
            ...affiliateData,
            referralCount
          }
        });
      }
    }

    // Vérifier s'il y a une demande en attente
    const requestSnapshot = await db.collection('demande')
      .where('uid', '==', uid)
      .orderBy('createdAt', 'desc')
      .limit(1)
      .get();

    if (!requestSnapshot.empty) {
      const latestRequest = requestSnapshot.docs[0].data();
      return res.json({
        isAffiliate: false,
        requestStatus: latestRequest.status // 'pending', 'approved' ou 'rejected'
      });
    }

    // Aucune demande trouvée
    res.json({ 
      isAffiliate: false,
      requestStatus: null
    });

  } catch (error) {
    console.error('Erreur vérification statut affiliation:', error);
    res.status(500).json({ error: 'Erreur lors de la vérification du statut' });
  }
});

app.post('/api/admin/approve-affiliate', authenticateAdmin, async (req, res) => {
  try {
    const { uid, identityCardUrl } = req.body;

    if (!uid || !identityCardUrl) {
      return res.status(400).json({ error: 'UID et URL de la carte d\'identité requis' });
    }

    // Vérifier la demande dans la collection 'demande'
    const demandeSnapshot = await db.collection('demande').where('uid', '==', uid).get();
    if (demandeSnapshot.empty) {
      return res.status(404).json({ error: 'Demande non trouvée' });
    }

    const demandeDoc = demandeSnapshot.docs[0];
    if (demandeDoc.data().status !== 'pending') {
      return res.status(400).json({ error: 'Demande déjà traitée' });
    }

    // Générer un code unique et lien d'affiliation
    const affiliateCode = generateUniqueAffiliateCode();
    const referralLink = `${affiliateCode}`;

    // Créer l'entrée dans affiliates
    const affiliateData = {
      uid,
      affiliateCode,
      referralLink,
      identityCardUrl,
      commissionRate: 0.05,
      totalEarnings: 0,
      referralCount: 0,
      isActive: true,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    await db.collection('affiliates').add(affiliateData);

    // Mettre à jour le statut de la demande et l'utilisateur
    await db.collection('demande').doc(demandeDoc.id).update({ status: 'approved' });
    await db.collection('users').doc(uid).update({ isAffiliate: true });

    res.status(200).json({ message: 'Affilié approuvé avec succès', affiliateData });
  } catch (error) {
    console.error('Erreur approbation affilié:', error);
    res.status(500).json({ error: 'Erreur lors de l\'approbation de l\'affilié' });
  }
});

// Middleware pour vérifier un lien d'affiliation et incrémenter les parrainages
app.get('/api/affiliate/verify-link', async (req, res) => {
  try {
    const { ref } = req.query;

    if (!ref || typeof ref !== 'string') {
      return res.status(400).json({ error: 'Paramètre de référence invalide' });
    }

    // Rechercher l'affilié par son code d'affiliation
    const affiliateSnapshot = await db.collection('affiliates')
      .where('affiliateCode', '==', ref)
      .get();

    if (affiliateSnapshot.empty) {
      return res.status(404).json({ message: 'Le lien d\'affiliation n\'existe pas.' });
    }

    const affiliateDoc = affiliateSnapshot.docs[0];
    const affiliateData = affiliateDoc.data();

    // Incrémenter le nombre de parrainages
    const newReferralCount = (affiliateData.referralCount || 0) + 1;
    await affiliateDoc.ref.update({ referralCount: newReferralCount });

    res.json({
      message: 'Lien d\'affiliation valide. Votre parrainage a été enregistré.',
      affiliateCode: ref,
      referralCount: newReferralCount,
    });
  } catch (error) {
    console.error('Erreur vérification lien d\'affiliation:', error);
    res.status(500).json({ error: 'Erreur lors de la vérification du lien d\'affiliation' });
  }
});

// Appliquer la réduction pour les clients référés
app.post('/apply-discount', async (req, res) => {
  try {
    const { userId, orderTotal } = req.body;

    // Trouver si l'utilisateur a été référé
    const referralSnapshot = await db.collection('referrals')
      .where('referredUserId', '==', userId)
      .where('status', '==', 'pending')
      .get();

    if (referralSnapshot.empty) {
      return res.json({ hasDiscount: false });
    }

    const referral = referralSnapshot.docs[0];
    const discountPercentage = 0.10; // 10% de réduction
    const discountAmount = orderTotal * discountPercentage;

    res.json({
      hasDiscount: true,
      discountPercentage,
      discountAmount,
      referralData: referral.data()
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Middleware pour lister les demandes en attente (pending)
app.get('/api/affiliate/requests/pending', authenticateToken, async (req, res) => {
  try {
    // Récupérer toutes les demandes avec statut 'pending'
    const snapshot = await db.collection('demande')
      .where('status', '==', 'pending')
      .get();

    const pendingRequests = snapshot.docs.map(doc => ({
      id: doc.id,
      ...doc.data()
    }));

    res.json({
      success: true,
      requests: pendingRequests
    });
  } catch (error) {
    console.error('Erreur récupération demandes en attente:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération des demandes en attente' });
  }
});

// Middleware pour lister les demandes approuvées (approved)
app.get('/api/affiliate/requests/approved', authenticateToken, async (req, res) => {
  try {
    // Récupérer toutes les demandes avec statut 'approved'
    const snapshot = await db.collection('demande')
      .where('status', '==', 'approved')
      .get();

    const approvedRequests = snapshot.docs.map(doc => ({
      id: doc.id,
      ...doc.data()
    }));

    res.json({
      success: true,
      requests: approvedRequests
    });
  } catch (error) {
    console.error('Erreur récupération demandes approuvées:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération des demandes approuvées' });
  }
});

// Middleware pour lister les demandes rejetées (rejected)
app.get('/api/affiliate/requests/rejected', authenticateToken, async (req, res) => {
  try {
    // Récupérer toutes les demandes avec statut 'rejected'
    const snapshot = await db.collection('demande')
      .where('status', '==', 'rejected')
      .get();

    const rejectedRequests = snapshot.docs.map(doc => ({
      id: doc.id,
      ...doc.data()
    }));

    res.json({
      success: true,
      requests: rejectedRequests
    });
  } catch (error) {
    console.error('Erreur récupération demandes rejetées:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération des demandes rejetées' });
  }
});

app.put('/api/affiliate/request/:id/reject', authenticateAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    const demandeDoc = await db.collection('demande').doc(id).get();
    if (!demandeDoc.exists) {
      return res.status(404).json({ error: 'Demande non trouvée' });
    }
    await db.collection('demande').doc(id).update({ status: 'rejected' });
    res.json({ message: 'Demande rejetée avec succès' });
  } catch (error) {
    console.error('Erreur rejet demande:', error);
    res.status(500).json({ error: 'Erreur lors du rejet de la demande' });
  }
});

app.delete('/api/affiliate/request/:id', authenticateAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    const demandeDoc = await db.collection('demande').doc(id).get();
    if (!demandeDoc.exists) {
      return res.status(404).json({ error: 'Demande non trouvée' });
    }
    await db.collection('demande').doc(id).delete();
    res.json({ message: 'Demande supprimée avec succès' });
  } catch (error) {
    console.error('Erreur suppression demande:', error);
    res.status(500).json({ error: 'Erreur lors de la suppression de la demande' });
  }
});

// Route de test pour vérifier l'authentification
app.get('/api/profile', authenticateToken, async (req, res) => {
  try {
    const userDoc = await db.collection('users').doc(req.user.uid).get();
    const userData = userDoc.data();
    
    // Ne pas retourner le mot de passe hashé
    const { password, resetToken, resetTokenExpiry, ...safeUserData } = userData;
    
    res.json({
      uid: req.user.uid,
      ...safeUserData
    });
  } catch (error) {
    console.error('Profile error:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération du profil' });
  }
});

app.use((req, res, next) => {
  res.header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
  res.header('Cross-Origin-Embedder-Policy', 'credentialless');
  next();
});

// Gestion des erreurs 404
app.use((req, res) => {
  res.status(404).json({ error: 'Route non trouvée' });
});

// Gestion des erreurs globales
app.use((error, req, res, next) => {
  console.error('Erreur serveur:', error);
  res.status(500).json({ error: 'Erreur interne du serveur' });
});

// Démarrage du serveur
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
});
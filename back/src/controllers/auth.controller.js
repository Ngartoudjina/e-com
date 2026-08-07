import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { eq } from 'drizzle-orm';
import { auth } from '../config/firebase.js';
import { googleClient } from '../config/google.js';
import { db } from '../config/db.js';
import { users, pendingUsers } from '../db/schema.js';
import { transporter, generateEmailTemplate } from '../services/email.js';

const signToken = (payload) => jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '24h' });
const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

export const register = async (req, res) => {
  try {
    const { email, password, name, firstName, lastName, phone, address } = req.body;

    if (!email || !password || !name || !firstName || !lastName || !phone || !address) {
      return res.status(400).json({ error: 'Tous les champs sont requis' });
    }
    if (!validateEmail(email)) {
      return res.status(400).json({ error: 'Email invalide' });
    }
    if (password.length < 6) {
      return res.status(400).json({ error: 'Le mot de passe doit contenir au moins 6 caractères' });
    }

    const [existing] = await db.select().from(users).where(eq(users.email, email)).limit(1);
    if (existing) {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    const userRecord = await auth.createUser({
      email,
      password,
      displayName: name,
      emailVerified: false,
    });

    const verificationLink = await auth.generateEmailVerificationLink(email);
    const emailHtml = generateEmailTemplate(verificationLink, `${firstName} ${lastName}`);

    await transporter.sendMail({
      from: `"E-com Team 🛍️" <${process.env.EMAIL_USER}>`,
      to: email,
      subject: '✨ Vérifiez votre email pour rejoindre E-com',
      html: emailHtml,
      text: [
        `Bonjour ${firstName} ${lastName},`,
        '',
        'Merci de vous être inscrit sur E-com !',
        '',
        'Pour compléter votre inscription, veuillez vérifier votre adresse email en cliquant sur ce lien :',
        verificationLink,
        '',
        'Ce lien expire dans 24 heures pour votre sécurité.',
        '',
        'Cordialement,',
        "L'équipe E-com",
      ].join('\n'),
    });

    await db.insert(pendingUsers).values({
      uid: userRecord.uid,
      email,
      name,
      firstName,
      lastName,
      phone,
      address,
      hashedPassword,
      isAdmin: false,
      createdAt: new Date(),
      verificationStatus: 'pending',
    });

    const tempToken = signToken({ uid: userRecord.uid, emailVerified: false });

    res.status(200).json({
      message: 'Un email de vérification avec un design amélioré a été envoyé. Veuillez vérifier votre email pour compléter l\'inscription.',
      tempToken,
      uid: userRecord.uid,
    });
  } catch (error) {
    console.error('Registration error:', error.message);
    if (error.code === 'auth/email-already-exists') {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }
    res.status(400).json({ error: error.message });
  }
};

export const login = async (req, res) => {
  try {
    const { email, password, idToken } = req.body;

    if (idToken) {
      const decodedToken = await auth.verifyIdToken(idToken);
      const uid = decodedToken.uid;

      let [userRow] = await db.select().from(users).where(eq(users.uid, uid)).limit(1);

      if (!userRow) {
        const userRecord = await auth.getUser(uid);
        userRow = {
          uid,
          email: userRecord.email,
          name: userRecord.displayName || 'Utilisateur',
          isAdmin: false,
          createdAt: new Date(),
          updatedAt: new Date(),
        };
        await db.insert(users).values(userRow).onConflictDoNothing();
      }

      const token = signToken({ uid, isAdmin: userRow.isAdmin || false });
      return res.status(200).json({
        token,
        user: { uid, email: userRow.email, name: userRow.name, isAdmin: userRow.isAdmin || false },
      });
    }

    if (email && password) {
      const [userRow] = await db.select().from(users).where(eq(users.email, email)).limit(1);

      if (!userRow) {
        return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
      }

      const isPasswordValid = await bcrypt.compare(password, userRow.password || '');
      if (!isPasswordValid) {
        return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
      }

      const token = signToken({ uid: userRow.uid, isAdmin: userRow.isAdmin || false });
      return res.status(200).json({
        token,
        user: { uid: userRow.uid, email: userRow.email, name: userRow.name, isAdmin: userRow.isAdmin || false },
      });
    }

    return res.status(400).json({ error: 'Email, mot de passe ou token requis' });
  } catch (error) {
    console.error('Login error:', error.message);
    if (error.code === 'auth/id-token-expired' || error.code === 'auth/invalid-id-token') {
      return res.status(401).json({ error: 'Token invalide ou expiré' });
    }
    res.status(400).json({ error: 'Erreur de connexion' });
  }
};

export const googleLogin = async (req, res) => {
  try {
    const { idToken } = req.body;

    if (!idToken) {
      return res.status(400).json({ error: 'Token Google requis' });
    }

    const ticket = await googleClient.verifyIdToken({
      idToken,
      audience: process.env.GOOGLE_CLIENT_ID,
    });

    const payload = ticket.getPayload();
    const { email, name, sub: googleId } = payload;

    let userRecord;
    try {
      userRecord = await auth.getUserByEmail(email);
    } catch (error) {
      if (error.code === 'auth/user-not-found') {
        userRecord = await auth.createUser({
          email,
          displayName: name,
        });
      } else {
        throw error;
      }
    }

    await db.insert(users).values({
      uid: userRecord.uid,
      email,
      name,
      googleId,
      isAdmin: false,
      createdAt: new Date(),
      updatedAt: new Date(),
    }).onConflictDoUpdate({
      target: users.uid,
      set: { name, email, googleId, updatedAt: new Date() },
    });

    const token = signToken({ uid: userRecord.uid });
    res.status(200).json({ token, user: { uid: userRecord.uid, email, name } });
  } catch (error) {
    console.error('Google login backend error:', error.message);
    res.status(400).json({ error: 'Erreur de connexion Google' });
  }
};

export const verifyEmail = async (req, res) => {
  try {
    const { uid } = req.body;

    if (!uid) {
      return res.status(400).json({ error: 'UID requis' });
    }

    const userRecord = await auth.getUser(uid);
    if (!userRecord.emailVerified) {
      return res.status(400).json({ error: 'Email non vérifié' });
    }

    const [pending] = await db.select().from(pendingUsers).where(eq(pendingUsers.uid, uid)).limit(1);
    if (!pending) {
      return res.status(404).json({ error: 'Utilisateur en attente non trouvé' });
    }

    await db.insert(users).values({
      uid,
      email: pending.email,
      name: pending.name,
      firstName: pending.firstName,
      lastName: pending.lastName,
      phone: pending.phone,
      address: pending.address,
      password: pending.hashedPassword,
      isAdmin: pending.isAdmin || false,
      emailVerified: true,
      createdAt: pending.createdAt,
      updatedAt: new Date(),
    }).onConflictDoUpdate({
      target: users.uid,
      set: { emailVerified: true, updatedAt: new Date() },
    });

    await db.delete(pendingUsers).where(eq(pendingUsers.uid, uid));

    const token = signToken({ uid, emailVerified: true, isAdmin: pending.isAdmin || false });
    res.status(200).json({
      message: 'Email vérifié avec succès',
      token,
      user: { uid, email: pending.email, name: pending.name, isAdmin: pending.isAdmin || false },
    });
  } catch (error) {
    console.error('Email verification error:', error.message);
    res.status(400).json({ error: 'Erreur lors de la vérification de l\'email' });
  }
};

export const resendVerification = async (req, res) => {
  try {
    const { uid } = req.body;

    if (!uid) {
      return res.status(400).json({ error: 'UID is required' });
    }

    const userRecord = await auth.getUser(uid);
    if (userRecord.emailVerified) {
      return res.status(400).json({ error: 'Email is already verified' });
    }

    const verificationLink = await auth.generateEmailVerificationLink(userRecord.email);

    const [pending] = await db.select().from(pendingUsers).where(eq(pendingUsers.uid, uid)).limit(1);
    if (!pending) {
      return res.status(404).json({ error: 'No pending user found for this UID' });
    }

    const emailHtml = generateEmailTemplate(verificationLink, `${pending.firstName} ${pending.lastName}`);

    await transporter.sendMail({
      from: `E-com Team 🛍️ <${process.env.EMAIL_USER}>`,
      to: userRecord.email,
      subject: '✨ Re-vérifiez votre email pour rejoindre E-com',
      html: emailHtml,
      text: [
        `Bonjour ${pending.firstName} ${pending.lastName},`,
        '',
        'Nous avons reçu une demande pour renvoyer le lien de vérification de votre email. Cliquez sur ce lien pour vérifier votre adresse :',
        verificationLink,
        '',
        'Ce lien expire dans 24 heures pour votre sécurité. Si vous n\'avez pas demandé cela, ignorez cet email.',
        '',
        'Cordialement,',
        "L'équipe E-com",
      ].join('\n'),
    });

    res.status(200).json({ message: 'Un nouvel email de vérification a été envoyé.' });
  } catch (error) {
    console.error('Resend verification error:', error.message);
    if (error.code === 'auth/user-not-found') {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }
    res.status(500).json({ error: 'Erreur lors de l\'envoi de l\'email de vérification.' });
  }
};

export const resetPassword = async (req, res) => {
  try {
    const { email } = req.body;

    if (!email || !validateEmail(email)) {
      return res.status(400).json({ error: 'Veuillez entrer un email valide' });
    }

    const [userRow] = await db.select().from(users).where(eq(users.email, email)).limit(1);
    if (!userRow) {
      return res.status(400).json({ error: 'Aucun utilisateur trouvé avec cet email' });
    }

    const resetToken = jwt.sign({ email }, process.env.JWT_SECRET, { expiresIn: '1h' });
    await db.update(users).set({
      resetToken,
      resetTokenExpiry: new Date(Date.now() + 3600000),
    }).where(eq(users.email, email));

    res.status(200).json({
      message: 'Email de réinitialisation envoyé avec succès',
      ...(process.env.NODE_ENV === 'development' && { resetToken }),
    });
  } catch (error) {
    console.error('Reset password error:', error.message);
    res.status(500).json({ error: error.message || 'Erreur lors de l\'envoi de l\'email de réinitialisation' });
  }
};

export const confirmResetPassword = async (req, res) => {
  try {
    const { token, newPassword } = req.body;

    if (!token || !newPassword) {
      return res.status(400).json({ error: 'Token et nouveau mot de passe requis' });
    }
    if (newPassword.length < 6) {
      return res.status(400).json({ error: 'Le mot de passe doit contenir au moins 6 caractères' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const { email } = decoded;

    const [userRow] = await db.select().from(users).where(eq(users.email, email)).limit(1);
    if (!userRow) {
      return res.status(400).json({ error: 'Token invalide' });
    }

    if (userRow.resetToken !== token || (userRow.resetTokenExpiry && new Date() > userRow.resetTokenExpiry)) {
      return res.status(400).json({ error: 'Token invalide ou expiré' });
    }

    const hashedPassword = await bcrypt.hash(newPassword, 10);
    await db.update(users).set({
      password: hashedPassword,
      resetToken: null,
      resetTokenExpiry: null,
    }).where(eq(users.email, email));

    res.status(200).json({ message: 'Mot de passe réinitialisé avec succès' });
  } catch (error) {
    console.error('Confirm reset password error:', error.message);
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      return res.status(400).json({ error: 'Token invalide ou expiré' });
    }
    res.status(500).json({ error: 'Erreur lors de la réinitialisation du mot de passe' });
  }
};

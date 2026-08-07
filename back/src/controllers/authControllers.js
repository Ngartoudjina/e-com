import { auth, db } from '../config/firebase.js';

// Inscription
export const signup = async (req, res) => {
  try {
    const { email, password, firstName, lastName, phone, address } = req.body;

    // Validation des champs
    if (!email || !password || !firstName || !lastName || !phone || !address) {
      return res.status(400).json({ error: 'Tous les champs sont requis' });
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return res.status(400).json({ error: 'Email invalide' });
    }
    if (password.length < 6) {
      return res.status(400).json({ error: 'Le mot de passe doit contenir au moins 6 caractères' });
    }

    // Créer l'utilisateur dans Firebase Authentication
    const userRecord = await auth.createUser({
      email,
      password,
      displayName: `${firstName} ${lastName}`,
    });

    // Stocker les données utilisateur dans Firestore
    const userData = {
      uid: userRecord.uid,
      email,
      displayName: `${firstName} ${lastName}`,
      firstName,
      lastName,
      phone,
      address,
      role: 'client',
      createdAt: new Date().toISOString(),
      lastLogin: new Date().toISOString(),
    };
    await db.collection('users').doc(userRecord.uid).set(userData);

    // Générer un token JWT
    const token = await auth.createCustomToken(userRecord.uid);

    res.status(201).json({
      message: 'Inscription réussie',
      user: {
        uid: userRecord.uid,
        email,
        displayName: userRecord.displayName,
      },
      token,
    });
  } catch (error) {
    console.error('Erreur lors de l\'inscription:', error);
    if (error.code === 'auth/email-already-exists') {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }
    res.status(500).json({ error: 'Erreur lors de l\'inscription' });
  }
};

// Connexion
export const signin = async (req, res) => {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ error: 'Email requis' });
    }

    // Vérifier l'utilisateur dans Firebase Authentication
    let userRecord;
    try {
      userRecord = await auth.getUserByEmail(email);
    } catch (error) {
      if (error.code === 'auth/user-not-found') {
        return res.status(401).json({ error: 'Utilisateur non trouvé' });
      }
      throw error;
    }

    // Mettre à jour lastLogin dans Firestore
    const userRef = db.collection('users').doc(userRecord.uid);
    const userDoc = await userRef.get();

    if (userDoc.exists) {
      await userRef.update({
        lastLogin: new Date().toISOString(),
      });
    } else {
      // Créer un document utilisateur si inexistant
      await userRef.set({
        uid: userRecord.uid,
        email: userRecord.email,
        displayName: userRecord.displayName || '',
        role: 'client',
        createdAt: new Date().toISOString(),
        lastLogin: new Date().toISOString(),
      });
    }

    // Générer un token JWT
    const token = await auth.createCustomToken(userRecord.uid);

    res.status(200).json({
      message: 'Connexion réussie',
      user: {
        uid: userRecord.uid,
        email: userRecord.email,
        displayName: userRecord.displayName,
      },
      token,
    });
  } catch (error) {
    console.error('Erreur lors de la connexion:', error);
    res.status(500).json({ error: 'Erreur lors de la connexion' });
  }
};

// Connexion avec Google
export const signinWithGoogle = async (req, res) => {
  try {
    const { idToken } = req.body;

    if (!idToken) {
      return res.status(400).json({ error: 'Token Google requis' });
    }

    // Vérifier le token Google
    let decodedToken;
    try {
      decodedToken = await auth.verifyIdToken(idToken);
    } catch (error) {
      return res.status(401).json({ error: 'Token Google invalide' });
    }

    const uid = decodedToken.uid;
    let userRecord;
    try {
      userRecord = await auth.getUser(uid);
    } catch (error) {
      if (error.code === 'auth/user-not-found') {
        userRecord = await auth.createUser({
          uid,
          email: decodedToken.email,
          displayName: decodedToken.name || '',
          photoURL: decodedToken.picture || '',
        });
      } else {
        throw error;
      }
    }

    // Mettre à jour ou créer le document utilisateur dans Firestore
    const userRef = db.collection('users').doc(uid);
    const userDoc = await userRef.get();

    if (userDoc.exists) {
      await userRef.update({
        lastLogin: new Date().toISOString(),
        displayName: userRecord.displayName || '',
        email: userRecord.email,
        photoURL: userRecord.photoURL || '',
      });
    } else {
      await userRef.set({
        uid: userRecord.uid,
        email: userRecord.email,
        displayName: userRecord.displayName || '',
        photoURL: userRecord.photoURL || '',
        role: 'client',
        provider: 'google',
        createdAt: new Date().toISOString(),
        lastLogin: new Date().toISOString(),
      });
    }

    // Générer un token JWT
    const customToken = await auth.createCustomToken(uid);

    res.status(200).json({
      message: 'Connexion avec Google réussie',
      user: {
        uid: userRecord.uid,
        email: userRecord.email,
        displayName: userRecord.displayName,
      },
      token: customToken,
    });
  } catch (error) {
    console.error('Erreur lors de la connexion avec Google:', error);
    res.status(500).json({ error: 'Erreur lors de la connexion avec Google' });
  }
};
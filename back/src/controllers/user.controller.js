import { eq } from 'drizzle-orm';
import { users, subscribers } from '../db/schema.js';
import { db } from '../config/db.js';
import { mapUserRow } from '../services/helpers.js';

export const getProfile = async (req, res) => {
  try {
    const uid = req.user?.uid;
    if (!uid) {
      return res.status(401).json({ error: 'Utilisateur non authentifié' });
    }

    const [row] = await db.select().from(users).where(eq(users.uid, uid)).limit(1);
    if (!row) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    res.json({ success: true, user: mapUserRow(row) });
  } catch (error) {
    console.error('Get profile error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération du profil' });
  }
};

export const updateProfile = async (req, res) => {
  try {
    const uid = req.user?.uid;
    if (!uid) {
      return res.status(401).json({ error: 'Utilisateur non authentifié' });
    }

    const { firstName, lastName, name, phone, address } = req.body;

    const updates = {};
    if (firstName !== undefined) updates.firstName = firstName;
    if (lastName !== undefined) updates.lastName = lastName;
    if (name !== undefined) updates.name = name;
    if (phone !== undefined) updates.phone = phone;
    if (address !== undefined) updates.address = address;
    updates.updatedAt = new Date();

    const [row] = await db.update(users).set(updates).where(eq(users.uid, uid)).returning();
    if (!row) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    res.json({ success: true, user: mapUserRow(row), message: 'Profil mis à jour' });
  } catch (error) {
    console.error('Update profile error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la mise à jour du profil' });
  }
};

export const subscribe = async (req, res) => {
  try {
    const { email } = req.body;

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return res.status(400).json({ error: 'Email invalide' });
    }

    const [existing] = await db.select().from(subscribers).where(eq(subscribers.email, email)).limit(1);
    if (existing) {
      await db.update(subscribers).set({ optOut: false, subscribedAt: existing.subscribedAt || new Date() })
        .where(eq(subscribers.email, email));
      return res.json({ success: true, message: 'Vous êtes déjà abonné' });
    }

    await db.insert(subscribers).values({
      email,
      subscribedAt: new Date(),
      optOut: false,
    });

    res.status(201).json({ success: true, message: 'Inscription réussie à la newsletter' });
  } catch (error) {
    console.error('Subscribe error:', error.message);
    res.status(500).json({ error: 'Erreur lors de l\'inscription à la newsletter' });
  }
};

export const unsubscribe = async (req, res) => {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ error: 'Email requis' });
    }

    await db.update(subscribers).set({ optOut: true }).where(eq(subscribers.email, email));
    res.json({ success: true, message: 'Désabonnement pris en compte' });
  } catch (error) {
    console.error('Unsubscribe error:', error.message);
    res.status(500).json({ error: 'Erreur lors du désabonnement' });
  }
};

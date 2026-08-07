import jwt from 'jsonwebtoken';
import { eq } from 'drizzle-orm';
import { users } from '../db/schema.js';
import { db } from '../config/db.js';

const extractToken = (req) => {
  const authHeader = req.headers['authorization'];
  return authHeader && authHeader.split(' ')[1];
};

const verifyJwt = (req, res, next) => {
  const token = extractToken(req);
  if (!token) return res.status(401).json({ error: 'Token d\'accès requis' });

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) return res.status(403).json({ error: 'Token invalide' });
    req.user = user;
    next();
  });
};

export const authenticateToken = verifyJwt;

export const authenticateToken1 = (req, res, next) => {
  const token = extractToken(req);
  if (!token) return res.status(401).json({ error: 'Token requis' });

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) return res.status(403).json({ error: 'Token invalide' });
    req.user = user;
    next();
  });
};

export const authenticateAdmin = async (req, res, next) => {
  try {
    const token = extractToken(req);
    if (!token) return res.status(401).json({ error: 'Token d\'accès requis' });

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const [userRow] = await db.select().from(users).where(eq(users.uid, decoded.uid)).limit(1);

    if (!userRow || !userRow.isAdmin) {
      return res.status(403).json({ error: 'Accès admin requis' });
    }

    req.user = { uid: decoded.uid, ...userRow };
    next();
  } catch (error) {
    console.error('Admin auth error:', error.message);
    return res.status(403).json({ error: 'Token invalide' });
  }
};

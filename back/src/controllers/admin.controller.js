import { eq, desc, count, sql } from 'drizzle-orm';
import { products, users, referrals, subscribers } from '../db/schema.js';
import { db } from '../config/db.js';
import { uploadFile, deleteImage } from '../services/uploadService.js';
import { mapUserRow } from '../services/helpers.js';
import { transporter, generateBulkEmailTemplate } from '../services/email.js';

const parseNumber = (value, fallback) => {
  const parsed = parseFloat(value);
  return isNaN(parsed) ? fallback : parsed;
};

export const createProduct = async (req, res) => {
  try {
    const { name, description, price, category } = req.body;
    let imageUrl = req.body.imageUrl;

    if (req.file) {
      const result = await uploadFile(req.file);
      imageUrl = result.secure_url;
    }

    if (!name || !price || !imageUrl) {
      return res.status(400).json({ error: 'Nom, prix et image sont requis' });
    }

    const [row] = await db.insert(products).values({
      name,
      description: description || '',
      price: parseNumber(price, 0),
      category: category || 'Autre',
      imageUrl,
      stock: parseInt(req.body.stock, 10) || 0,
      rating: req.body.rating !== undefined ? parseNumber(req.body.rating, 0) : 0,
      isActive: req.body.isActive !== 'false',
      createdBy: req.user?.uid || null,
      createdAt: new Date(),
      updatedAt: new Date(),
    }).returning();

    res.status(201).json({ success: true, product: row, message: 'Produit créé avec succès' });
  } catch (error) {
    console.error('Create product error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la création du produit' });
  }
};

export const listAdminProducts = async (req, res) => {
  try {
    const rows = await db.select().from(products).orderBy(desc(products.createdAt));
    res.json({ success: true, products: rows, count: rows.length });
  } catch (error) {
    console.error('List admin products error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des produits' });
  }
};

export const updateProduct = async (req, res) => {
  try {
    const { id } = req.params;
    const [existing] = await db.select().from(products).where(eq(products.id, id)).limit(1);
    if (!existing) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    let imageUrl = req.body.imageUrl || existing.imageUrl;
    if (req.file) {
      const result = await uploadFile(req.file);
      imageUrl = result.secure_url;
      if (existing.imageUrl) {
        await deleteImage(existing.imageUrl).catch(() => {});
      }
    }

    const updates = {};
    if (req.body.name !== undefined) updates.name = req.body.name;
    if (req.body.description !== undefined) updates.description = req.body.description;
    if (req.body.price !== undefined) updates.price = parseNumber(req.body.price, existing.price);
    if (req.body.category !== undefined) updates.category = req.body.category;
    if (req.body.stock !== undefined) updates.stock = parseInt(req.body.stock, 10) || 0;
    if (req.body.rating !== undefined) updates.rating = parseNumber(req.body.rating, existing.rating);
    if (req.body.isActive !== undefined) updates.isActive = req.body.isActive !== 'false';
    updates.imageUrl = imageUrl;
    updates.updatedAt = new Date();

    const [row] = await db.update(products).set(updates).where(eq(products.id, id)).returning();
    res.json({ success: true, product: row, message: 'Produit mis à jour avec succès' });
  } catch (error) {
    console.error('Update product error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la mise à jour du produit' });
  }
};

export const deleteProduct = async (req, res) => {
  try {
    const { id } = req.params;
    const [existing] = await db.select().from(products).where(eq(products.id, id)).limit(1);
    if (!existing) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    await db.delete(products).where(eq(products.id, id));
    if (existing.imageUrl) {
      await deleteImage(existing.imageUrl).catch(() => {});
    }

    res.json({ success: true, message: 'Produit supprimé avec succès' });
  } catch (error) {
    console.error('Delete product error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la suppression du produit' });
  }
};

export const listAdminUsers = async (req, res) => {
  try {
    const rows = await db.select().from(users).orderBy(desc(users.createdAt));
    res.json({ success: true, users: rows.map(mapUserRow), count: rows.length });
  } catch (error) {
    console.error('List admin users error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des utilisateurs' });
  }
};

export const updateUserRole = async (req, res) => {
  try {
    const { id } = req.params;
    const { isAdmin } = req.body;

    const [row] = await db.update(users).set({ isAdmin: !!isAdmin, updatedAt: new Date() })
      .where(eq(users.id, id)).returning();

    if (!row) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }
    res.json({ success: true, user: mapUserRow(row), message: 'Rôle mis à jour' });
  } catch (error) {
    console.error('Update user role error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la mise à jour du rôle' });
  }
};

export const getAnalytics = async (req, res) => {
  try {
    const [{ value: totalUsers }] = await db.select({ value: count() }).from(users);
    const [{ value: totalProducts }] = await db.select({ value: count() }).from(products);

    const referralRows = await db.select().from(referrals);

    let totalOrders = 0;
    let totalRevenue = 0;
    const orders = [];
    for (const ref of referralRows) {
      const list = Array.isArray(ref.orders) ? ref.orders : [];
      for (const order of list) {
        totalOrders += 1;
        totalRevenue += parseFloat(order.total || order.amount || 0) || 0;
        orders.push({ ...order, affiliate: ref.affiliateName || ref.affiliateCode });
      }
    }

    const revenueByDay = {};
    for (const order of orders) {
      const day = (order.createdAt || order.date || new Date().toISOString()).slice(0, 10);
      revenueByDay[day] = (revenueByDay[day] || 0) + (parseFloat(order.total || order.amount || 0) || 0);
    }

    res.json({
      success: true,
      stats: {
        totalUsers,
        totalProducts,
        totalOrders,
        totalRevenue,
        totalAffiliates: referralRows.length,
      },
      orders: orders.slice(0, 50),
      revenueByDay,
      recentUsers: (await db.select().from(users).orderBy(desc(users.createdAt)).limit(5)).map(mapUserRow),
    });
  } catch (error) {
    console.error('Get analytics error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des statistiques' });
  }
};

export const sendBulkEmail = async (req, res) => {
  try {
    const { subject, message, imageUrl } = req.body;

    if (!subject || !message) {
      return res.status(400).json({ error: 'Sujet et message sont requis' });
    }

    const subscriberRows = await db.select().from(subscribers);
    const emails = subscriberRows
      .filter((s) => !s.optOut)
      .map((s) => s.email)
      .filter(Boolean);

    if (emails.length === 0) {
      return res.status(400).json({ error: 'Aucun abonné disponible' });
    }

    const html = generateBulkEmailTemplate(subject, message, imageUrl);

    let sent = 0;
    const errors = [];
    for (const email of emails) {
      try {
        await transporter.sendMail({
          from: `"E-com Team 🛍️" <${process.env.EMAIL_USER}>`,
          to: email,
          subject,
          html,
        });
        sent += 1;
      } catch (error) {
        errors.push({ email, error: error.message });
      }
    }

    res.json({ success: true, sent, failed: errors.length, errors });
  } catch (error) {
    console.error('Send bulk email error:', error.message);
    res.status(500).json({ error: 'Erreur lors de l\'envoi des emails' });
  }
};

export const handleUpload = async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'Aucun fichier fourni' });
    }
    const result = await uploadFile(req.file);
    res.json({ success: true, url: result.secure_url, publicId: result.public_id });
  } catch (error) {
    console.error('Upload error:', error.message);
    res.status(500).json({ error: 'Erreur lors de l\'upload' });
  }
};

import { eq, desc, and } from 'drizzle-orm';
import { affiliates, affiliateRequests, referrals } from '../db/schema.js';
import { db } from '../config/db.js';
import { uploadFile, deleteImage } from '../services/uploadService.js';
import { generateAffiliateCode } from '../services/helpers.js';

const COMMISSION_RATE = 0.05;

const publicAffiliate = (row) => {
  const { ...data } = row;
  return { id: data.id, userId: data.userId, email: data.email, name: data.name, code: data.code, commission: data.commission, status: data.status, createdAt: data.createdAt, updatedAt: data.updatedAt };
};

export const submitRequest = async (req, res) => {
  try {
    const { email, name } = req.body;
    const userId = req.user?.uid;

    if (!email || !name) {
      return res.status(400).json({ error: 'Email et nom sont requis' });
    }
    if (!req.file) {
      return res.status(400).json({ error: 'Carte d\'identité requise' });
    }

    const result = await uploadFile(req.file);

    const [existing] = await db.select().from(affiliateRequests)
      .where(and(eq(affiliateRequests.userId, userId || email), eq(affiliateRequests.status, 'pending')))
      .limit(1);
    if (existing) {
      return res.status(400).json({ error: 'Une demande est déjà en attente' });
    }

    const [row] = await db.insert(affiliateRequests).values({
      userId: userId || null,
      email,
      name,
      identityCardUrl: result.secure_url,
      identityCardPublicId: result.public_id,
      status: 'pending',
      createdAt: new Date(),
      updatedAt: new Date(),
    }).returning();

    res.status(201).json({ success: true, request: row, message: 'Demande soumise, en attente de validation' });
  } catch (error) {
    console.error('Submit affiliate request error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la soumission de la demande' });
  }
};

export const getStatus = async (req, res) => {
  try {
    const userId = req.user?.uid;
    const email = req.user?.email;

    const [affiliateRow] = await db.select().from(affiliates)
      .where(and(
        userId ? eq(affiliates.userId, userId) : eq(affiliates.email, email),
        eq(affiliates.status, 'approved'),
      ))
      .limit(1);

    const [requestRow] = await db.select().from(affiliateRequests)
      .where(and(
        userId ? eq(affiliateRequests.userId, userId) : eq(affiliateRequests.email, email),
      ))
      .orderBy(desc(affiliateRequests.createdAt))
      .limit(1);

    if (affiliateRow) {
      return res.json({ success: true, status: 'approved', affiliate: publicAffiliate(affiliateRow) });
    }
    if (requestRow) {
      return res.json({ success: true, status: requestRow.status, request: requestRow });
    }
    res.json({ success: true, status: 'none' });
  } catch (error) {
    console.error('Get affiliate status error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération du statut' });
  }
};

export const listRequests = async (req, res) => {
  try {
    const { tab } = req.params;
    const validTabs = ['pending', 'approved', 'rejected'];
    const status = validTabs.includes(tab) ? tab : 'pending';

    const rows = await db.select().from(affiliateRequests)
      .where(eq(affiliateRequests.status, status))
      .orderBy(desc(affiliateRequests.createdAt));

    res.json({ success: true, requests: rows, count: rows.length });
  } catch (error) {
    console.error('List affiliate requests error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des demandes' });
  }
};

export const approveRequest = async (req, res) => {
  try {
    const { id } = req.params;
    const [requestRow] = await db.select().from(affiliateRequests).where(eq(affiliateRequests.id, id)).limit(1);
    if (!requestRow) {
      return res.status(404).json({ error: 'Demande non trouvée' });
    }

    let code = `AFF-${Math.floor(100000 + Math.random() * 900000)}`;
    let collision = true;
    while (collision) {
      const [existing] = await db.select().from(affiliates).where(eq(affiliates.code, code)).limit(1);
      collision = !!existing;
      if (collision) code = `AFF-${Math.floor(100000 + Math.random() * 900000)}`;
    }

    const [affiliateRow] = await db.insert(affiliates).values({
      userId: requestRow.userId,
      email: requestRow.email,
      name: requestRow.name,
      code,
      commission: COMMISSION_RATE,
      status: 'approved',
      createdAt: new Date(),
      updatedAt: new Date(),
    }).returning();

    await db.update(affiliateRequests).set({ status: 'approved', updatedAt: new Date() })
      .where(eq(affiliateRequests.id, id));

    res.json({ success: true, affiliate: affiliateRow, message: 'Demande approuvée' });
  } catch (error) {
    console.error('Approve affiliate request error:', error.message);
    res.status(500).json({ error: 'Erreur lors de l\'approbation' });
  }
};

export const rejectRequest = async (req, res) => {
  try {
    const { id } = req.params;
    const [row] = await db.update(affiliateRequests)
      .set({ status: 'rejected', updatedAt: new Date() })
      .where(eq(affiliateRequests.id, id)).returning();

    if (!row) {
      return res.status(404).json({ error: 'Demande non trouvée' });
    }
    res.json({ success: true, request: row, message: 'Demande rejetée' });
  } catch (error) {
    console.error('Reject affiliate request error:', error.message);
    res.status(500).json({ error: 'Erreur lors du rejet' });
  }
};

export const deleteRequest = async (req, res) => {
  try {
    const { id } = req.params;
    const [existing] = await db.select().from(affiliateRequests).where(eq(affiliateRequests.id, id)).limit(1);

    await db.delete(affiliateRequests).where(eq(affiliateRequests.id, id));

    if (existing?.identityCardPublicId) {
      await deleteImage(existing.identityCardPublicId).catch(() => {});
    }
    res.json({ success: true, message: 'Demande supprimée' });
  } catch (error) {
    console.error('Delete affiliate request error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la suppression' });
  }
};

export const getAffiliateStats = async (req, res) => {
  try {
    const userId = req.user?.uid;
    const [affiliateRow] = await db.select().from(affiliates)
      .where(and(eq(affiliates.userId, userId), eq(affiliates.status, 'approved')))
      .limit(1);

    if (!affiliateRow) {
      return res.status(403).json({ error: 'Compte affilié non trouvé' });
    }

    const referralRows = await db.select().from(referrals)
      .where(eq(referrals.affiliateCode, affiliateRow.code));

    let totalOrders = 0;
    let totalRevenue = 0;
    for (const ref of referralRows) {
      const list = Array.isArray(ref.orders) ? ref.orders : [];
      for (const order of list) {
        totalOrders += 1;
        totalRevenue += parseFloat(order.total || order.amount || 0) || 0;
      }
    }

    res.json({
      success: true,
      stats: {
        code: affiliateRow.code,
        totalClicks: referralRows.length,
        totalOrders,
        totalRevenue,
        commissionRate: affiliateRow.commission,
        totalEarnings: totalRevenue * affiliateRow.commission,
      },
      recentReferrals: referralRows.slice(0, 20).map((r) => ({
        id: r.id,
        clickedAt: r.clickedAt,
        orders: r.orders || [],
      })),
    });
  } catch (error) {
    console.error('Get affiliate stats error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des statistiques affiliées' });
  }
};

export const trackClick = async (req, res) => {
  try {
    const { ref, productId } = req.body;

    if (!ref) {
      return res.status(400).json({ error: 'Code affilié manquant' });
    }

    const [affiliateRow] = await db.select().from(affiliates)
      .where(and(eq(affiliates.code, ref), eq(affiliates.status, 'approved')))
      .limit(1);

    await db.insert(referrals).values({
      affiliateCode: ref,
      affiliateName: affiliateRow?.name || null,
      userId: req.body.userId || null,
      productId: productId || null,
      clickedAt: new Date(),
      orders: [],
    });

    res.json({ success: true, tracked: true });
  } catch (error) {
    console.error('Track click error:', error.message);
    res.status(500).json({ error: 'Erreur lors du suivi du clic' });
  }
};

import { eq, inArray } from 'drizzle-orm';
import { likes, products } from '../db/schema.js';
import { db } from '../config/db.js';

export const toggleLike = async (req, res) => {
  try {
    const uid = req.user?.uid;
    const { productId } = req.body;

    if (!uid) {
      return res.status(401).json({ error: 'Utilisateur non authentifié' });
    }
    if (!productId) {
      return res.status(400).json({ error: 'productId requis' });
    }

    const likeId = `${uid}:${productId}`;
    const [existing] = await db.select().from(likes).where(eq(likes.id, likeId)).limit(1);

    if (existing) {
      await db.delete(likes).where(eq(likes.id, likeId));
      return res.json({ success: true, liked: false, message: 'Retiré des favoris' });
    }

    const [productRow] = await db.select().from(products).where(eq(products.id, productId)).limit(1);

    await db.insert(likes).values({
      id: likeId,
      userId: uid,
      productId,
      product: productRow || null,
      createdAt: new Date(),
    });

    res.status(201).json({ success: true, liked: true, message: 'Ajouté aux favoris' });
  } catch (error) {
    console.error('Toggle like error:', error.message);
    res.status(500).json({ error: 'Erreur lors du toggle du like' });
  }
};

export const listLikedProducts = async (req, res) => {
  try {
    const uid = req.user?.uid;
    if (!uid) {
      return res.status(401).json({ error: 'Utilisateur non authentifié' });
    }

    const rows = await db.select().from(likes).where(eq(likes.userId, uid));
    const productIds = rows.map((r) => r.productId);

    let productsById = {};
    if (productIds.length > 0) {
      const productRows = await db.select().from(products).where(inArray(products.id, productIds));
      productsById = Object.fromEntries(productRows.map((p) => [p.id, p]));
    }

    res.json({
      success: true,
      likes: rows.map((r) => ({ id: r.id, productId: r.productId, product: productsById[r.productId] || r.product, createdAt: r.createdAt })),
      likedProductIds: productIds,
    });
  } catch (error) {
    console.error('List liked products error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des favoris' });
  }
};

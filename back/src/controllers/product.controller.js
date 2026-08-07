import { and, inArray, like, eq, asc, desc, count } from 'drizzle-orm';
import { products } from '../db/schema.js';
import { db } from '../config/db.js';

const publicProduct = (row) => {
  const { createdBy, ...publicData } = row;
  return { id: row.id, ...publicData };
};

const VALID_SORT_FIELDS = ['createdAt', 'name', 'price', 'rating', 'stock'];

export const listProducts = async (req, res) => {
  try {
    const {
      page = 1,
      limit = 46,
      category = '',
      search = '',
      sortBy = 'createdAt',
      order = 'desc',
      all = 'false',
    } = req.query;

    const isAll = all.toLowerCase() === 'true' || all === true || all === 'true';

    if (!VALID_SORT_FIELDS.includes(sortBy)) {
      return res.status(400).json({ error: 'Champ de tri invalide. Utilisez : createdAt, name, price, rating, ou stock' });
    }
    if (order !== 'asc' && order !== 'desc') {
      return res.status(400).json({ error: 'Ordre invalide. Utilisez : asc ou desc' });
    }

    const parsedLimit = parseInt(limit, 10);
    const parsedPage = parseInt(page, 10);
    if (!isAll) {
      if (isNaN(parsedLimit) || parsedLimit < 1 || parsedLimit > 100) {
        return res.status(400).json({ error: 'Limite invalide. Doit être entre 1 et 100' });
      }
      if (isNaN(parsedPage) || parsedPage < 1) {
        return res.status(400).json({ error: 'Page invalide. Doit être un nombre positif' });
      }
    }

    const conditions = [];
    if (category) {
      const categoriesArray = category.split(',').map((c) => c.trim()).filter(Boolean);
      if (categoriesArray.length > 0) {
        conditions.push(inArray(products.category, categoriesArray));
      }
    }
    if (search) {
      conditions.push(like(products.name, `${search}%`));
    }
    const where = conditions.length > 0 ? and(...conditions) : undefined;

    const sortColumn = products[sortBy];
    const orderBy = order === 'asc' ? asc(sortColumn) : desc(sortColumn);

    const rows = isAll
      ? await db.select().from(products).where(where).orderBy(orderBy)
      : await db.select().from(products).where(where).orderBy(orderBy)
          .limit(parsedLimit)
          .offset((parsedPage - 1) * parsedLimit);

    const [{ value: totalItems }] = await db.select({ value: count() }).from(products).where(where);

    const list = rows.map(publicProduct);

    const pagination = isAll
      ? { currentPage: 1, itemsPerPage: list.length, totalItems, totalPages: 1, isComplete: true }
      : {
          currentPage: parsedPage,
          itemsPerPage: parsedLimit,
          totalItems,
          totalPages: Math.ceil(totalItems / parsedLimit),
          hasNextPage: parsedPage < Math.ceil(totalItems / parsedLimit),
          hasPreviousPage: parsedPage > 1,
        };

    const response = {
      products: list,
      pagination,
    };

    response.meta = {
      timestamp: new Date().toISOString(),
      filters: { category: category || null, search: search || null, sortBy, order },
      retrievedAll: isAll,
      count: list.length,
    };

    res.json(response);
  } catch (error) {
    console.error('Get public products error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des produits' });
  }
};

export const getProductById = async (req, res) => {
  try {
    const { id } = req.params;
    const [row] = await db.select().from(products).where(eq(products.id, id)).limit(1);

    if (!row) {
      return res.status(404).json({ error: 'Produit non trouvé' });
    }

    res.json(publicProduct(row));
  } catch (error) {
    console.error('Get public product error:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération du produit' });
  }
};

export const listAllSimple = async (req, res) => {
  try {
    const rows = await db.select().from(products);
    res.json({
      success: true,
      products: rows.map(publicProduct),
      count: rows.length,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error('Erreur dans /api/products/simple-all:', error.message);
    res.status(500).json({ error: 'Erreur lors de la récupération des produits', success: false });
  }
};

export const countProducts = async (req, res) => {
  try {
    const { category = '', search = '' } = req.query;

    const conditions = [];
    if (category) {
      conditions.push(eq(products.category, category));
    }
    if (search) {
      conditions.push(like(products.name, `${search}%`));
    }
    const where = conditions.length > 0 ? and(...conditions) : undefined;

    const [{ value: total }] = await db.select({ value: count() }).from(products).where(where);

    res.json({
      success: true,
      count: total,
      filters: { category, search },
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error('Erreur dans /api/products/count:', error.message);
    res.status(500).json({ error: 'Erreur lors du comptage des produits', success: false });
  }
};

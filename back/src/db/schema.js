import { pgTable, text, boolean, integer, timestamp, doublePrecision, json } from 'drizzle-orm/pg-core';
import crypto from 'crypto';

const genId = () => crypto.randomUUID();

export const users = pgTable('users', {
  uid: text('uid').primaryKey(),
  email: text('email').notNull().unique(),
  name: text('name'),
  firstName: text('first_name'),
  lastName: text('last_name'),
  phone: text('phone'),
  address: text('address'),
  password: text('password'),
  isAdmin: boolean('is_admin').notNull().default(false),
  isAffiliate: boolean('is_affiliate').notNull().default(false),
  emailVerified: boolean('email_verified').notNull().default(false),
  photoUrl: text('photo_url'),
  provider: text('provider'),
  googleId: text('google_id'),
  resetToken: text('reset_token'),
  resetTokenExpiry: timestamp('reset_token_expiry'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
  lastLogin: timestamp('last_login'),
});

export const pendingUsers = pgTable('pending_users', {
  uid: text('uid').primaryKey(),
  email: text('email').notNull(),
  name: text('name'),
  firstName: text('first_name'),
  lastName: text('last_name'),
  phone: text('phone'),
  address: text('address'),
  hashedPassword: text('hashed_password'),
  isAdmin: boolean('is_admin').notNull().default(false),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  verificationStatus: text('verification_status').notNull().default('pending'),
});

export const products = pgTable('products', {
  id: text('id').primaryKey().$defaultFn(genId),
  name: text('name').notNull(),
  price: doublePrecision('price').notNull(),
  description: text('description').notNull(),
  rating: doublePrecision('rating').notNull().default(0),
  stock: integer('stock').notNull().default(0),
  category: text('category').notNull(),
  soldCount: integer('sold_count').notNull().default(0),
  mediaUrl: text('media_url'),
  mediaPublicId: text('media_public_id'),
  createdBy: text('created_by'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const affiliates = pgTable('affiliates', {
  id: text('id').primaryKey().$defaultFn(genId),
  uid: text('uid').notNull().unique(),
  affiliateCode: text('affiliate_code').notNull().unique(),
  referralLink: text('referral_link'),
  identityCardUrl: text('identity_card_url'),
  commissionRate: doublePrecision('commission_rate').notNull().default(0.05),
  totalEarnings: doublePrecision('total_earnings').notNull().default(0),
  totalReferrals: integer('total_referrals').notNull().default(0),
  referralCount: integer('referral_count').notNull().default(0),
  isActive: boolean('is_active').notNull().default(true),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const referrals = pgTable('referrals', {
  id: text('id').primaryKey().$defaultFn(genId),
  affiliateId: text('affiliate_id').notNull(),
  referredUserId: text('referred_user_id').notNull(),
  affiliateCode: text('affiliate_code'),
  firstClickAt: timestamp('first_click_at'),
  lastClickAt: timestamp('last_click_at'),
  conversionAt: timestamp('conversion_at'),
  status: text('status').notNull().default('pending'),
  orders: json('orders').$type().default([]),
  totalValue: doublePrecision('total_value').notNull().default(0),
});

export const affiliateRequests = pgTable('affiliate_requests', {
  id: text('id').primaryKey().$defaultFn(genId),
  uid: text('uid').notNull(),
  reason: text('reason').notNull(),
  identityCardUrl: text('identity_card_url').notNull(),
  identityCardPublicId: text('identity_card_public_id'),
  status: text('status').notNull().default('pending'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
});

export const subscribers = pgTable('subscribers', {
  email: text('email').primaryKey(),
  subscribedAt: timestamp('subscribed_at').notNull().defaultNow(),
});

export const likes = pgTable('likes', {
  id: text('id').primaryKey(),
  uid: text('uid').notNull(),
  productId: text('product_id').notNull(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
});

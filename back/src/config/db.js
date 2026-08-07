import './env.js';
import { neon } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-http';
import * as schema from '../db/schema.js';

if (!process.env.DATABASE_URL) {
  console.error('DATABASE_URL manquante dans .env.local');
  process.exit(1);
}

const client = neon(process.env.DATABASE_URL);

export const db = drizzle({ client, schema });

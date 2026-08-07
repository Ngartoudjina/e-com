import './env.js';
import { initializeApp, cert } from 'firebase-admin/app';
import { getAuth } from 'firebase-admin/auth';

let firebaseConfig;

if (process.env.GOOGLE_APPLICATION_CREDENTIALS) {
  firebaseConfig = {
    credential: cert(process.env.GOOGLE_APPLICATION_CREDENTIALS),
  };
} else if (
  process.env.FIREBASE_PROJECT_ID &&
  process.env.FIREBASE_PRIVATE_KEY &&
  process.env.FIREBASE_CLIENT_EMAIL
) {
  firebaseConfig = {
    credential: cert({
      projectId: process.env.FIREBASE_PROJECT_ID,
      privateKey: process.env.FIREBASE_PRIVATE_KEY?.replace(/\\n/g, '\n'),
      clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
    }),
  };
} else {
  console.error('Configuration Firebase incomplète dans .env.local');
  process.exit(1);
}

initializeApp(firebaseConfig);

export const auth = getAuth();

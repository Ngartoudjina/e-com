import express from 'express';
import multer from 'multer';
import cloudinary from '../config/cloudinary.js';

const router = express.Router();

// Configuration de multer pour gérer les fichiers uploadés
const storage = multer.memoryStorage(); // Stocke le fichier en mémoire comme Buffer
const upload = multer({ storage });

router.post('/upload', upload.single('image'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'Aucun fichier envoyé' });
    }

    const options = {
      folder: 'products',
      tags: ['product'],
    };

    const result = await cloudinary.uploadFromFile(req.file.buffer, options);
    res.status(200).json({ secure_url: result.secure_url, public_id: result.public_id });
  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ error: error.message });
  }
});

export default router;
import '../config/env.js';
import { v2 as cloudinary } from 'cloudinary';
import sharp from 'sharp';

if (!process.env.CLOUDINARY_CLOUD_NAME || !process.env.CLOUDINARY_API_KEY || !process.env.CLOUDINARY_API_SECRET) {
  console.error('Configuration Cloudinary incomplète dans .env.local');
  process.exit(1);
}

cloudinary.config({
  cloud_name: process.env.CLOUDINARY_CLOUD_NAME,
  api_key: process.env.CLOUDINARY_API_KEY,
  api_secret: process.env.CLOUDINARY_API_SECRET,
  secure: true,
});

export const optimizeImage = (buffer) =>
  sharp(buffer)
    .resize(800, 600, { fit: 'inside', withoutEnlargement: true })
    .toFormat('webp', { quality: 80 })
    .toBuffer();

export const uploadImage = (buffer, folder = 'products') =>
  new Promise((resolve, reject) => {
    const stream = cloudinary.uploader.upload_stream(
      { folder, resource_type: 'image', format: 'webp' },
      (error, result) => (error ? reject(error) : resolve(result)),
    );
    stream.end(buffer);
  });

export const uploadFile = async (file, folder = 'products') => {
  const optimized = await optimizeImage(file.buffer);
  return uploadImage(optimized, folder);
};

export const uploadAuto = (buffer, folder = 'products') =>
  new Promise((resolve, reject) => {
    const stream = cloudinary.uploader.upload_stream(
      { folder, resource_type: 'auto' },
      (error, result) => (error ? reject(error) : resolve(result)),
    );
    stream.end(buffer);
  });

export const destroyMedia = (publicId) => cloudinary.uploader.destroy(publicId);

export const deleteImage = destroyMedia;

export default cloudinary;

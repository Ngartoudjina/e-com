// Configuration Cloudinary
const CLOUDINARY_CLOUD_NAME = 'dffo9wq7x'; // Remplacez par votre cloud name
const CLOUDINARY_UPLOAD_PRESET = 'ilrJ5_E0lZ8FuJ5cwgy2s3EKTks';

// Nécessite le package form-data pour Node.js
import FormData from 'form-data';

class CloudinaryUploader {
  constructor(cloudName, uploadPreset) {
    this.cloudName = cloudName;
    this.uploadPreset = uploadPreset;
    this.baseUrl = `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`;
  }

  async uploadFromFile(fileContent, options = {}) {
    if (!fileContent) {
      throw new Error('Aucun contenu de fichier fourni');
    }

    const formData = new FormData();
    formData.append('file', fileContent, { filename: 'image.jpg' }); // Ajout d'un nom de fichier pour compatibilité
    formData.append('upload_preset', this.uploadPreset);

    if (options?.folder) {
      formData.append('folder', options.folder);
    }

    if (options?.tags) {
      formData.append('tags', options.tags.join(','));
    }

    if (options?.transformation) {
      formData.append('transformation', options.transformation);
    }

    if (options?.context) {
      formData.append('context', Object.entries(options.context).map(([key, value]) => `${key}=${value}`).join('|'));
    }

    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(`Erreur Cloudinary: ${errorData.error?.message || 'Erreur inconnue'}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      if (error instanceof Error) {
        throw error;
      }
      throw new Error('Erreur lors de l\'upload');
    }
  }

  async uploadFromUrl(imageUrl, options = {}) {
    if (!imageUrl) {
      throw new Error('URL d\'image requise');
    }

    const formData = new FormData();
    formData.append('file', imageUrl);
    formData.append('upload_preset', this.uploadPreset);

    if (options?.folder) {
      formData.append('folder', options.folder);
    }

    if (options?.tags) {
      formData.append('tags', options.tags.join(','));
    }

    if (options?.transformation) {
      formData.append('transformation', options.transformation);
    }

    if (options?.context) {
      formData.append('context', Object.entries(options.context).map(([key, value]) => `${key}=${value}`).join('|'));
    }

    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(`Erreur Cloudinary: ${errorData.error?.message || 'Erreur inconnue'}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      if (error instanceof Error) {
        throw error;
      }
      throw new Error('Erreur lors de l\'upload depuis URL');
    }
  }

  async uploadFromBase64(base64String, options = {}) {
    if (!base64String) {
      throw new Error('String base64 requise');
    }

    const formData = new FormData();
    formData.append('file', base64String);
    formData.append('upload_preset', this.uploadPreset);

    if (options?.folder) {
      formData.append('folder', options.folder);
    }

    if (options?.tags) {
      formData.append('tags', options.tags.join(','));
    }

    if (options?.transformation) {
      formData.append('transformation', options.transformation);
    }

    if (options?.context) {
      formData.append('context', Object.entries(options.context).map(([key, value]) => `${key}=${value}`).join('|'));
    }

    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(`Erreur Cloudinary: ${errorData.error?.message || 'Erreur inconnue'}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      if (error instanceof Error) {
        throw error;
      }
      throw new Error('Erreur lors de l\'upload base64');
    }
  }

  async uploadMultiple(files, options = {}, onProgress) {
    const results = [];
    const totalFiles = files.length;

    for (let i = 0; i < files.length; i++) {
      try {
        const result = await this.uploadFromFile(files[i], options);
        results.push(result);

        if (onProgress) {
          const progress = ((i + 1) / totalFiles) * 100;
          onProgress(progress, i + 1, totalFiles);
        }
      } catch (error) {
        console.error(`Erreur upload fichier ${i + 1}:`, error);
        throw error;
      }
    }

    return results;
  }

  static generateOptimizedUrl(publicId, cloudName, options = {}) {
    const baseUrl = `https://res.cloudinary.com/${cloudName}/image/upload`;

    const transformations = [];

    if (options?.width) transformations.push(`w_${options.width}`);
    if (options?.height) transformations.push(`h_${options.height}`);
    if (options?.quality) transformations.push(`q_${options.quality}`);
    if (options?.format) transformations.push(`f_${options.format}`);
    if (options?.crop) transformations.push(`c_${options.crop}`);

    const transformString = transformations.length > 0 ? transformations.join(',') + '/' : '';

    return `${baseUrl}/${transformString}${publicId}`;
  }
}

const cloudinary = new CloudinaryUploader(CLOUDINARY_CLOUD_NAME, CLOUDINARY_UPLOAD_PRESET);

export const uploadImage = async (fileContent, options = {}) => {
  const result = await cloudinary.uploadFromFile(fileContent, options);
  return result.secure_url;
};

export const uploadImageFromUrl = async (url, options = {}) => {
  const result = await cloudinary.uploadFromUrl(url, options);
  return result.secure_url;
};

export const uploadMultipleImages = async (files, options = {}, onProgress) => {
  const results = await cloudinary.uploadMultiple(files, options, onProgress);
  return results.map(result => result.secure_url);
};

// Export de l'instance du téléchargeur
export default cloudinary;
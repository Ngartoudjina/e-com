import crypto from 'crypto';

export const generateUniqueAffiliateCode = () => {
  const timestamp = Date.now().toString(36);
  const randomBytes = crypto.randomBytes(4).toString('hex').toUpperCase();
  return `AFF-${timestamp}-${randomBytes}`;
};

export const mapUserRow = (row) => {
  if (!row) return null;
  const { password, resetToken, resetTokenExpiry, ...safe } = row;
  return safe;
};

export const generateAffiliateCode = generateUniqueAffiliateCode;

import '../config/env.js';
import nodemailer from 'nodemailer';

export const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: process.env.EMAIL_USER,
    pass: process.env.EMAIL_PASS,
  },
});

export function generateEmailTemplate(verificationLink, userName) {
  return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email - E-com</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .logo { font-size: 32px; font-weight: bold; margin-bottom: 10px; letter-spacing: -1px; }
        .header-subtitle { opacity: 0.9; font-size: 16px; font-weight: 300; }
        .content { padding: 40px 30px; }
        .welcome-title { font-size: 28px; font-weight: 700; color: #1a202c; margin-bottom: 30px; text-align: center; }
        .welcome-text { font-size: 16px; color: #4a5568; margin-bottom: 30px; text-align: center; }
        .verification-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .verification-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .verification-text { font-size: 18px; color: #2d3748; margin-bottom: 25px; font-weight: 600; }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .info-section { background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 20px; margin: 30px 0; }
        .info-title { color: #0369a1; font-weight: 600; margin-bottom: 10px; font-size: 16px; }
        .info-text { color: #0284c7; font-size: 14px; }
        .security-note { background-color: #fef7f0; border: 1px solid #fed7aa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .security-title { color: #ea580c; font-weight: 600; margin-bottom: 10px; font-size: 16px; }
        .security-text { color: #c2410c; font-size: 14px; }
        .footer { background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer-text { color: #718096; font-size: 14px; margin-bottom: 15px; }
        .divider { height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0; }
        @media (max-width: 640px) {
            .email-container { margin: 10px; border-radius: 8px; }
            .header { padding: 30px 20px; }
            .content { padding: 30px 20px; }
            .welcome-title { font-size: 24px; }
            .verification-card { padding: 20px; }
            .verify-button { padding: 14px 28px; font-size: 15px; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">E-com</div>
            <div class="header-subtitle">Votre marketplace de confiance</div>
        </div>
        <div class="content">
            <h1 class="welcome-title">Bienvenue ${userName} ! 🎉</h1>
            <p class="welcome-text">
                Merci de nous avoir rejoint ! Nous sommes ravis de vous compter parmi nos membres.
                Pour finaliser votre inscription, veuillez vérifier votre adresse email.
            </p>
            <div class="verification-card">
                <div class="verification-icon">✉️</div>
                <div class="verification-text">Vérifiez votre adresse email</div>
                <a href="${verificationLink}" class="verify-button">Vérifier mon email</a>
            </div>
            <div class="info-section">
                <div class="info-title">📋 Pourquoi vérifier votre email ?</div>
                <div class="info-text">
                    La vérification garantit la sécurité de votre compte et vous permet de recevoir
                    des notifications importantes concernant vos commandes et votre compte.
                </div>
            </div>
            <div class="security-note">
                <div class="security-title">🔒 Note de sécurité</div>
                <div class="security-text">
                    Si vous n'avez pas créé de compte sur E-com, vous pouvez ignorer cet email en toute sécurité.
                    Aucun compte ne sera créé sans votre confirmation.
                </div>
            </div>
            <div class="divider"></div>
            <p style="text-align: center; color: #718096; font-size: 14px;">
                Ce lien de vérification expire dans 24 heures pour votre sécurité.
            </p>
        </div>
        <div class="footer">
            <p class="footer-text">
                Cet email a été envoyé par E-com<br>
                Vous recevez cet email car vous vous êtes inscrit sur notre plateforme.
            </p>
            <p style="color: #a0aec0; font-size: 12px; margin-top: 20px;">
                © 2025 E-com. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
  `;
}

export function generateBulkEmailTemplate(subject, message, imageUrl) {
  return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${subject}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; color: #333; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px 30px; text-align: center; color: white; }
        .logo { font-size: 28px; font-weight: bold; }
        .content { padding: 32px 30px; }
        .message-text { font-size: 16px; color: #4a5568; line-height: 1.7; }
        .banner { width: 100%; border-radius: 10px; margin: 24px 0; }
        .footer { background-color: #f8fafc; padding: 24px 30px; text-align: center; color: #718096; font-size: 13px; border-top: 1px solid #e2e8f0; }
        @media (max-width: 640px) { .email-container { margin: 10px; } .header { padding: 24px 20px; } .content { padding: 24px 20px; } }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">E-com</div>
        </div>
        <div class="content">
            <h2 style="color: #1a202c; margin-bottom: 16px;">${subject}</h2>
            ${imageUrl ? `<img src="${imageUrl}" alt="banner" class="banner" />` : ''}
            <div class="message-text">${message.replace(/\n/g, '<br/>')}</div>
        </div>
        <div class="footer">
            <p>Vous recevez cet email car vous êtes abonné à la newsletter E-com.</p>
            <p style="margin-top: 8px;">© 2025 E-com. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
  `;
}


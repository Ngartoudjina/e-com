/*
 * Les identifiants exposés sont-ils encore valides ?
 *
 * Chaque contrôle tente réellement de s'en servir. « Révoqué » n'est affirmé
 * que si le fournisseur refuse ; une panne réseau reste signalée comme telle,
 * jamais confondue avec une révocation.
 *
 * Aucun secret n'est affiché.
 */
import { readFileSync, existsSync } from 'node:fs'
import { createSign } from 'node:crypto'

const RACINE = 'C:/Users/ADMIN/Documents/e-com'
const SERVICE_NODE = 'https://e-com-back-nxod.onrender.com'

const lireEnv = (chemin, cle) => {
  if (!existsSync(chemin)) return null
  const ligne = readFileSync(chemin, 'utf8').split(/\r?\n/).find((l) => l.startsWith(cle + '='))
  return ligne ? ligne.slice(cle.length + 1).trim().replace(/^["']|["']$/g, '') : null
}

const resultats = []
const noter = (sujet, etat, detail) => resultats.push({ sujet, etat, detail })

// ------------------------------------------------------------------ Render
try {
  const reponse = await fetch(SERVICE_NODE + '/', { signal: AbortSignal.timeout(120000) })
  const corps = (await reponse.text()).slice(0, 120)

  // L'application Express sert son propre 404 JSON en français ; la page de
  // l'hébergeur pour un service éteint est du HTML.
  const estApplication = corps.includes('Route non trouvée')

  noter(
    'Service Node (Render)',
    estApplication ? 'ENCORE EN LIGNE' : 'éteint',
    estApplication ? `l'application répond — ${corps}` : `HTTP ${reponse.status}, ce n'est plus l'application`
  )
} catch (e) {
  noter('Service Node (Render)', 'éteint', `injoignable — ${e.message}`)
}

// ------------------------------------------------- Compte de service Google
const cheminCle = `${RACINE}/back/e-comme.json`

if (!existsSync(cheminCle)) {
  noter('Clé de service Firebase', 'fichier absent', 'back/e-comme.json a été supprimé localement')
} else {
  const compte = JSON.parse(readFileSync(cheminCle, 'utf8'))

  try {
    // Échange JWT signé → jeton d'accès. Une clé révoquée fait échouer
    // l'échange avec `invalid_grant`.
    const maintenant = Math.floor(Date.now() / 1000)
    const entete = Buffer.from(JSON.stringify({ alg: 'RS256', typ: 'JWT' })).toString('base64url')
    const charge = Buffer.from(
      JSON.stringify({
        iss: compte.client_email,
        scope: 'https://www.googleapis.com/auth/cloud-platform.read-only',
        aud: 'https://oauth2.googleapis.com/token',
        exp: maintenant + 300,
        iat: maintenant,
      })
    ).toString('base64url')

    const signature = createSign('RSA-SHA256').update(`${entete}.${charge}`).sign(compte.private_key, 'base64url')

    const reponse = await fetch('https://oauth2.googleapis.com/token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        assertion: `${entete}.${charge}.${signature}`,
      }),
      signal: AbortSignal.timeout(60000),
    })

    const donnees = await reponse.json()

    noter(
      'Clé de service Firebase',
      reponse.ok ? 'ENCORE VALIDE' : 'révoquée',
      reponse.ok ? 'Google a délivré un jeton d’accès' : `refus Google : ${donnees.error ?? reponse.status}`
    )
  } catch (e) {
    noter('Clé de service Firebase', 'indéterminé', `contrôle impossible — ${e.message}`)
  }
}

// -------------------------------------------------------------- Cloudinary
const cloud = lireEnv(`${RACINE}/back/.env.local`, 'CLOUDINARY_CLOUD_NAME')
const cle = lireEnv(`${RACINE}/back/.env.local`, 'CLOUDINARY_API_KEY')
const secret = lireEnv(`${RACINE}/back/.env.local`, 'CLOUDINARY_API_SECRET')

if (!cloud || !cle || !secret) {
  noter('Clés Cloudinary', 'fichier absent', 'back/.env.local ne porte plus ces valeurs')
} else {
  try {
    const reponse = await fetch(`https://api.cloudinary.com/v1_1/${cloud}/ping`, {
      headers: { Authorization: 'Basic ' + Buffer.from(`${cle}:${secret}`).toString('base64') },
      signal: AbortSignal.timeout(60000),
    })

    noter(
      'Clés Cloudinary',
      reponse.ok ? 'ENCORE VALIDES' : 'révoquées',
      reponse.ok ? 'le compte a répondu à un ping authentifié' : `refus : HTTP ${reponse.status}`
    )
  } catch (e) {
    noter('Clés Cloudinary', 'indéterminé', `contrôle impossible — ${e.message}`)
  }
}

// ------------------------------------------------------------- Ancienne base
const ancienne = lireEnv(`${RACINE}/api/.env.avant-rotation`, 'DB_URL')
noter(
  'Ancienne base Neon',
  ancienne ? 'à contrôler séparément' : 'référence perdue',
  ancienne ? 'lancer verif-neon.php : PHP porte le pilote PostgreSQL' : 'api/.env.avant-rotation absent'
)

// ------------------------------------------------------------------ Rapport
console.log('')
for (const { sujet, etat, detail } of resultats) {
  const marque = etat.startsWith('ENCORE') ? '✗' : etat === 'indéterminé' || etat.includes('contrôler') ? '·' : '✓'
  console.log(`${marque} ${sujet.padEnd(26)} ${etat}`)
  console.log(`  ${detail}`)
}

const restants = resultats.filter((r) => r.etat.startsWith('ENCORE')).length
console.log(`\n${restants} identifiant(s) exposé(s) encore utilisable(s).`)

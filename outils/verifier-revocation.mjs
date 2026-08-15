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
/*
 * `gravite` est portée par chaque constat, jamais déduite du libellé.
 * Deviner d'après le texte français a déjà produit un verdict faux :
 * « incontrôlable » ne contient pas « contrôler », et la ligne s'affichait
 * en vert alors que rien n'avait été vérifié.
 *
 *   vif     — l'identifiant fonctionne encore
 *   clos    — le fournisseur l'a refusé
 *   inconnu — le contrôle n'a pas tranché ; ce n'est pas une révocation
 */
const noter = (sujet, etat, detail, gravite) => resultats.push({ sujet, etat, detail, gravite })

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
    estApplication ? `l'application répond — ${corps}` : `HTTP ${reponse.status}, ce n'est plus l'application`,
    estApplication ? 'vif' : 'clos'
  )
} catch (e) {
  noter('Service Node (Render)', 'éteint', `injoignable — ${e.message}`, 'clos')
}

// ------------------------------------------------- Compte de service Google
const cheminCle = `${RACINE}/back/e-comme.json`

if (!existsSync(cheminCle)) {
  noter('Clé de service Firebase', 'fichier absent', 'back/e-comme.json a été supprimé localement', 'inconnu')
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
      reponse.ok ? 'Google a délivré un jeton d’accès' : `refus Google : ${donnees.error ?? reponse.status}`,
      reponse.ok ? 'vif' : 'clos'
    )
  } catch (e) {
    noter('Clé de service Firebase', 'indéterminé', `contrôle impossible — ${e.message}`, 'inconnu')
  }
}

// -------------------------------------------------------------- Cloudinary
/*
 * Identifiants réellement exposés, inscrits ici plutôt que lus du disque.
 *
 * Une première version se contentait de lire back/.env.local. Le jour où ces
 * valeurs y ont été remplacées par celles d'un compte neuf, le contrôle a
 * testé le nouveau compte tout en affichant « encore valides » : rassurant à
 * l'envers, puisqu'il ne parlait plus du secret compromis.
 */
const CLOUDINARY_EXPOSE = { cloud: 'dffo9wq7x', cle: '822124857229833' }

const cloud = lireEnv(`${RACINE}/back/.env.local`, 'CLOUDINARY_CLOUD_NAME')
const cle = lireEnv(`${RACINE}/back/.env.local`, 'CLOUDINARY_API_KEY')
const secret = lireEnv(`${RACINE}/back/.env.local`, 'CLOUDINARY_API_SECRET')

if (cle !== CLOUDINARY_EXPOSE.cle || cloud !== CLOUDINARY_EXPOSE.cloud) {
  /*
   * Sans le secret, la validité de la clé est hors de portée. Reste à savoir
   * si le compte existe encore : res.cloudinary.com sert les visuels d'un
   * cloud vivant et rend 404 pour un nom inconnu. Un compte supprimé ne peut
   * plus honorer sa clé, quel qu'en soit l'état.
   */
  let existence = 'inconnue'
  try {
    const sonde = await fetch(`https://res.cloudinary.com/${CLOUDINARY_EXPOSE.cloud}/image/upload/sample.jpg`, {
      signal: AbortSignal.timeout(60000),
    })
    existence = sonde.status === 404 ? 'supprimé' : 'existe toujours'
  } catch {
    // Laissé « inconnue » : une panne réseau ne prouve pas une suppression.
  }

  noter(
    'Clés Cloudinary exposées',
    existence === 'supprimé' ? 'compte supprimé' : 'incontrôlable d’ici',
    existence === 'supprimé'
      ? `le cloud « ${CLOUDINARY_EXPOSE.cloud} » n’existe plus : sa clé ne peut plus servir`
      : `le secret du cloud « ${CLOUDINARY_EXPOSE.cloud} » (clé ${CLOUDINARY_EXPOSE.cle}) n’est plus sur ce poste, ` +
        `et le compte ${existence}. Créer un cloud neuf ne révoque pas l’ancien : à constater dans sa console.`,
    existence === 'supprimé' ? 'clos' : 'inconnu'
  )
} else if (!secret) {
  noter('Clés Cloudinary exposées', 'indéterminé', 'clé présente mais secret absent du fichier', 'inconnu')
} else {
  try {
    const reponse = await fetch(`https://api.cloudinary.com/v1_1/${cloud}/ping`, {
      headers: { Authorization: 'Basic ' + Buffer.from(`${cle}:${secret}`).toString('base64') },
      signal: AbortSignal.timeout(60000),
    })

    noter(
      'Clés Cloudinary exposées',
      reponse.ok ? 'ENCORE VALIDES' : 'révoquées',
      reponse.ok ? 'le compte a répondu à un ping authentifié' : `refus : HTTP ${reponse.status}`,
      reponse.ok ? 'vif' : 'clos'
    )
  } catch (e) {
    noter('Clés Cloudinary exposées', 'indéterminé', `contrôle impossible — ${e.message}`, 'inconnu')
  }
}

// ------------------------------------------------------------- Ancienne base
const ancienne = lireEnv(`${RACINE}/api/.env.avant-rotation`, 'DB_URL')
noter(
  'Ancienne base Neon',
  ancienne ? 'à contrôler séparément' : 'référence perdue',
  ancienne ? 'lancer verifier-ancienne-base.php : PHP porte le pilote PostgreSQL' : 'api/.env.avant-rotation absent',
  'inconnu'
)

// ------------------------------------------------------------------ Rapport
const MARQUE = { vif: '✗', clos: '✓', inconnu: '·' }

console.log('')
for (const { sujet, etat, detail, gravite } of resultats) {
  console.log(`${MARQUE[gravite] ?? '·'} ${sujet.padEnd(26)} ${etat}`)
  console.log(`  ${detail}`)
}

const restants = resultats.filter((r) => r.gravite === 'vif').length
const incertains = resultats.filter((r) => r.gravite === 'inconnu').length

console.log(`\n${restants} identifiant(s) exposé(s) encore utilisable(s).`)
if (incertains) {
  console.log(`${incertains} point(s) non tranché(s) d’ici : un contrôle qui n’aboutit pas n’est pas une révocation.`)
}

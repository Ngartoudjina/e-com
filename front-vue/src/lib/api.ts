import axios, { type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'

/**
 * Base de l'API.
 *
 * Configurable par VITE_API_BASE afin de pouvoir viser un backend local en
 * développement : l'instance déployée refuse l'origine localhost (CORS), ce
 * qui rendait le catalogue vide en local sans erreur visible à l'écran.
 */
export const API_BASE = import.meta.env.VITE_API_BASE || 'https://e-com-back-nxod.onrender.com'

export const api = axios.create({
  baseURL: API_BASE,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/* ==================================================================
   Cache des lectures
   ------------------------------------------------------------------
   Les réponses GET du catalogue sont conservées le temps de la session.
   Sans cela, chaque aller-retour entre la grille et une fiche produit
   relançait la même requête — coûteuse, l'API répondant en plusieurs
   secondes.

   Deux propriétés importantes :
   - les requêtes identiques lancées en parallèle partagent une seule
     réponse réseau, au lieu de partir en double ;
   - toute écriture (POST, PUT, PATCH, DELETE) vide le cache, pour ne
     jamais réafficher un état que l'on vient de modifier.
================================================================== */

interface Entree {
  expiration: number
  donnees: unknown
}

/** Durée alignée sur celle du cache serveur. */
const DUREE_MS = 5 * 60 * 1000

const cache = new Map<string, Entree>()
const enVol = new Map<string, Promise<unknown>>()

/** Seules les lectures du catalogue sont mises en cache. */
const CHEMINS_CACHABLES = ['/api/products', '/api/admin/analytics', '/api/settings']

const estCachable = (url: string) => CHEMINS_CACHABLES.some((chemin) => url.startsWith(chemin))

const cleDe = (url: string, params?: Record<string, unknown>) => {
  if (!params) return url
  const tri = Object.keys(params).sort()
  const paire = tri.map((cle) => `${cle}=${String(params[cle])}`).join('&')
  return paire ? `${url}?${paire}` : url
}

export const viderCacheApi = () => {
  cache.clear()
  enVol.clear()
}

/**
 * GET mis en cache.
 * Renvoie la même forme qu'axios pour rester interchangeable avec `api.get`.
 */
export const getCache = async <T = unknown>(
  url: string,
  params?: Record<string, unknown>
): Promise<{ data: T }> => {
  const cle = cleDe(url, params)

  if (estCachable(url)) {
    const entree = cache.get(cle)
    if (entree && entree.expiration > Date.now()) {
      return { data: entree.donnees as T }
    }

    // Une requête identique est déjà partie : on attend sa réponse.
    const enCours = enVol.get(cle)
    if (enCours) {
      return { data: (await enCours) as T }
    }
  }

  const promesse = api
    .get(url, { params })
    .then((reponse: AxiosResponse) => {
      if (estCachable(url)) {
        cache.set(cle, { expiration: Date.now() + DUREE_MS, donnees: reponse.data })
      }
      return reponse.data
    })
    .finally(() => {
      enVol.delete(cle)
    })

  if (estCachable(url)) {
    enVol.set(cle, promesse)
  }

  return { data: (await promesse) as T }
}

/** Une écriture rend le cache de lecture caduc. */
api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const methode = (config.method ?? 'get').toLowerCase()
  if (['post', 'put', 'patch', 'delete'].includes(methode)) {
    viderCacheApi()
  }
  return config
})

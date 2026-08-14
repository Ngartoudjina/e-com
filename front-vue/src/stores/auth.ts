import { defineStore } from 'pinia'
import { api } from '@/lib/api'

export interface Utilisateur {
  uid: string
  email: string
  name: string | null
  firstName: string | null
  lastName: string | null
  isAdmin: boolean
  isAffiliate: boolean
  emailVerified: boolean
  photoUrl: string | null
}

const CLE_JETON = 'token'
const CLE_UTILISATEUR = 'goldshop:utilisateur'

/**
 * Dernier profil connu, relu au démarrage.
 *
 * Sans lui, l'interface ignore qui est connecté jusqu'au retour de
 * /api/auth/me — plusieurs secondes pendant lesquelles un administrateur ne
 * voit pas son entrée vers l'administration, et l'espace client s'affiche
 * vide. Le profil restauré n'est qu'une hypothèse : il est revalidé auprès du
 * serveur juste après, et effacé si le jeton n'est plus valable.
 *
 * Il ne sert jamais à autoriser quoi que ce soit : les droits sont vérifiés à
 * chaque requête par le middleware côté API.
 */
const lireProfil = (): Utilisateur | null => {
  try {
    const brut = localStorage.getItem(CLE_UTILISATEUR)
    return brut ? (JSON.parse(brut) as Utilisateur) : null
  } catch {
    return null
  }
}

const ecrireProfil = (utilisateur: Utilisateur | null) => {
  try {
    if (utilisateur) {
      localStorage.setItem(CLE_UTILISATEUR, JSON.stringify(utilisateur))
    } else {
      localStorage.removeItem(CLE_UTILISATEUR)
    }
  } catch {
    // Stockage indisponible : on se contente de la mémoire.
  }
}

/**
 * Authentification par jeton Sanctum.
 *
 * Le backend est passé de Firebase à Sanctum : ce store interroge désormais
 * /api/auth/*. Le jeton est conservé sous la clé « token », que l'intercepteur
 * de lib/api pose en en-tête Authorization.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    // Profil restauré immédiatement : l'interface est juste dès le premier rendu.
    user: localStorage.getItem(CLE_JETON) ? lireProfil() : null,
    loading: true,
    initialized: false,
    /**
     * Revalidation en cours auprès du serveur.
     * Le profil restauré du stockage local sert à l'affichage ; toute
     * décision d'accès doit attendre cette promesse.
     */
    verification: null as Promise<void> | null,
  }),

  getters: {
    isAdmin: (state) => state.user?.isAdmin === true,
    estConnecte: (state) => state.user !== null,
  },

  actions: {
    /**
     * Restaure la session au démarrage.
     * Sans jeton, on n'appelle pas l'API : inutile de provoquer un 401.
     */
    async init() {
      if (this.initialized) return
      this.initialized = true

      const jeton = localStorage.getItem(CLE_JETON)
      if (!jeton) {
        this.loading = false
        return
      }

      // Un profil restauré suffit à afficher l'interface pendant la
      // revalidation : inutile de bloquer sur `loading`.
      if (this.user) this.loading = false

      this.verification = (async () => {
        try {
          const reponse = await api.get('/api/auth/me')
          this.user = reponse.data.user
          ecrireProfil(this.user)
        } catch {
          // Jeton expiré ou révoqué : on repart d'une session propre.
          localStorage.removeItem(CLE_JETON)
          ecrireProfil(null)
          this.user = null
        } finally {
          this.loading = false
        }
      })()

      await this.verification
    },

    async connexion(email: string, motDePasse: string) {
      const reponse = await api.post('/api/auth/login', { email, password: motDePasse })
      localStorage.setItem(CLE_JETON, reponse.data.token)
      this.user = reponse.data.user
      ecrireProfil(this.user)
      this.loading = false
      return this.user
    },

    async inscription(donnees: {
      email: string
      password: string
      name: string
      firstName: string
      lastName: string
      phone: string
      address: string
    }) {
      const reponse = await api.post('/api/auth/register', donnees)
      return reponse.data
    },

    async demanderReinitialisation(email: string) {
      const reponse = await api.post('/api/auth/reset-password', { email })
      return reponse.data
    },

    async deconnexion() {
      try {
        await api.post('/api/auth/logout')
      } catch {
        // Le jeton était peut-être déjà invalide : la session locale part quand même.
      }
      localStorage.removeItem(CLE_JETON)
      ecrireProfil(null)
      this.user = null
      this.verification = null
    },
  },
})

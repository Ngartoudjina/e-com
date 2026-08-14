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

/**
 * Authentification par jeton Sanctum.
 *
 * Le backend est passé de Firebase à Sanctum : ce store interroge désormais
 * /api/auth/*. Le jeton est conservé sous la clé « token », que l'intercepteur
 * de lib/api pose en en-tête Authorization.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as Utilisateur | null,
    loading: true,
    initialized: false,
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

      try {
        const reponse = await api.get('/api/auth/me')
        this.user = reponse.data.user
      } catch {
        // Jeton expiré ou révoqué : on repart d'une session propre.
        localStorage.removeItem(CLE_JETON)
        this.user = null
      } finally {
        this.loading = false
      }
    },

    async connexion(email: string, motDePasse: string) {
      const reponse = await api.post('/api/auth/login', { email, password: motDePasse })
      localStorage.setItem(CLE_JETON, reponse.data.token)
      this.user = reponse.data.user
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
      this.user = null
    },
  },
})

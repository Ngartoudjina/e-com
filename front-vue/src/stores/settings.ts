import { defineStore } from 'pinia'
import { getCache } from '@/lib/api'

export interface ModeLivraison {
  key: string
  label: string
  detail: string
  price: number
}

export interface Reglages {
  currency: string
  freeShippingThreshold: number
  shippingMethods: ModeLivraison[]
  vatRate: number
  announcements: string[]
  returnDays: number
}

/**
 * Réglages commerciaux, servis par GET /api/settings.
 *
 * Le seuil de franco et les frais de port étaient recopiés dans le bandeau
 * d'annonces et dans le panier, sans garantie qu'ils restent d'accord entre
 * eux ni avec le calcul de commande. Ils viennent désormais du serveur, qui
 * est aussi celui qui facture.
 *
 * Les valeurs de repli ne servent qu'au tout premier rendu, avant la réponse.
 */
const REPLI: Reglages = {
  currency: 'EUR',
  freeShippingThreshold: 150,
  shippingMethods: [
    { key: 'standard', label: 'Standard · 2 à 3 jours', detail: 'Réception estimée sous 3 jours ouvrés', price: 6.9 },
  ],
  vatRate: 0.2,
  announcements: [],
  returnDays: 30,
}

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    reglages: REPLI,
    charge: false,
  }),

  getters: {
    franco: (state) => state.reglages.freeShippingThreshold,
    tauxTva: (state) => state.reglages.vatRate,
    annonces: (state) => state.reglages.announcements,
    modes: (state) => state.reglages.shippingMethods,
  },

  actions: {
    async charger() {
      if (this.charge) return

      try {
        const reponse = await getCache<Reglages>('/api/settings')
        this.reglages = { ...REPLI, ...reponse.data }
        this.charge = true
      } catch {
        // L'API est injoignable : on garde les valeurs de repli plutôt que
        // d'afficher une boutique sans conditions de livraison.
      }
    },

    /** Frais applicables, franco compris. Le serveur refait ce calcul. */
    fraisDePort(cle: string, montant: number): number {
      if (montant >= this.franco) return 0
      const mode = this.modes.find((m) => m.key === cle) ?? this.modes[0]
      return mode?.price ?? 0
    },
  },
})

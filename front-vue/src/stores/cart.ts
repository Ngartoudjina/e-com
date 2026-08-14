import { defineStore } from 'pinia'
import type { ProductWithDetails } from '@/types'

const CLE_STOCKAGE = 'goldshop:panier'

/**
 * Le panier est conservé dans le navigateur.
 * Sans cela il se vidait à chaque rechargement : un visiteur qui rafraîchit
 * la page, revient en arrière ou rouvre l'onglet perdait sa sélection.
 */
const lireStockage = (): ProductWithDetails[] => {
  try {
    const brut = localStorage.getItem(CLE_STOCKAGE)
    if (!brut) return []
    const valeur = JSON.parse(brut)
    return Array.isArray(valeur) ? valeur : []
  } catch {
    // Contenu illisible ou stockage indisponible (navigation privée stricte).
    return []
  }
}

const ecrireStockage = (articles: ProductWithDetails[]) => {
  try {
    localStorage.setItem(CLE_STOCKAGE, JSON.stringify(articles))
  } catch {
    // Quota dépassé ou stockage refusé : le panier reste valable en mémoire.
  }
}

/**
 * Une même référence dans deux tailles ou deux coloris compte pour deux
 * lignes distinctes : la clé de ligne combine les trois.
 */
const cleLigne = (article: Pick<ProductWithDetails, 'id' | 'selectedSize' | 'selectedColor'>) =>
  `${article.id}::${article.selectedSize ?? ''}::${article.selectedColor ?? ''}`

export const useCartStore = defineStore('cart', {
  state: () => ({
    cartItems: lireStockage(),
  }),

  getters: {
    totalItems: (state) => state.cartItems.reduce((somme, article) => somme + (article.quantity || 0), 0),
    totalCount: (state) => state.cartItems.reduce((somme, article) => somme + (article.quantity || 1), 0),
    sousTotal: (state) =>
      state.cartItems.reduce((somme, article) => somme + article.price * (article.quantity || 1), 0),
  },

  actions: {
    addToCart(produit: ProductWithDetails) {
      const cle = cleLigne(produit)
      const existant = this.cartItems.find((article) => cleLigne(article) === cle)

      if (existant) {
        existant.quantity = (existant.quantity || 1) + (produit.quantity || 1)
      } else {
        this.cartItems.push({
          ...produit,
          quantity: produit.quantity || 1,
          originalPrice: produit.originalPrice ?? undefined,
          isNew: produit.isNew ?? false,
          selectedColor: produit.selectedColor ?? 'default',
          selectedSize: produit.selectedSize ?? 'M',
        })
      }

      this.enregistrer()
    },

    removeFromCart(id: string, taille?: string, couleur?: string) {
      // Sans taille ni coloris, on retire toutes les lignes de la référence.
      this.cartItems = this.cartItems.filter((article) => {
        if (article.id !== id) return true
        if (taille === undefined && couleur === undefined) return false
        return article.selectedSize !== taille || article.selectedColor !== couleur
      })
      this.enregistrer()
    },

    updateQuantity(id: string, quantity: number, taille?: string, couleur?: string) {
      const quantiteSure = Math.max(1, Math.floor(quantity) || 1)

      for (const article of this.cartItems) {
        if (article.id !== id) continue
        if (taille !== undefined && article.selectedSize !== taille) continue
        if (couleur !== undefined && article.selectedColor !== couleur) continue
        article.quantity = quantiteSure
      }

      this.enregistrer()
    },

    clearCart() {
      this.cartItems = []
      this.enregistrer()
    },

    enregistrer() {
      ecrireStockage(this.cartItems)
    },
  },
})

/**
 * Vocabulaire partagé des commandes.
 * Les mêmes libellés servent à l'espace client et à l'administration : ils ne
 * doivent pas diverger d'un écran à l'autre.
 */

export interface LigneCommande {
  id: string
  productId: string | null
  name: string
  reference: string | null
  color: string | null
  size: string | null
  mediaUrl: string | null
  unitPrice: number
  quantity: number
  lineTotal: number
}

export interface Commande {
  id: string
  reference: string
  status: string
  email: string
  phone: string | null
  subtotal: number
  discount: number
  shipping: number
  total: number
  promoCode: string | null
  shippingMethod: string
  shippingAddress: {
    name: string | null
    address: string | null
    postalCode: string | null
    city: string | null
    country: string | null
  }
  placedAt: string | null
  shippedAt: string | null
  deliveredAt: string | null
  cancelledAt: string | null
  itemCount: number
  items: LigneCommande[]
}

export const ETATS_COMMANDE: Record<string, { libelle: string; classe: string }> = {
  pending: { libelle: 'En attente de paiement', classe: 'text-warning' },
  paid: { libelle: 'Payée', classe: 'text-success' },
  preparing: { libelle: 'En préparation', classe: 'text-warning' },
  shipped: { libelle: 'Expédiée', classe: 'text-action' },
  delivered: { libelle: 'Livrée', classe: 'text-success' },
  cancelled: { libelle: 'Annulée', classe: 'text-ink-500' },
  refunded: { libelle: 'Remboursée', classe: 'text-ink-500' },
}

/**
 * Transitions proposées à l'administrateur.
 * Le serveur les contraint également : cette liste sert à ne montrer que des
 * actions possibles, pas à faire foi.
 */
export const ACTIONS_COMMANDE: Record<string, { statut: string; libelle: string; principale?: boolean }[]> = {
  pending: [
    { statut: 'paid', libelle: 'Marquer comme payée', principale: true },
    { statut: 'cancelled', libelle: 'Annuler' },
  ],
  paid: [
    { statut: 'preparing', libelle: 'Mettre en préparation', principale: true },
    { statut: 'cancelled', libelle: 'Annuler' },
    { statut: 'refunded', libelle: 'Rembourser' },
  ],
  preparing: [
    { statut: 'shipped', libelle: 'Marquer comme expédiée', principale: true },
    { statut: 'cancelled', libelle: 'Annuler' },
    { statut: 'refunded', libelle: 'Rembourser' },
  ],
  shipped: [
    { statut: 'delivered', libelle: 'Marquer comme livrée', principale: true },
    { statut: 'refunded', libelle: 'Rembourser' },
  ],
  delivered: [{ statut: 'refunded', libelle: 'Rembourser' }],
  cancelled: [],
  refunded: [],
}

export const formatDateCourte = (valeur: string | null): string =>
  valeur
    ? new Date(valeur).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
    : '—'

export const formatDateLongue = (valeur: string | null): string =>
  valeur
    ? new Date(valeur).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : '—'

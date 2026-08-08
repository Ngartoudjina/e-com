/**
 * Formatage des prix.
 *
 * La maquette affiche « 490,00 € » : séparateur décimal français, deux
 * décimales toujours présentes, espace insécable avant le symbole. Les chiffres
 * sont rendus en chasse tabulaire (classe `.t-price`) pour que les colonnes de
 * prix s'alignent verticalement dans les grilles et les tableaux.
 */
const formateur = new Intl.NumberFormat('fr-FR', {
  style: 'currency',
  currency: 'EUR',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

export const formatPrix = (montant: number): string => formateur.format(montant ?? 0)

/** Variante sans décimales, pour les curseurs de filtre (« 150 € »). */
const formateurCourt = new Intl.NumberFormat('fr-FR', {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
})

export const formatPrixCourt = (montant: number): string => formateurCourt.format(montant ?? 0)

/** « 163,33 € × 3 sans frais » — le paiement fractionné annoncé sur la fiche. */
export const formatFractionne = (montant: number, fois = 3): string =>
  `${formatPrix(montant / fois)} × ${fois} sans frais`

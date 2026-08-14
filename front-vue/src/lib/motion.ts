import gsap from 'gsap'

/**
 * Mouvement du système GOLDSHOP, piloté par GSAP.
 *
 * Les durées et les courbes ne sont pas choisies ici : elles viennent de la
 * planche « Rayons, élévation, mouvement ». GSAP sert à les appliquer avec
 * un décalage et une orchestration que le CSS seul rend pénibles — il
 * n'introduit aucune valeur nouvelle.
 *
 *   120 ms  survol, pression
 *   200 ms  champs, puces, bascules
 *   320 ms  panneaux, modales, tiroir
 *   560 ms  révélation au défilement
 *   sortie  cubic-bezier(.2,.8,.2,1)
 *   entrée  cubic-bezier(.4,0,.2,1)
 *   décalage 60 ms par carte, 6 au plus
 */
export const DUREE = {
  pression: 0.12,
  controle: 0.2,
  panneau: 0.32,
  revelation: 0.56,
} as const

export const COURBE = {
  sortie: 'cubic-bezier(0.2, 0.8, 0.2, 1)',
  entree: 'cubic-bezier(0.4, 0, 0.2, 1)',
} as const

export const DECALAGE = 0.06
export const DECALAGE_MAX = 6

/** L'utilisateur peut avoir demandé à réduire les animations. */
export const mouvementReduit = (): boolean =>
  typeof window !== 'undefined' &&
  window.matchMedia('(prefers-reduced-motion: reduce)').matches

/**
 * Décalage d'apparition, plafonné : au-delà de six cartes, la dernière
 * n'attendrait plus rien de perceptible et la page semblerait lente.
 */
export const decalagePour = (index: number): number =>
  Math.min(index, DECALAGE_MAX) * DECALAGE

/** Révélation d'un élément entrant dans le champ. */
export const reveler = (element: Element, index = 0): gsap.core.Tween | null => {
  if (mouvementReduit()) {
    gsap.set(element, { opacity: 1, y: 0 })
    return null
  }

  return gsap.fromTo(
    element,
    { opacity: 0, y: 16 },
    {
      opacity: 1,
      y: 0,
      duration: DUREE.revelation,
      ease: COURBE.sortie,
      delay: decalagePour(index),
      clearProps: 'transform',
    }
  )
}

/**
 * Compte jusqu'à une valeur. Utilisé pour les indicateurs de
 * l'administration, où le chiffre est l'information principale.
 */
export const compterJusqua = (
  cible: { valeur: number },
  valeur: number,
  onUpdate: (courant: number) => void
): gsap.core.Tween | null => {
  if (mouvementReduit() || valeur === 0) {
    cible.valeur = valeur
    onUpdate(valeur)
    return null
  }

  return gsap.to(cible, {
    valeur,
    duration: DUREE.revelation,
    ease: COURBE.sortie,
    onUpdate: () => onUpdate(cible.valeur),
  })
}

/** Entrée d'un panneau : modale, tiroir, feuille inférieure. */
export const ouvrirPanneau = (element: Element, depuis: 'bas' | 'centre' = 'centre') => {
  if (mouvementReduit()) return null

  const depart = depuis === 'bas' ? { y: '100%' } : { opacity: 0, scale: 0.98, y: 12 }
  const arrivee = depuis === 'bas' ? { y: '0%' } : { opacity: 1, scale: 1, y: 0 }

  return gsap.fromTo(element, depart, {
    ...arrivee,
    duration: DUREE.panneau,
    ease: COURBE.sortie,
  })
}

export { gsap }

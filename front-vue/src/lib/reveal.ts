import type { Directive } from 'vue'
import { decalagePour, DUREE, gsap, mouvementReduit, COURBE } from './motion'

/**
 * v-reveal — révèle l'élément quand il entre dans le champ.
 *
 * Usage : <div v-reveal /> ou <div v-reveal="2" /> pour l'index dans une
 * grille, dont découle le décalage (60 ms par carte, 6 au plus).
 *
 * L'animation est jouée par GSAP plutôt que par une transition CSS : le
 * décalage se calcule par élément, et l'animation est proprement interrompue
 * si le composant disparaît avant la fin.
 */
const suivis = new WeakMap<Element, { observateur: IntersectionObserver; animation?: gsap.core.Tween }>()

/**
 * Largeur en deçà de laquelle le contenu s'affiche sans mise en scène.
 *
 * Deux raisons. La révélation pose `opacity: 0` puis s'en remet à
 * l'observateur : s'il ne se déclenche pas, le contenu reste invisible, et
 * c'est sur téléphone que ce risque est le plus concret — défilement rapide,
 * éléments plus hauts que la fenêtre. Ensuite, faire apparaître chaque carte
 * l'une après l'autre allonge la perception du chargement là où l'écran n'en
 * montre que deux à la fois : ce qui donne du rythme sur un grand écran
 * ressemble à de la lenteur sur un petit.
 */
const LARGEUR_MINIMALE = 768

export const vReveal: Directive<HTMLElement, number | undefined> = {
  mounted(el, binding) {
    if (mouvementReduit()) return
    if (window.innerWidth < LARGEUR_MINIMALE) return

    // État de départ posé immédiatement : sans cela l'élément clignote
    // entre le rendu et le déclenchement de l'observateur.
    gsap.set(el, { opacity: 0, y: 16 })

    const index = Number(binding.value ?? 0)
    // La valeur transmise est un index de carte ; les anciens appels passaient
    // un délai en millisecondes, qu'on ramène à un index.
    const rang = index > 20 ? Math.round(index / 60) : index

    const observateur = new IntersectionObserver(
      (entrees) => {
        for (const entree of entrees) {
          if (!entree.isIntersecting) continue

          const animation = gsap.to(el, {
            opacity: 1,
            y: 0,
            duration: DUREE.revelation,
            ease: COURBE.sortie,
            delay: decalagePour(rang),
            clearProps: 'transform',
          })

          suivis.set(el, { observateur, animation })
          observateur.disconnect()
        }
      },
      { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    )

    observateur.observe(el)
    suivis.set(el, { observateur })
  },

  unmounted(el) {
    const suivi = suivis.get(el)
    suivi?.observateur.disconnect()
    // Une animation encore en cours continuerait de toucher un nœud détaché.
    suivi?.animation?.kill()
    suivis.delete(el)
  },
}

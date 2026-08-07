import type { Directive } from 'vue'

/**
 * v-reveal — révèle l'élément quand il entre dans le viewport.
 * Usage : <div v-reveal />  ou  <div v-reveal="120" /> pour un délai en ms.
 */
const observed = new WeakMap<Element, IntersectionObserver>()

export const vReveal: Directive<HTMLElement, number | undefined> = {
  mounted(el, binding) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

    el.dataset.reveal = 'out'
    if (binding.value) el.style.setProperty('--reveal-delay', `${binding.value}ms`)

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue
          el.dataset.reveal = 'in'
          observer.disconnect()
          observed.delete(el)
        }
      },
      { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    )

    observer.observe(el)
    observed.set(el, observer)
  },
  unmounted(el) {
    observed.get(el)?.disconnect()
    observed.delete(el)
  },
}

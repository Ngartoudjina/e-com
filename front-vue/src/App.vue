<template>
  <div class="min-h-screen bg-paper">
    <!--
      Transition entre les pages, jouée par GSAP.
      Volontairement discrète : une opacité et huit pixels de translation, sur
      la durée « panneau » du système. Le contenu marchand ne doit pas se faire
      attendre pour un effet.
    -->
    <RouterView v-slot="{ Component, route }">
      <Transition
        mode="out-in"
        :css="false"
        @enter="entrer"
        @leave="sortir"
      >
        <component :is="Component" :key="route.path" />
      </Transition>
    </RouterView>

    <Toaster />
  </div>
</template>

<script setup lang="ts">
import Toaster from '@/components/common/Toaster.vue'
import { COURBE, DUREE, gsap, mouvementReduit } from '@/lib/motion'

const entrer = (element: Element, terminer: () => void) => {
  if (mouvementReduit()) {
    terminer()
    return
  }

  gsap.fromTo(
    element,
    { opacity: 0, y: 8 },
    {
      opacity: 1,
      y: 0,
      duration: DUREE.panneau,
      ease: COURBE.sortie,
      onComplete: terminer,
      // La translation résiduelle casserait `position: sticky` des en-têtes.
      clearProps: 'transform',
    }
  )
}

const sortir = (element: Element, terminer: () => void) => {
  if (mouvementReduit()) {
    terminer()
    return
  }

  gsap.to(element, {
    opacity: 0,
    duration: DUREE.controle,
    ease: COURBE.entree,
    onComplete: terminer,
  })
}
</script>

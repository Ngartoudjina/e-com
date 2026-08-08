<template>
  <!--
    Feuille inférieure des filtres, propre au mobile.
    Elle réutilise CatalogFilters plutôt que d'en dupliquer les contrôles :
    seuls le contenant et le pied d'action changent.
  -->
  <Teleport to="body">
    <Transition name="voile">
      <div
        v-if="ouvert"
        class="fixed inset-0 z-50 bg-ink-900/60 lg:hidden"
        aria-hidden="true"
        @click="$emit('fermer')"
      />
    </Transition>

    <Transition name="feuille">
      <section
        v-if="ouvert"
        class="fixed inset-x-0 bottom-0 z-50 flex max-h-[88dvh] flex-col bg-paper lg:hidden"
        style="border-radius: var(--radius-4) var(--radius-4) 0 0"
        role="dialog"
        aria-modal="true"
        aria-label="Filtrer les résultats"
      >
        <!-- Poignée de préhension -->
        <div class="flex justify-center pt-3">
          <span class="h-1 w-10 rounded-full bg-rule" aria-hidden="true" />
        </div>

        <div class="flex-1 overflow-y-auto px-5 pb-4 pt-4">
          <slot />
        </div>

        <!-- Pied d'action : réinitialiser à gauche, confirmation à droite. -->
        <div
          class="flex gap-3 border-t border-rule bg-paper px-5 py-4"
          style="padding-bottom: calc(16px + env(safe-area-inset-bottom))"
        >
          <button type="button" class="btn btn-secondary flex-1" @click="$emit('reinitialiser')">
            Réinitialiser
          </button>
          <button type="button" class="btn btn-primary flex-[1.6]" @click="$emit('fermer')">
            Voir les {{ resultats }} pièces
          </button>
        </div>
      </section>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'

const props = defineProps<{ ouvert: boolean; resultats: number }>()
const emit = defineEmits<{ fermer: []; reinitialiser: [] }>()

// La page ne doit pas défiler derrière la feuille.
watch(
  () => props.ouvert,
  (ouvert) => {
    document.body.style.overflow = ouvert ? 'hidden' : ''
  }
)

const surEchap = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.ouvert) emit('fermer')
}
window.addEventListener('keydown', surEchap)

onBeforeUnmount(() => {
  window.removeEventListener('keydown', surEchap)
  document.body.style.overflow = ''
})
</script>

<style scoped>
@reference "../../index.css";

.voile-enter-active,
.voile-leave-active {
  transition: opacity var(--duration-panel) var(--ease-exit);
}
.voile-enter-from,
.voile-leave-to {
  opacity: 0;
}

/* La feuille monte depuis le bas — 320 ms, courbe de sortie. */
.feuille-enter-active,
.feuille-leave-active {
  transition: transform var(--duration-panel) var(--ease-exit);
}
.feuille-enter-from,
.feuille-leave-to {
  transform: translateY(100%);
}
</style>

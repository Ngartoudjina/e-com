<template>
  <div
    class="pointer-events-none fixed inset-x-4 bottom-4 z-[9999] flex flex-col items-center gap-2 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:items-end"
    role="status"
    aria-live="polite"
  >
    <TransitionGroup name="toast">
      <button
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        type="button"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 border border-rule bg-ink-900 px-5 py-4 text-left text-paper shadow-e2"
        @click="toastStore.remove(toast.id)"
      >
        <!-- Rayon 0 et fond encre : le message emprunte au reste du système. -->
        <span aria-hidden="true" class="t-small shrink-0 pt-px" :class="marque[toast.type].classe">
          {{ marque[toast.type].signe }}
        </span>
        <span class="t-body flex-1 text-paper">{{ toast.message }}</span>
        <X class="mt-px size-4 shrink-0 opacity-60" />
      </button>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

const marque = {
  success: { signe: '●', classe: 'text-success' },
  error: { signe: '●', classe: 'text-error' },
  info: { signe: '●', classe: 'text-paper/70' },
} as const
</script>

<style scoped>
@reference "@/index.css";

/* Durée « panneau » et courbe de sortie du système de mouvement. */
.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 200ms var(--ease-exit),
    transform 320ms var(--ease-exit);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

.toast-leave-active {
  position: absolute;
}

@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active {
    transition: opacity 120ms linear;
  }

  .toast-enter-from,
  .toast-leave-to {
    transform: none;
  }
}
</style>

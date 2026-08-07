<template>
  <div class="fixed bottom-6 right-5 z-40 flex flex-col items-end gap-3 sm:bottom-8 sm:right-8" :class="className">
    <!-- Actions secondaires -->
    <TransitionGroup name="fab-item" tag="div" class="flex flex-col items-end gap-3">
      <div v-for="(button, index) in isExpanded ? buttons : []" :key="button.path" class="flex items-center gap-3">
        <span class="glass rounded-xl px-3 py-1.5 text-sm font-medium shadow-[var(--shadow-soft)]">
          {{ button.label }}
        </span>
        <button
          type="button"
          :aria-label="button.label"
          :style="{ transitionDelay: `${index * 40}ms` }"
          class="flex size-12 items-center justify-center rounded-2xl text-white shadow-[var(--shadow-lift)] transition-transform duration-200 hover:scale-110 active:scale-95"
          :class="button.color"
          @click="handleNavigation(button.path)"
        >
          <component :is="button.icon" class="size-5" />
        </button>
      </div>
    </TransitionGroup>

    <!-- Déclencheur -->
    <button
      type="button"
      :aria-expanded="isExpanded"
      :aria-label="isExpanded ? 'Fermer le menu rapide' : 'Ouvrir le menu rapide'"
      class="relative flex size-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-[var(--shadow-brand)] transition-transform duration-300 hover:scale-105 active:scale-95"
      @click="isExpanded = !isExpanded"
    >
      <!-- Onde d'attention, uniquement quand le menu est fermé -->
      <span
        v-if="!isExpanded"
        class="pointer-events-none absolute inset-0 rounded-2xl bg-primary"
        style="animation: pulse-ring 2.6s ease-out infinite"
      />
      <Plus class="relative size-6 transition-transform duration-300" :class="isExpanded ? 'rotate-45' : ''" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { markRaw, ref, type Component } from 'vue'
import { useRouter } from 'vue-router'
import { Home, Plus, Settings, ShoppingCart } from 'lucide-vue-next'

defineProps<{ className?: string }>()

const router = useRouter()
const isExpanded = ref(false)

const buttons: { path: string; icon: Component; color: string; label: string }[] = [
  { path: '/', icon: markRaw(Home), color: 'bg-emerald-600', label: 'Accueil' },
  { path: '/panier', icon: markRaw(ShoppingCart), color: 'bg-amber-600', label: 'Panier' },
  { path: '/admin', icon: markRaw(Settings), color: 'bg-sky-600', label: 'Admin' },
]

const handleNavigation = (path: string) => {
  router.push(path)
  isExpanded.value = false
}
</script>

<style scoped>
.fab-item-enter-active,
.fab-item-leave-active {
  transition: opacity 0.25s ease, transform 0.3s var(--ease-spring);
}
.fab-item-enter-from,
.fab-item-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.85);
}
</style>

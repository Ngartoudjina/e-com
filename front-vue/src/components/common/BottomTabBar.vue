<template>
  <!--
    Navigation principale du mobile. Absente du desktop, où la barre
    d'en-tête suffit. L'onglet actif prend la couleur d'action.
  -->
  <nav
    class="sticky bottom-0 z-30 border-t border-rule bg-paper lg:hidden"
    aria-label="Navigation principale"
    style="padding-bottom: env(safe-area-inset-bottom)"
  >
    <ul class="grid grid-cols-4">
      <li v-for="onglet in onglets" :key="onglet.libelle">
        <RouterLink
          :to="onglet.to"
          class="flex min-h-[var(--size-touch)] flex-col items-center justify-center gap-1 py-2 transition-colors duration-[120ms]"
          :class="estActif(onglet) ? 'text-action' : 'text-ink-700'"
        >
          <span class="relative">
            <component :is="onglet.icone" class="size-5" />
            <span
              v-if="onglet.badge && onglet.badge > 0"
              data-numeric
              class="absolute -right-2 -top-1 flex size-[16px] items-center justify-center rounded-full bg-ink-900 text-[9px] text-white"
            >
              {{ onglet.badge }}
            </span>
          </span>
          <span class="t-small text-[11px]">{{ onglet.libelle }}</span>
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
import { computed, markRaw, type Component } from 'vue'
import { useRoute } from 'vue-router'
import { Heart, Home, LayoutGrid, User } from 'lucide-vue-next'

interface Onglet {
  libelle: string
  to: string
  icone: Component
  badge?: number
}

const route = useRoute()

// Le panier n'apparaît pas ici : il reste dans l'en-tête, comme sur la maquette.
const onglets = computed<Onglet[]>(() => [
  { libelle: 'Accueil', to: '/', icone: markRaw(Home) },
  { libelle: 'Boutique', to: '/catalogue', icone: markRaw(LayoutGrid) },
  { libelle: 'Favoris', to: '/favoris', icone: markRaw(Heart) },
  { libelle: 'Compte', to: '/compte', icone: markRaw(User) },
])

/** L'accueil ne doit pas rester actif sur toutes les routes. */
const estActif = (onglet: Onglet) =>
  onglet.to === '/' ? route.path === '/' : route.path.startsWith(onglet.to)
</script>

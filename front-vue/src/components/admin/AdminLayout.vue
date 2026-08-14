<template>
  <div class="min-h-screen bg-paper lg:flex">
    <!-- Barre latérale sombre -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-[280px] flex-col bg-ink-900 text-white transition-transform duration-[320ms] lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
      :class="menuOuvert ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex items-center justify-between gap-3 px-6 py-6">
        <RouterLink to="/" class="font-display text-[20px] tracking-[0.18em]">GOLDSHOP</RouterLink>
        <span class="t-label border border-white/25 px-2 py-1 text-white/70">Admin</span>
      </div>

      <div class="px-6">
        <label class="relative block">
          <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-white/40" />
          <input
            v-model="recherche"
            type="search"
            placeholder="Rechercher"
            aria-label="Rechercher dans l'administration"
            class="h-11 w-full border border-white/15 bg-white/5 pl-9 pr-3 text-[13px] text-white outline-none transition-colors placeholder:text-white/40 focus:border-white/40"
          />
        </label>
      </div>

      <nav class="mt-6 flex-1 overflow-y-auto px-3 pb-6" aria-label="Administration">
        <template v-for="groupe in navigation" :key="groupe.titre ?? 'principal'">
          <p v-if="groupe.titre" class="t-label mt-8 px-3 text-white/40">{{ groupe.titre }}</p>

          <ul class="mt-2 space-y-0.5">
            <li v-for="entree in groupe.entrees" :key="entree.libelle">
              <RouterLink
                :to="entree.to"
                class="flex h-11 items-center gap-3 px-3 text-[13px] transition-colors duration-[120ms]"
                :class="estActive(entree.to) ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white'"
                @click="menuOuvert = false"
              >
                <component :is="entree.icone" v-if="entree.icone" class="size-4 shrink-0" />
                <span class="flex-1">{{ entree.libelle }}</span>
                <span
                  v-if="entree.compteur"
                  data-numeric
                  class="flex min-w-5 items-center justify-center bg-action px-1.5 text-[11px] text-white"
                >
                  {{ entree.compteur }}
                </span>
              </RouterLink>
            </li>
          </ul>
        </template>
      </nav>

      <!-- Compte connecté -->
      <div class="border-t border-white/10 px-6 py-5">
        <RouterLink to="/admin/parametres" class="flex items-center gap-3">
          <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-[13px]">
            {{ initiale }}
          </span>
          <span class="min-w-0 flex-1">
            <span class="block truncate text-[13px]">{{ authStore.user?.name || 'Utilisateur' }}</span>
            <span class="block truncate text-[11px] text-white/50">{{ authStore.user?.email }}</span>
          </span>
        </RouterLink>
        <button type="button" class="mt-4 text-[13px] text-white/50 transition-colors hover:text-white" @click="seDeconnecter">
          Se déconnecter
        </button>
      </div>
    </aside>

    <!-- Voile mobile -->
    <div v-if="menuOuvert" class="fixed inset-0 z-40 bg-ink-900/60 lg:hidden" @click="menuOuvert = false" />

    <div class="min-w-0 flex-1">
      <!-- Barre supérieure -->
      <header class="sticky top-0 z-30 border-b border-rule bg-surface">
        <div class="flex flex-wrap items-center gap-4 px-5 py-4 lg:px-8">
          <!--
            Utilitaires posés directement : une classe de style scoped porte
            l'attribut [data-v-…] et l'emporterait sur `lg:hidden`, laissant
            le bouton visible en desktop.
          -->
          <button
            type="button"
            class="inline-flex size-11 items-center justify-center border border-rule bg-surface text-ink-900 lg:hidden"
            aria-label="Ouvrir le menu"
            @click="menuOuvert = true"
          >
            <Menu class="size-5" />
          </button>

          <div class="min-w-0 flex-1">
            <h1 class="t-h3 truncate">{{ titre }}</h1>
          </div>

          <div class="flex items-center gap-2">
            <slot name="actions" />
          </div>
        </div>
      </header>

      <main class="px-5 py-6 lg:px-8 lg:py-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, markRaw, ref, watch, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  BarChart3,
  CreditCard,
  LayoutGrid,
  Menu,
  Package,
  Search,
  Settings,
  ShoppingBag,
  Users,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

interface Entree {
  libelle: string
  to: string
  icone?: Component
  compteur?: number
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const menuOuvert = ref(false)
const recherche = ref('')

const navigation: { titre?: string; entrees: Entree[] }[] = [
  {
    entrees: [
      { libelle: 'Vue d’ensemble', to: '/admin', icone: markRaw(LayoutGrid) },
      { libelle: 'Statistiques', to: '/admin/statistiques', icone: markRaw(BarChart3) },
      { libelle: 'Commandes', to: '/admin/commandes', icone: markRaw(ShoppingBag) },
      { libelle: 'Produits', to: '/admin/produits', icone: markRaw(Package) },
      { libelle: 'Clients', to: '/admin/clients', icone: markRaw(Users) },
      { libelle: 'Affiliation', to: '/admin/affiliation', icone: markRaw(CreditCard) },
    ],
  },
  {
    titre: 'Paramètres',
    entrees: [{ libelle: 'Réglages', to: '/admin/parametres', icone: markRaw(Settings) }],
  },
]

const titres: Record<string, string> = {
  '/admin': 'Vue d’ensemble',
  '/admin/statistiques': 'Statistiques',
  '/admin/commandes': 'Commandes',
  '/admin/produits': 'Produits',
  '/admin/clients': 'Clients',
  '/admin/affiliation': 'Affiliation',
  '/admin/parametres': 'Réglages',
}

const titre = computed(() => {
  // Le détail d'une commande porte sa référence en titre.
  if (route.name === 'admin-commande-detail') return `Commande #${route.params.reference}`
  return titres[route.path] ?? 'Administration'
})

const initiale = computed(() => (authStore.user?.name?.[0] ?? 'A').toUpperCase())

/** « Vue d'ensemble » ne doit pas rester active sur les sous-pages. */
const estActive = (to: string) => (to === '/admin' ? route.path === '/admin' : route.path.startsWith(to))

const seDeconnecter = async () => {
  await authStore.deconnexion()
  router.push('/')
}

watch(() => route.path, () => (menuOuvert.value = false))
</script>


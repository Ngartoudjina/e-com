<template>
  <header class="sticky top-0 z-40 border-b border-rule bg-paper">
    <div class="container-page">
      <!--
        Trois zones de largeur égale : le logo reste centré sans recouvrir
        les outils. Le centrage absolu précédent le faisait chevaucher les
        icônes dès que la fenêtre se resserrait.
      -->
      <div class="flex h-[72px] items-center gap-3">
        <!-- Navigation principale, alignée à gauche du logo centré. -->
        <nav class="hidden flex-1 items-center gap-8 lg:flex">
          <RouterLink
            v-for="lien in navigation"
            :key="lien.libelle"
            :to="lien.to"
            class="t-body pb-1 transition-colors duration-[120ms]"
            :class="[
              lien.accent ? 'text-error' : 'text-ink-900 hover:text-ink-700',
              estActif(lien) ? 'border-b border-ink-900' : 'border-b border-transparent',
            ]"
          >
            {{ lien.libelle }}
          </RouterLink>
        </nav>

        <!-- Menu mobile -->
        <div class="flex flex-1 lg:hidden">
          <button
            type="button"
            class="inline-flex size-11 items-center justify-center text-ink-900 transition-colors duration-[120ms] hover:text-ink-700"
            :aria-expanded="menuOuvert"
            aria-label="Ouvrir le menu"
            @click="menuOuvert = !menuOuvert"
          >
            <X v-if="menuOuvert" class="size-5" />
            <Menu v-else class="size-5" />
          </button>
        </div>

        <!-- Signature -->
        <RouterLink
          to="/"
          class="shrink-0 whitespace-nowrap font-display text-[20px] font-normal tracking-[0.18em] text-ink-900 sm:text-[26px]"
        >
          GOLDSHOP
        </RouterLink>

        <!--
          Outils. Les utilitaires sont posés directement sur les éléments :
          une classe déclarée en style scoped porte l'attribut [data-v-…] et
          l'emporte alors sur `hidden`, qui n'avait plus aucun effet.
        -->
        <div class="flex flex-1 items-center justify-end gap-1">
          <RouterLink
            to="/recherche"
            class="inline-flex size-11 items-center justify-center text-ink-900 transition-colors duration-[120ms] hover:text-ink-700"
            aria-label="Rechercher"
          >
            <Search class="size-5" />
          </RouterLink>
          <RouterLink
            to="/compte"
            class="hidden size-11 items-center justify-center text-ink-900 transition-colors duration-[120ms] hover:text-ink-700 lg:inline-flex"
            aria-label="Mon compte"
          >
            <User class="size-5" />
          </RouterLink>
          <RouterLink
            to="/favoris"
            class="hidden size-11 items-center justify-center text-ink-900 transition-colors duration-[120ms] hover:text-ink-700 lg:inline-flex"
            aria-label="Mes favoris"
          >
            <Heart class="size-5" />
          </RouterLink>
          <RouterLink
            to="/panier"
            class="relative inline-flex size-11 items-center justify-center text-ink-900 transition-colors duration-[120ms] hover:text-ink-700"
            aria-label="Mon panier"
          >
            <ShoppingBag class="size-5" />
            <span
              v-if="cartStore.totalItems > 0"
              data-numeric
              class="absolute right-0 top-0 flex size-[18px] items-center justify-center rounded-full bg-ink-900 text-[10px] font-medium text-white"
            >
              {{ cartStore.totalItems }}
            </span>
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Panneau mobile -->
    <Transition name="panneau">
      <nav v-if="menuOuvert" class="border-t border-rule bg-paper lg:hidden">
        <div class="container-page py-4">
          <RouterLink
            v-for="lien in navigation"
            :key="lien.libelle"
            :to="lien.to"
            class="t-body block py-3"
            :class="lien.accent ? 'text-error' : 'text-ink-900'"
            @click="menuOuvert = false"
          >
            {{ lien.libelle }}
          </RouterLink>
        </div>
      </nav>
    </Transition>
  </header>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Heart, Menu, Search, ShoppingBag, User, X } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const cartStore = useCartStore()
const menuOuvert = ref(false)

/**
 * Les cinq entrées de la maquette. Elles visent toutes le catalogue, filtré
 * par query : le site n'a pas de rayons distincts en base.
 */
const navigation = [
  { libelle: 'Femme', to: '/catalogue?rayon=femme', cle: 'rayon', valeur: 'femme' },
  { libelle: 'Homme', to: '/catalogue?rayon=homme', cle: 'rayon', valeur: 'homme' },
  { libelle: 'Nouveautés', to: '/catalogue?tri=nouveautes', cle: 'tri', valeur: 'nouveautes' },
  { libelle: 'Collections', to: '/catalogue' },
  { libelle: 'Archives −40 %', to: '/catalogue?remise=1', cle: 'remise', valeur: '1', accent: true },
]

/**
 * Un lien n'est actif que si sa query correspond aussi.
 * Comparer le seul chemin soulignait les cinq entrées à la fois, toutes
 * pointant vers /catalogue.
 *
 * L'entrée sans query (« Collections ») ne s'allume que sur le catalogue nu :
 * sinon elle resterait active en même temps que l'entrée filtrée.
 */
const clesDeFiltre = navigation.map((lien) => lien.cle).filter(Boolean) as string[]

const estActif = (lien: (typeof navigation)[number]) => {
  if (route.path !== lien.to.split('?')[0]) return false

  if (lien.cle) {
    return route.query[lien.cle] === lien.valeur
  }

  return clesDeFiltre.every((cle) => route.query[cle] === undefined)
}

watch(() => route.fullPath, () => (menuOuvert.value = false))
</script>

<style scoped>
@reference "../../index.css";

.panneau-enter-active,
.panneau-leave-active {
  transition: opacity var(--duration-panel) var(--ease-exit);
}
.panneau-enter-from,
.panneau-leave-to {
  opacity: 0;
}
</style>

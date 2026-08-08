<template>
  <header class="sticky top-0 z-40 border-b border-rule bg-paper">
    <div class="container-page">
      <div class="relative flex h-[72px] items-center justify-between">
        <!-- Navigation principale, alignée à gauche du logo centré. -->
        <nav class="hidden items-center gap-8 lg:flex">
          <RouterLink
            v-for="lien in navigation"
            :key="lien.libelle"
            :to="lien.to"
            class="t-body pb-1 transition-colors duration-[120ms]"
            :class="[
              lien.accent ? 'text-error' : 'text-ink-900 hover:text-ink-700',
              estActif(lien.to) ? 'border-b border-ink-900' : 'border-b border-transparent',
            ]"
          >
            {{ lien.libelle }}
          </RouterLink>
        </nav>

        <!-- Menu mobile -->
        <button
          type="button"
          class="btn-icon lg:hidden"
          :aria-expanded="menuOuvert"
          aria-label="Ouvrir le menu"
          @click="menuOuvert = !menuOuvert"
        >
          <X v-if="menuOuvert" class="size-5" />
          <Menu v-else class="size-5" />
        </button>

        <!-- Signature, centrée optiquement sur la page -->
        <RouterLink
          to="/"
          class="absolute left-1/2 -translate-x-1/2 font-display text-[26px] font-normal tracking-[0.18em] text-ink-900"
        >
          GOLDSHOP
        </RouterLink>

        <!-- Outils -->
        <div class="flex items-center gap-1">
          <button type="button" class="tool" aria-label="Rechercher" @click="$emit('ouvrir-recherche')">
            <Search class="size-5" />
          </button>
          <RouterLink to="/compte" class="tool hidden sm:inline-flex" aria-label="Mon compte">
            <User class="size-5" />
          </RouterLink>
          <RouterLink to="/favoris" class="tool hidden sm:inline-flex" aria-label="Mes favoris">
            <Heart class="size-5" />
          </RouterLink>
          <RouterLink to="/panier" class="tool relative" aria-label="Mon panier">
            <ShoppingBag class="size-5" />
            <span
              v-if="cartStore.totalItems > 0"
              data-numeric
              class="absolute -right-1 -top-1 flex size-[18px] items-center justify-center rounded-full bg-ink-900 text-[10px] font-medium text-white"
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

defineEmits<{ 'ouvrir-recherche': [] }>()

const route = useRoute()
const cartStore = useCartStore()
const menuOuvert = ref(false)

const navigation = [
  { libelle: 'Femme', to: '/catalogue?rayon=femme' },
  { libelle: 'Homme', to: '/catalogue?rayon=homme' },
  { libelle: 'Nouveautés', to: '/catalogue?tri=nouveautes' },
  { libelle: 'Collections', to: '/collections' },
  { libelle: 'Archives −40 %', to: '/catalogue?remise=1', accent: true },
]

const estActif = (to: string) => route.fullPath.startsWith(to.split('?')[0]) && to !== '/collections'

watch(() => route.fullPath, () => (menuOuvert.value = false))
</script>

<style scoped>
@reference "../../index.css";

.tool {
  display: inline-flex;
  width: var(--size-control-md);
  height: var(--size-control-md);
  align-items: center;
  justify-content: center;
  color: var(--color-ink-900);
  transition: color var(--duration-press) var(--ease-exit);
}
.tool:hover {
  color: var(--color-ink-700);
}

.panneau-enter-active,
.panneau-leave-active {
  transition: opacity var(--duration-panel) var(--ease-exit);
}
.panneau-enter-from,
.panneau-leave-to {
  opacity: 0;
}
</style>

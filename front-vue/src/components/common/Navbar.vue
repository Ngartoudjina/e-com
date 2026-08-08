<template>
  <header class="fixed inset-x-0 top-0 z-50 transition-[padding] duration-500" :class="isScrolled ? 'pt-2 sm:pt-3' : 'pt-0'">
    <nav
      class="mx-auto flex h-16 items-center justify-between gap-3 transition-all duration-500 lg:h-20"
      :class="
        isScrolled
          ? 'glass w-[min(96%,80rem)] rounded-2xl px-3 shadow-[var(--shadow-lift)] sm:px-5'
          : 'w-full max-w-7xl rounded-none border-transparent bg-transparent px-5 sm:px-8 lg:px-12'
      "
    >
      <!-- Marque -->
      <RouterLink to="/" class="group flex shrink-0 items-center gap-2.5" aria-label="Accueil GOLDSHOP">
        <span class="relative flex size-10 items-center justify-center overflow-hidden rounded-xl ring-1 ring-border transition-transform duration-300 group-hover:scale-105 lg:size-11">
          <img :src="isDarkMode ? '/logo_dark.png' : '/logo_white.png'" alt="" class="size-full object-contain" />
        </span>
        <span class="hidden text-lg font-extrabold tracking-tight sm:block">
          GOLD<span class="text-gradient">SHOP</span>
        </span>
      </RouterLink>

      <!-- Navigation principale -->
      <div class="hidden items-center gap-1 md:flex">
        <div v-for="item in navItems" :key="item.id" class="relative">
          <button
            type="button"
            :aria-expanded="openSubmenu === item.id"
            @click="item.submenu && item.submenu.length ? toggleSubmenu(item.id) : handleItemClick(item.id, item.href)"
            class="relative flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium transition-colors duration-200 lg:px-4"
            :class="activeItem === item.id ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
          >
            <!-- Pastille active partagée : glisse d'un onglet à l'autre -->
            <span
              v-if="activeItem === item.id"
              class="absolute inset-0 -z-10 rounded-xl bg-accent"
            />
            <component :is="item.icon" v-if="item.icon" class="size-4" />
            <span>{{ item.label }}</span>
            <ChevronDown
              v-if="item.submenu && item.submenu.length"
              class="size-3.5 transition-transform duration-300"
              :class="openSubmenu === item.id ? 'rotate-180' : ''"
            />
          </button>

          <Transition name="pop">
            <div
              v-if="item.submenu && item.submenu.length && openSubmenu === item.id"
              class="glass absolute left-1/2 top-full mt-2 w-56 -translate-x-1/2 rounded-2xl p-1.5 shadow-[var(--shadow-float)]"
            >
              <button
                v-for="subItem in item.submenu"
                :key="subItem.id"
                type="button"
                @click="handleItemClick(subItem.id, subItem.href)"
                class="group flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
              >
                {{ subItem.label }}
                <ArrowUpRight class="size-3.5 -translate-x-1 opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100" />
              </button>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Actions -->
      <div class="hidden items-center gap-1.5 md:flex">
        <div class="relative">
          <div class="group relative flex items-center">
            <Search class="pointer-events-none absolute left-3 size-4 text-muted-foreground" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Rechercher…"
              aria-label="Rechercher un produit"
              @input="handleSearchInput"
              class="h-10 w-36 rounded-xl border border-border bg-muted/60 pl-9 pr-3 text-sm outline-none transition-all duration-300 placeholder:text-muted-foreground focus:w-56 focus:border-primary/40 focus:bg-card lg:w-44"
            />
          </div>

          <Transition name="pop">
            <div
              v-if="searchResults.length > 0"
              class="glass absolute right-0 top-full mt-2 max-h-80 w-80 overflow-y-auto rounded-2xl p-1.5 shadow-[var(--shadow-float)]"
            >
              <button
                v-for="product in searchResults"
                :key="product.id"
                type="button"
                @click="handleProductClick(product.id)"
                class="flex w-full items-center gap-3 rounded-xl p-2 text-left transition-colors hover:bg-accent"
              >
                <img :src="product.mediaUrl" :alt="product.name" class="size-11 shrink-0 rounded-lg object-cover" />
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-semibold">{{ product.name }}</span>
                  <span class="block truncate text-xs text-muted-foreground">{{ product.description }}</span>
                </span>
                <span class="shrink-0 text-sm font-bold text-primary">{{ formatPrice(product.price) }}</span>
              </button>
            </div>
          </Transition>
        </div>

        <button type="button" class="icon-btn" aria-label="Notifications">
          <Bell class="size-[18px]" />
          <span class="absolute right-1.5 top-1.5 size-2 rounded-full bg-destructive ring-2 ring-background" />
        </button>

        <button type="button" class="icon-btn" aria-label="Mon compte" @click="router.push('/signin')">
          <User class="size-[18px]" />
        </button>

        <button type="button" class="icon-btn" aria-label="Panier" @click="router.push('/panier')">
          <ShoppingBag class="size-[18px]" />
          <span
            v-if="cartStore.totalItems > 0"
            class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[11px] font-bold text-primary-foreground ring-2 ring-background"
          >
            {{ cartStore.totalItems }}
          </span>
        </button>

        <button
          type="button"
          class="ml-1.5 h-10 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-[var(--shadow-brand)] transition-transform duration-200 hover:-translate-y-0.5 active:translate-y-0"
          @click="router.push('/signin')"
        >
          Connexion
        </button>
      </div>

      <!-- Actions mobiles -->
      <div class="flex items-center gap-1 md:hidden">
        <button type="button" class="icon-btn" aria-label="Panier" @click="router.push('/panier')">
          <ShoppingBag class="size-[18px]" />
          <span
            v-if="cartStore.totalItems > 0"
            class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[11px] font-bold text-primary-foreground ring-2 ring-background"
          >
            {{ cartStore.totalItems }}
          </span>
        </button>
        <button
          ref="menuButtonRef"
          type="button"
          class="icon-btn menu-button"
          :aria-expanded="isMobileMenuOpen"
          aria-label="Menu"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <X v-if="isMobileMenuOpen" class="size-5" />
          <Menu v-else class="size-5" />
        </button>
      </div>
    </nav>

    <!-- Panneau mobile -->
    <Transition name="sheet">
      <div
        v-if="isMobileMenuOpen"
        class="mobile-menu glass absolute inset-x-2 top-[4.5rem] max-h-[calc(100dvh-6rem)] overflow-y-auto rounded-3xl p-4 shadow-[var(--shadow-float)] md:hidden"
      >
        <div class="relative mb-4">
          <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Rechercher un produit…"
            @input="handleSearchInput"
            class="h-12 w-full rounded-2xl border border-border bg-muted/60 pl-10 pr-4 text-sm outline-none focus:border-primary/40"
          />
        </div>

        <div v-if="searchResults.length" class="mb-4 space-y-1">
          <button
            v-for="product in searchResults.slice(0, 4)"
            :key="product.id"
            type="button"
            @click="handleProductClick(product.id)"
            class="flex w-full items-center gap-3 rounded-xl p-2 text-left transition-colors hover:bg-accent"
          >
            <img :src="product.mediaUrl" :alt="product.name" class="size-10 rounded-lg object-cover" />
            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ product.name }}</span>
            <span class="text-sm font-bold text-primary">{{ formatPrice(product.price) }}</span>
          </button>
        </div>

        <nav class="space-y-1">
          <div v-for="item in navItems" :key="item.id">
            <button
              type="button"
              @click="item.submenu && item.submenu.length ? toggleSubmenu(item.id) : handleItemClick(item.id, item.href)"
              class="flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left transition-colors"
              :class="activeItem === item.id ? 'bg-accent text-accent-foreground' : 'hover:bg-muted'"
            >
              <span class="flex items-center gap-3 text-base font-semibold">
                <component :is="item.icon" v-if="item.icon" class="size-[18px]" />
                {{ item.label }}
              </span>
              <ChevronDown
                v-if="item.submenu && item.submenu.length"
                class="size-4 transition-transform duration-300"
                :class="openSubmenu === item.id ? 'rotate-180' : ''"
              />
            </button>

            <Transition name="pop">
              <div v-if="item.submenu && item.submenu.length && openSubmenu === item.id" class="ml-4 mt-1 space-y-0.5 border-l border-border pl-3">
                <button
                  v-for="subItem in item.submenu"
                  :key="subItem.id"
                  type="button"
                  @click="handleItemClick(subItem.id, subItem.href)"
                  class="w-full rounded-lg px-3 py-2 text-left text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                  {{ subItem.label }}
                </button>
              </div>
            </Transition>
          </div>
        </nav>

        <button
          type="button"
          class="mt-5 h-12 w-full rounded-2xl bg-primary text-sm font-semibold text-primary-foreground shadow-[var(--shadow-brand)]"
          @click="handleItemClick('signin', '/signin')"
        >
          Connexion
        </button>
      </div>
    </Transition>
  </header>
</template>

<script setup lang="ts">
import { markRaw, onBeforeUnmount, onMounted, ref, watch, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowUpRight, Bell, ChevronDown, Home, Menu, Search, ShoppingBag, User, X } from 'lucide-vue-next'
import { api } from '@/lib/api'
import { useCartStore } from '@/stores/cart'
import type { Product } from '@/types'

interface NavItem {
  id: string
  label: string
  icon?: Component
  href?: string
  submenu?: NavItem[]
}

interface SearchProduct {
  id: string
  name: string
  price: number
  mediaUrl: string
  description?: string
}

const router = useRouter()
const route = useRoute()
const cartStore = useCartStore()

const activeItem = ref<string>('home')
const isMobileMenuOpen = ref<boolean>(false)
const isScrolled = ref<boolean>(false)
const openSubmenu = ref<string | null>(null)
const searchQuery = ref<string>('')
const searchResults = ref<SearchProduct[]>([])
const isDarkMode = ref<boolean>(false)
const menuButtonRef = ref<HTMLElement | null>(null)

const navItems = ref<NavItem[]>([
  { id: 'home', label: 'Accueil', icon: markRaw(Home), href: '/' },
  { id: 'products', label: 'Produits', href: '/product', submenu: [] },
  { id: 'blog', label: 'Blog', href: '/blog' },
  { id: 'about', label: 'À propos', href: '/propos' },
])

const formatPrice = (price: number) => `${new Intl.NumberFormat('fr-FR').format(price)} F`

const fetchCategories = async () => {
  try {
    const response = await api.get('/api/products', { params: { all: 'true' } })
    const fetchedProducts: Product[] = response.data.products || []
    const uniqueCategories = Array.from(new Set(fetchedProducts.map((product) => product.category || 'Autres')))
    const productsItem = navItems.value.find((item) => item.id === 'products')
    if (productsItem) {
      productsItem.submenu = uniqueCategories.map((category) => ({
        id: category.toLowerCase().replace(/\s+/g, '-'),
        label: category,
        href: '/product',
      }))
    }
  } catch (error) {
    console.error('Erreur lors de la récupération des catégories:', error)
  }
}

let searchTimer: ReturnType<typeof setTimeout> | undefined
const handleSearchInput = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    if (searchQuery.value.trim()) {
      try {
        const response = await api.get(`/api/products?category=${encodeURIComponent(searchQuery.value)}`)
        searchResults.value = response.data.products || []
      } catch (error) {
        console.error('Erreur lors de la récupération des résultats de recherche:', error)
        searchResults.value = []
      }
    } else {
      searchResults.value = []
    }
  }, 300)
}

const handleItemClick = (itemId: string, href?: string) => {
  activeItem.value = itemId
  isMobileMenuOpen.value = false
  openSubmenu.value = null
  if (href) {
    router.push(href)
  }
}

const toggleSubmenu = (itemId: string) => {
  openSubmenu.value = openSubmenu.value === itemId ? null : itemId
}

const handleProductClick = (id: string) => {
  router.push(`/single/${id}`)
  searchQuery.value = ''
  searchResults.value = []
  isMobileMenuOpen.value = false
}

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as Node
  const menuButton = menuButtonRef.value
  const isClickOnMenuButton = menuButton && menuButton.contains(target)
  const isClickInsideMenu = (event.target as Element).closest('.mobile-menu')
  if (isMobileMenuOpen.value && !isClickInsideMenu && !isClickOnMenuButton) {
    isMobileMenuOpen.value = false
    openSubmenu.value = null
  }
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 16
}

const handleDarkModeChange = (e: MediaQueryListEvent) => {
  isDarkMode.value = e.matches
}

// Le menu mobile est plein écran : on bloque le défilement du corps derrière lui.
watch(isMobileMenuOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

// Synchronise l'onglet actif avec l'URL (retour arrière, lien direct, etc.).
watch(
  () => route.path,
  (path) => {
    const match = navItems.value.find((item) => item.href === path)
    if (match) activeItem.value = match.id
  },
  { immediate: true }
)

let mediaQuery: MediaQueryList | null = null

onMounted(() => {
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  isDarkMode.value = mediaQuery.matches
  mediaQuery.addEventListener('change', handleDarkModeChange)
  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll()
  fetchCategories()
})

const clickTimer = setTimeout(() => document.addEventListener('click', handleClickOutside), 100)

onBeforeUnmount(() => {
  clearTimeout(clickTimer)
  clearTimeout(searchTimer)
  document.body.style.overflow = ''
  window.removeEventListener('scroll', handleScroll)
  mediaQuery?.removeEventListener('change', handleDarkModeChange)
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Tailwind v4 : chaque bloc <style> est compilé isolément, @reference lui donne accès au thème. */
@reference "../../index.css";

/* Écrit en CSS simple sur les jetons du système : ce composant sera refait
   à partir de la maquette, ceci le garde compilable d'ici là. */
.icon-btn {
  position: relative;
  display: inline-flex;
  width: var(--size-control-md);
  height: var(--size-control-md);
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-2);
  color: var(--color-ink-700);
  transition: background-color var(--duration-press) var(--ease-exit),
    color var(--duration-press) var(--ease-exit);
}
.icon-btn:hover {
  background: var(--color-rule-soft);
  color: var(--color-ink-900);
}

.pop-enter-active,
.pop-leave-active {
  transition: opacity var(--duration-control) var(--ease-exit),
    transform var(--duration-control) var(--ease-exit);
}
.pop-enter-from,
.pop-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.97);
}
.pop-enter-from,
.pop-leave-to {
  transform-origin: top;
}

.sheet-enter-active,
.sheet-leave-active {
  transition: opacity 0.25s ease, transform 0.3s var(--ease-out-expo);
}
.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}
</style>

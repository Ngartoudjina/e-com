<template>
  <section class="section bg-background">
    <div class="container-x">
      <!-- En-tête -->
      <div class="mx-auto max-w-2xl text-center">
        <span class="eyebrow">Notre catalogue</span>
        <h2 class="display-2 mt-5">
          La <span class="text-gradient">collection</span> du moment
        </h2>
        <p class="mt-5 text-lg text-muted-foreground">
          Les pièces les plus demandées, par catégorie.
        </p>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="mt-14 space-y-12">
        <div v-for="row in 2" :key="row">
          <div class="skeleton h-7 w-44" />
          <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <div v-for="i in 5" :key="i" class="surface overflow-hidden">
              <div class="skeleton aspect-[4/5] rounded-none" />
              <div class="space-y-2 p-4">
                <div class="skeleton h-4 w-3/4" />
                <div class="skeleton h-3 w-1/2" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Erreur -->
      <div v-else-if="error" class="mx-auto mt-14 max-w-md">
        <div class="surface flex flex-col items-center p-10 text-center">
          <span class="flex size-12 items-center justify-center rounded-2xl bg-destructive/10 text-destructive">
            <TriangleAlert class="size-6" />
          </span>
          <p class="mt-4 font-semibold">{{ error }}</p>
          <button
            type="button"
            @click="fetchProducts"
            class="mt-5 inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground"
          >
            <RefreshCw class="size-4" />
            Réessayer
          </button>
        </div>
      </div>

      <!-- Catalogue -->
      <div v-else class="mt-14 space-y-16">
        <section v-for="(categoryProducts, category) in groupedProducts" :key="category">
          <div class="mb-6 flex items-end justify-between gap-4">
            <div>
              <h3 class="text-2xl font-bold tracking-tight">{{ category }}</h3>
              <p class="mt-1 text-sm text-muted-foreground">
                {{ categoryProducts.length }} article{{ categoryProducts.length > 1 ? 's' : '' }}
              </p>
            </div>
            <RouterLink
              to="/product"
              class="group hidden items-center gap-1.5 text-sm font-semibold text-primary sm:inline-flex"
            >
              Tout voir
              <ArrowRight class="size-4 transition-transform group-hover:translate-x-1" />
            </RouterLink>
          </div>

          <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <article
              v-for="(product, index) in categoryProducts"
              :key="product.id"
              v-reveal="Math.min(index, 6) * 60"
              class="group surface surface-hover relative flex flex-col overflow-hidden"
            >
              <!-- Visuel -->
              <div class="relative aspect-[4/5] overflow-hidden bg-muted">
                <video
                  v-if="isVideo(product.mediaUrl)"
                  :src="product.mediaUrl"
                  class="size-full object-cover transition-transform duration-700 group-hover:scale-105"
                  muted
                  playsinline
                />
                <img
                  v-else
                  :src="product.mediaUrl"
                  :alt="product.name"
                  loading="lazy"
                  class="size-full object-cover transition-transform duration-700 group-hover:scale-105"
                />

                <!-- Voile + actions au survol -->
                <div
                  class="absolute inset-0 flex items-end justify-center bg-gradient-to-t from-black/70 via-black/10 to-transparent p-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-focus-within:opacity-100"
                >
                  <div class="flex w-full translate-y-3 gap-2 transition-transform duration-300 group-hover:translate-y-0 group-focus-within:translate-y-0">
                    <button
                      type="button"
                      aria-label="Aperçu"
                      class="quick-action"
                      @click.stop="handleImageView(product)"
                    >
                      <Eye class="size-4" />
                    </button>
                    <button
                      type="button"
                      class="flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-white text-sm font-semibold text-neutral-900 transition-colors hover:bg-white/90"
                      @click.stop="handleProductPageOpen(product)"
                    >
                      <ShoppingBag class="size-4" />
                      Ajouter
                    </button>
                  </div>
                </div>

                <!-- Badge catégorie / nouveauté -->
                <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-neutral-900 backdrop-blur">
                  Nouveau
                </span>

                <!-- Favori -->
                <button
                  type="button"
                  :aria-label="likedProducts.has(product.id) ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                  :aria-pressed="likedProducts.has(product.id)"
                  class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-full bg-white/90 text-neutral-700 backdrop-blur transition-all duration-200 hover:scale-110 active:scale-95"
                  @click.stop="toggleLike(product.id)"
                >
                  <Heart class="size-4 transition-colors" :class="likedProducts.has(product.id) ? 'fill-rose-500 text-rose-500' : ''" />
                </button>
              </div>

              <!-- Informations -->
              <div class="flex flex-1 flex-col p-4">
                <h4 class="truncate text-sm font-semibold" :title="product.name">
                  {{ product.name }}
                </h4>
                <p class="mt-1 line-clamp-2 min-h-[2.25rem] text-xs leading-relaxed text-muted-foreground">
                  {{ product.description || 'Aucune description disponible' }}
                </p>

                <div class="mt-3 flex items-center gap-1">
                  <Star
                    v-for="i in 5"
                    :key="i"
                    class="size-3.5"
                    :class="i <= Math.round(product.rating || 0) ? 'fill-amber-400 text-amber-400' : 'text-border'"
                  />
                  <span class="ml-1 text-xs text-muted-foreground">{{ (product.rating || 0).toFixed(1) }}</span>
                </div>

                <div class="mt-3 flex items-end justify-between gap-2 border-t border-border pt-3">
                  <span class="text-base font-extrabold tracking-tight">{{ formatPrice(product.price) }}</span>
                  <button
                    type="button"
                    aria-label="Acheter maintenant"
                    class="inline-flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground transition-transform duration-200 hover:scale-105 active:scale-95"
                    @click="handleBuyNow(withDetails(product))"
                  >
                    <ShoppingCart class="size-4" />
                  </button>
                </div>
              </div>
            </article>
          </div>
        </section>
      </div>
    </div>

    <!-- Aperçu média -->
    <Transition name="modal">
      <div
        v-if="selectedImage && selectedProduct && !showProductPage"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click="closeModal"
      >
        <div class="relative flex max-h-[92dvh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-card shadow-[var(--shadow-float)]" @click.stop>
          <header class="flex items-center justify-between gap-4 border-b border-border p-5">
            <div class="min-w-0">
              <h3 class="truncate text-lg font-bold">{{ selectedProduct.name }}</h3>
              <p class="text-sm text-muted-foreground">{{ selectedProduct.category }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
              <button type="button" class="modal-btn" aria-label="Dézoomer" @click="handleZoomOut"><ZoomOut class="size-4" /></button>
              <button type="button" class="modal-btn" aria-label="Zoomer" @click="handleZoomIn"><ZoomIn class="size-4" /></button>
              <button type="button" class="modal-btn" aria-label="Fermer" @click="closeModal"><X class="size-4" /></button>
            </div>
          </header>

          <div class="relative flex-1 overflow-hidden bg-muted" style="min-height: 45vh">
            <video
              v-if="isVideo(selectedImage)"
              :src="selectedImage"
              controls
              class="size-full object-contain transition-transform duration-300"
              :style="{ transform: `scale(${imageZoom})` }"
            />
            <img
              v-else
              :src="selectedImage"
              :alt="selectedProduct.name"
              class="size-full object-contain transition-transform duration-300"
              :style="{ transform: `scale(${imageZoom})` }"
            />
          </div>

          <footer class="space-y-4 border-t border-border p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-3">
                <span class="text-2xl font-extrabold tracking-tight">{{ formatPrice(selectedProduct.price) }}</span>
                <span class="flex items-center gap-0.5">
                  <Star
                    v-for="i in 5"
                    :key="i"
                    class="size-3.5"
                    :class="i <= Math.round(selectedProduct.rating || 0) ? 'fill-amber-400 text-amber-400' : 'text-border'"
                  />
                </span>
              </div>
              <div class="flex flex-wrap gap-2">
                <button type="button" class="modal-action border border-border hover:bg-accent" @click="toggleLike(selectedProduct.id)">
                  <Heart class="size-4" :class="likedProducts.has(selectedProduct.id) ? 'fill-rose-500 text-rose-500' : ''" />
                  J'aime
                </button>
                <button type="button" class="modal-action border border-border hover:bg-accent" @click="handleProductPageOpen(selectedProduct)">
                  <ShoppingBag class="size-4" />
                  Au panier
                </button>
                <button type="button" class="modal-action bg-primary text-primary-foreground hover:opacity-90" @click="handleBuyNow(withDetails(selectedProduct))">
                  Acheter maintenant
                </button>
              </div>
            </div>
            <p class="text-sm leading-relaxed text-muted-foreground">{{ selectedProduct.description }}</p>
          </footer>
        </div>
      </div>
    </Transition>

    <!-- Fiche produit -->
    <Transition name="modal">
      <div
        v-if="showProductPage && selectedProduct"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click="showProductPage = false"
      >
        <div class="relative max-h-[92dvh] w-full max-w-7xl overflow-hidden rounded-3xl bg-card shadow-[var(--shadow-float)]" @click.stop>
          <ProductPage
            :product="selectedProduct"
            @close="handleProductPageClose"
            @add-to-cart="handleAddToCart"
            @buy-now="handleBuyNow"
          />
        </div>
      </div>
    </Transition>
  </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watchEffect } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowRight,
  Eye,
  Heart,
  RefreshCw,
  ShoppingBag,
  ShoppingCart,
  Star,
  TriangleAlert,
  X,
  ZoomIn,
  ZoomOut,
} from 'lucide-vue-next'
import { api } from '@/lib/api'
import { useCartStore } from '@/stores/cart'
import ProductPage from './ProductPage.vue'
import type { Product, ProductWithDetails } from '@/types'

const cartStore = useCartStore()
const router = useRouter()

const loading = ref(true)
const error = ref<string | null>(null)
const likedProducts = ref<Set<string>>(new Set())
const selectedImage = ref<string | null>(null)
const selectedProduct = ref<Product | null>(null)
const imageZoom = ref(1)
const groupedProducts = ref<Record<string, Product[]>>({})
const showProductPage = ref(false)

const isVideo = (url: string) => /\.(mp4|mov|mpeg)$/.test(url)
const formatPrice = (price: number) => `${new Intl.NumberFormat('fr-FR').format(price)} F`

const fetchProducts = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/api/products', { params: { all: true } })
    const fetchedProducts: Product[] = (response.data.products || []).map((p: any) => ({
      ...p,
      mediaUrl: p.mediaUrl || p.imageUrl,
    }))
    const grouped: Record<string, Product[]> = {}
    fetchedProducts.forEach((product) => {
      const category = product.category || 'Autres'
      if (!grouped[category]) grouped[category] = []
      grouped[category].push(product)
    })
    groupedProducts.value = grouped
  } catch (err) {
    console.error('Erreur:', err)
    error.value = 'Erreur lors du chargement des produits'
  } finally {
    loading.value = false
  }
}

onMounted(fetchProducts)

const toggleLike = (productId: string) => {
  const newSet = new Set(likedProducts.value)
  if (newSet.has(productId)) newSet.delete(productId)
  else newSet.add(productId)
  likedProducts.value = newSet
}

const handleImageView = (product: Product) => {
  selectedProduct.value = product
  selectedImage.value = product.mediaUrl
  imageZoom.value = 1
}

const handleProductPageOpen = (product: Product) => {
  selectedProduct.value = product
  showProductPage.value = true
  selectedImage.value = null
}

const handleProductPageClose = () => {
  showProductPage.value = false
  selectedProduct.value = null
}

const handleAddToCart = (productWithDetails: ProductWithDetails) => {
  cartStore.addToCart(productWithDetails)
  showProductPage.value = false
}

const handleBuyNow = (productWithDetails: ProductWithDetails) => {
  cartStore.addToCart(productWithDetails)
  router.push('/panier')
}

const handleZoomIn = () => (imageZoom.value = Math.min(imageZoom.value + 0.2, 2))
const handleZoomOut = () => (imageZoom.value = Math.max(imageZoom.value - 0.2, 0.5))

const closeModal = () => {
  selectedImage.value = null
  selectedProduct.value = null
}

const withDetails = (product: Product): ProductWithDetails => ({
  ...product,
  quantity: 1,
  selectedColor: 'default',
  selectedSize: 'M',
})

// Un overlay ouvert ne doit pas laisser la page défiler derrière lui.
watchEffect(() => {
  const isOverlayOpen = Boolean(showProductPage.value || selectedImage.value)
  document.body.style.overflow = isOverlayOpen ? 'hidden' : ''
})

const handleEscape = (event: KeyboardEvent) => {
  if (event.key !== 'Escape') return
  if (showProductPage.value) handleProductPageClose()
  else if (selectedImage.value) closeModal()
}

onMounted(() => document.addEventListener('keydown', handleEscape))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})
</script>

<style scoped>
@reference "../../index.css";

/* CSS simple sur les jetons du système, en attendant la refonte de cet écran
   d'après « 02 Catalogue » et « 03 Fiche produit ». */
.quick-action {
  display: flex;
  width: var(--size-control-md);
  height: var(--size-control-md);
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-2);
  background: var(--color-surface);
  color: var(--color-ink-900);
  transition: background-color var(--duration-press) var(--ease-exit);
}

.modal-btn {
  display: inline-flex;
  width: var(--size-control-sm);
  height: var(--size-control-sm);
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-2);
  color: var(--color-ink-500);
  transition: background-color var(--duration-press) var(--ease-exit),
    color var(--duration-press) var(--ease-exit);
}
.modal-btn:hover {
  background: var(--color-rule-soft);
  color: var(--color-ink-900);
}

.modal-action {
  display: inline-flex;
  height: var(--size-control-md);
  align-items: center;
  gap: var(--spacing-2);
  padding-inline: var(--spacing-4);
  border-radius: var(--radius-2);
  font-size: 15px;
  font-weight: 500;
  transition: background-color var(--duration-press) var(--ease-exit);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-active > div,
.modal-leave-active > div {
  transition: transform 0.3s var(--ease-out-expo), opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from > div,
.modal-leave-to > div {
  opacity: 0;
  transform: scale(0.96) translateY(12px);
}
</style>

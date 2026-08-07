<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-indigo-50">
    <aside class="fixed left-0 top-0 w-64 bg-white/80 backdrop-blur-sm shadow-lg h-screen p-4 z-40 hidden lg:block">
      <div class="mb-6">
        <div class="flex items-center space-x-2 hover:scale-105 transition-transform">
          <img src="/logo.jpg" class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center" />
          <span class="text-base sm:text-lg lg:text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
            GOLDSHOP
          </span>
        </div>
        <p class="text-gray-600 text-xs mt-1">Dashboard Produits</p>
      </div>
      <nav class="space-y-2">
        <button
          v-for="section in sections"
          :key="section.id"
          type="button"
          @click="activeSection = section.id"
          class="w-full p-3 rounded-lg cursor-pointer transition-all duration-200 hover:scale-102 hover:translate-x-1 active:scale-98"
          :class="activeSection === section.id ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'"
        >
          <div class="flex items-center space-x-2">
            <span class="text-xl">{{ section.icon }}</span>
            <span class="font-medium text-sm">{{ section.label }}</span>
          </div>
        </button>
      </nav>
    </aside>

    <main class="lg:ml-64 p-4">
      <div class="max-w-7xl mx-auto">
        <div class="mb-6 text-center">
          <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
            Collection Premium
          </h1>
          <p class="text-sm sm:text-base text-gray-600">Découvrez nos produits exclusifs</p>
        </div>

        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-md p-4 border border-white/20">
          <template v-if="activeSection !== 'faq'">
            <div class="mb-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
              <div class="w-full sm:w-auto flex items-center space-x-2">
                <div class="relative w-full">
                  <Search class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <input
                    v-model="searchTerm"
                    type="text"
                    placeholder="Rechercher..."
                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
                <select v-model="sortBy" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                  <option value="name">Nom</option>
                  <option value="price">Prix</option>
                  <option value="rating">Note</option>
                </select>
              </div>
              <div class="flex items-center space-x-2">
                <Button :variant="viewMode === 'grid' ? 'default' : 'outline'" size="sm" class="p-2" @click="viewMode = 'grid'">
                  <Grid class="w-4 h-4" />
                </Button>
                <Button :variant="viewMode === 'list' ? 'default' : 'outline'" size="sm" class="p-2" @click="viewMode = 'list'">
                  <List class="w-4 h-4" />
                </Button>
                <div ref="cartIconRef" class="relative">
                  <Button variant="outline" size="sm" class="p-2" @click="$router.push('/panier')">
                    <ShoppingCart class="w-4 h-4" />
                  </Button>
                  <Badge v-if="totalCartItems > 0" class="absolute -top-4 -right-4 bg-indigo-600 text-white text-xs">
                    {{ totalCartItems }}
                  </Badge>
                </div>
              </div>
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 border-b-2 border-indigo-500 pb-2 mb-6">
              {{ activeSectionLabel }}
            </h2>

            <div class="space-y-6">
              <section v-for="(categoryProducts, category) in groupedProducts" :key="category" class="space-y-4">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-800">{{ category }}</h3>
                <div class="grid gap-4" :class="viewMode === 'grid' ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' : 'grid-cols-1'">
                  <div
                    v-for="product in categoryProducts"
                    :key="product.id"
                    class="relative group cursor-pointer hover:-translate-y-1 hover:scale-102 transition-all duration-200"
                    @mouseenter="hoveredProduct = product.id"
                    @mouseleave="hoveredProduct = null"
                  >
                    <Card class="overflow-hidden bg-white/90 border-gray-200 shadow-sm hover:shadow-md transition-all duration-200">
                      <CardContent class="p-0">
                        <div class="relative h-40 sm:h-48 bg-gray-100 overflow-hidden">
                          <img :src="product.mediaUrl" :alt="product.name" class="w-full h-full object-cover hover:scale-105 transition-transform duration-200" />

                          <div v-if="hoveredProduct === product.id" class="absolute inset-0 bg-black/50 flex items-center justify-center backdrop-blur-sm">
                            <div class="flex space-x-1">
                              <Button size="sm" class="bg-indigo-600/80 hover:bg-indigo-700/80 p-2" @click="handleQuickAdd(product)">
                                <ShoppingCart class="w-3 h-3 text-white" />
                              </Button>
                              <Button size="sm" class="bg-purple-600/80 hover:bg-purple-700/80 p-2" @click="handleImageView(product)">
                                <Eye class="w-3 h-3 text-white" />
                              </Button>
                            </div>
                          </div>

                          <div class="absolute top-1 left-1">
                            <Badge class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs">
                              Nouveau
                            </Badge>
                          </div>
                          <div class="absolute top-1 right-1">
                            <div class="bg-gray-800/80 text-white px-2 py-1 rounded-full text-xs">
                              {{ product.price }}fcfa
                            </div>
                          </div>
                        </div>
                        <div class="p-3 space-y-2">
                          <h3 class="text-sm font-semibold text-gray-800 line-clamp-1">
                            {{ product.name }}
                          </h3>
                          <p class="text-xs text-gray-600 line-clamp-2">{{ product.description || 'Sans desc.' }}</p>
                          <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1">
                              <Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= Math.floor(product.rating || 0) ? 'text-yellow-500' : 'text-gray-300'" />
                              <span class="text-xs text-gray-500">({{ product.rating ?? 0 }})</span>
                            </div>
                            <Button size="sm" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-xs p-1" @click="handleProductPageOpen(product)">
                              <ShoppingCart class="w-3 h-3" />
                            </Button>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </div>
              </section>

              <div v-if="Object.keys(groupedProducts).length === 0" class="text-center py-6">
                <div class="text-gray-400 text-3xl mb-2">🔍</div>
                <p class="text-gray-600 text-base">
                  {{ searchTerm ? 'Aucun produit trouvé' : 'Aucun produit' }}
                </p>
              </div>
            </div>
          </template>

          <div v-else class="space-y-4">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">FAQ</h2>
            <div class="space-y-2">
              <div v-for="(faq, index) in faqs" :key="index" class="border border-gray-200 rounded-lg">
                <button
                  type="button"
                  class="w-full p-4 text-left bg-white hover:bg-gray-50 transition-colors flex items-center justify-between text-sm"
                  @click="expandedFaq = expandedFaq === index ? null : index"
                >
                  <h3 class="font-semibold text-gray-900">{{ faq.question }}</h3>
                  <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="expandedFaq === index ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <div v-if="expandedFaq === index" class="p-4 pt-0 bg-gray-50 text-sm">
                  <p class="text-gray-700">{{ faq.answer }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <div class="lg:hidden fixed bottom-6 left-8 z-50">
      <button
        type="button"
        @click="showMobileMenu = !showMobileMenu"
        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-3 rounded-full shadow-md hover:scale-110 active:scale-90 transition-transform"
      >
        <X v-if="showMobileMenu" class="w-5 h-5" />
        <Menu v-else class="w-5 h-5" />
      </button>
      <Badge v-if="totalCartItems > 0" class="absolute -top-1 -right-1 bg-indigo-600 text-white text-xs">
        {{ totalCartItems }}
      </Badge>
    </div>

    <div v-if="showMobileMenu" class="lg:hidden fixed inset-0 bg-white/95 backdrop-blur-sm p-4 z-40">
      <div class="mt-16 space-y-3">
        <button
          v-for="section in sections"
          :key="section.id"
          type="button"
          @click="selectMobileSection(section.id)"
          class="w-full p-3 rounded-lg cursor-pointer transition-all duration-200 hover:scale-102 hover:translate-x-1 active:scale-98"
          :class="activeSection === section.id ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'"
        >
          <div class="flex items-center space-x-2">
            <span class="text-2xl">{{ section.icon }}</span>
            <span class="font-medium text-base">{{ section.label }}</span>
          </div>
        </button>
      </div>
    </div>

    <div
      v-if="selectedImage && selectedProduct && !showProductPage"
      class="fixed inset-0 bg-black/90 flex items-center justify-center z-50 p-2"
      @click="closeImageView"
    >
      <div class="relative max-w-xs sm:max-w-md w-full max-h-[80vh] bg-white rounded-xl shadow-lg overflow-hidden" @click.stop>
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-3 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white">{{ selectedProduct.name }}</h3>
            <p class="text-indigo-100 text-xs">{{ selectedProduct.category }}</p>
          </div>
          <div class="flex items-center space-x-1">
            <button type="button" @click="handleZoomOut" class="p-1 bg-white/20 rounded-full hover:bg-white/30">
              <ZoomOut class="w-3 h-3 text-white" />
            </button>
            <button type="button" @click="handleZoomIn" class="p-1 bg-white/20 rounded-full hover:bg-white/30">
              <ZoomIn class="w-3 h-3 text-white" />
            </button>
            <button type="button" @click="closeImageView" class="p-1 bg-white/20 rounded-full hover:bg-white/30">
              <X class="w-3 h-3 text-white" />
            </button>
          </div>
        </div>
        <div class="relative overflow-hidden bg-gray-100" style="height: 50vh">
          <img
            :src="selectedImage"
            :alt="selectedProduct.name"
            class="w-full h-full object-contain transition-transform duration-200"
            :style="{ transform: `scale(${imageZoom})` }"
          />
        </div>
        <div class="p-3">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <div class="flex items-center space-x-1">
                <Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= Math.floor(selectedProduct.rating || 0) ? 'text-yellow-500' : 'text-gray-300'" />
                <span class="text-gray-600 text-xs">({{ selectedProduct.rating ?? 0 }})</span>
              </div>
              <div class="text-xl font-bold text-indigo-600">
                {{ selectedProduct.price }}fcfa
              </div>
            </div>
            <button
              type="button"
              @click="handleQuickAdd(selectedProduct)"
              class="flex items-center space-x-1 px-2 py-1 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white text-xs hover:scale-105 active:scale-95 transition-transform"
            >
              <ShoppingCart class="w-3 h-3" />
              <span>Ajouter</span>
            </button>
          </div>
          <p class="text-gray-700 text-xs sm:text-sm leading-relaxed">
            {{ selectedProduct.description }}
          </p>
        </div>
      </div>
    </div>

    <div
      v-if="showProductPage && selectedProduct"
      class="fixed inset-0 bg-black/90 flex items-center justify-center z-50 p-2"
      @click="showProductPage = false"
    >
      <div class="relative max-w-xs sm:max-w-2xl w-full max-h-[90vh] bg-white rounded-xl shadow-lg overflow-hidden" @click.stop>
        <ProductPage
          :product="selectedProduct"
          @close="handleProductPageClose"
          @add-to-cart="handleAddToCart"
          @buy-now="handleBuyNow"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Card, CardContent, Button, Badge } from '@/components/ui/index'
import { Star, ShoppingCart, Eye, Menu, X, Search, Grid, List, ZoomIn, ZoomOut } from 'lucide-vue-next'
import { api } from '@/lib/api'
import { useCartStore } from '@/stores/cart'
import ProductPage from '@/components/home/ProductPage.vue'
import type { Product, ProductWithDetails } from '@/types'

interface Section {
  id: string
  label: string
  icon: string
  categories: string[]
}

const cartStore = useCartStore()
const router = useRouter()

const activeSection = ref<string | null>(null)
const groupedProducts = ref<{ [key: string]: Product[] }>({})
const allProducts = ref<Product[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const hoveredProduct = ref<string | null>(null)
const searchTerm = ref('')
const sortBy = ref('name')
const viewMode = ref('grid')
const showMobileMenu = ref(false)
const expandedFaq = ref<number | null>(null)
const selectedImage = ref<string | null>(null)
const selectedProduct = ref<Product | null>(null)
const imageZoom = ref(1)
const showProductPage = ref(false)
const cartAnimation = ref<{ id: string; imageUrl: string; x: number; y: number } | null>(null)

const sections = ref<Section[]>([
  { id: 'all', label: 'Tous les produits', icon: '🌐', categories: [] },
  { id: 'faq', label: 'FAQ', icon: '❓', categories: [] },
])

const faqs = [
  { question: 'Délais de livraison ?', answer: '2-5 jours ouvrables.' },
  { question: 'Retour possible ?', answer: '14 jours avec emballage original.' },
  { question: 'Paiements ?', answer: 'Cartes, PayPal, virement.' },
  { question: 'Suivi ?', answer: 'Numéro envoyé par email.' },
  { question: 'Frais douane ?', answer: 'Selon pays, contactez-nous.' },
]

const fetchProductsAndCategories = async () => {
  try {
    loading.value = true
    const response = await api.get('/api/products', { params: { all: 'true' } })
    const fetchedProducts: Product[] = response.data.products || []
    allProducts.value = fetchedProducts

    const uniqueCategories = Array.from(new Set(fetchedProducts.map((product) => product.category || 'Autres')))
    const categorySections = uniqueCategories.map((category) => ({
      id: category.toLowerCase().replace(/\s+/g, '-'),
      label: category,
      icon: '🛒',
      categories: [category],
    }))
    sections.value = [
      { id: 'all', label: 'Tous les produits', icon: '🌐', categories: [] },
      ...categorySections,
      { id: 'faq', label: 'FAQ', icon: '❓', categories: [] },
    ]

    groupedProducts.value = groupProducts(fetchedProducts)
    activeSection.value = 'all'
  } catch (err: any) {
    console.error('Erreur chargement produits:', err)
    if (err.response) {
      switch (err.response.status) {
        case 400: error.value = 'Paramètres invalides.'; break
        case 401: error.value = 'Connexion requise.'; break
        case 429: error.value = 'Trop de requêtes.'; break
        default: error.value = 'Erreur serveur.'
      }
    } else {
      error.value = 'Connexion perdue.'
    }
  } finally {
    loading.value = false
  }
}

const groupProducts = (products: Product[]) =>
  products.reduce((acc: { [key: string]: Product[] }, product) => {
    const category = product.category || 'Autres'
    if (!acc[category]) acc[category] = []
    acc[category].push(product)
    return acc
  }, {})

const filteredGroupedProducts = computed(() => {
  const filtered = allProducts.value.filter((product) => {
    const matchesSearch = product.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    return matchesSearch
  })
  return groupProducts(filtered)
})

const activeSectionLabel = computed(
  () => sections.value.find((s) => s.id === activeSection.value)?.label || 'Produits'
)

const displayGrouped = computed(() => {
  if (searchTerm.value) return filteredGroupedProducts.value
  return groupedProducts.value
})

const filteredProducts = computed(() => {
  const list = Object.values(displayGrouped.value).flat()
  return [...list].sort((a, b) => {
    if (sortBy.value === 'price') return a.price - b.price
    if (sortBy.value === 'rating') return (b.rating || 0) - (a.rating || 0)
    return a.name.localeCompare(b.name)
  })
})

const sortedGrouped = computed(() => {
  const entries = Object.entries(displayGrouped.value)
  const result: { [key: string]: Product[] } = {}
  for (const [category, products] of entries) {
    result[category] = [...products].sort((a, b) => {
      if (sortBy.value === 'price') return a.price - b.price
      if (sortBy.value === 'rating') return (b.rating || 0) - (a.rating || 0)
      return a.name.localeCompare(b.name)
    })
  }
  return result
})

const totalCartItems = computed(() => cartStore.totalItems)

onMounted(fetchProductsAndCategories)

const handleImageView = (product: Product) => {
  selectedProduct.value = product
  selectedImage.value = product.mediaUrl
  imageZoom.value = 1
  showProductPage.value = false
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
  triggerCartAnimation(productWithDetails.id, productWithDetails.mediaUrl)
}

const handleBuyNow = (productWithDetails: ProductWithDetails) => {
  cartStore.addToCart(productWithDetails)
  showProductPage.value = false
  router.push('/panier')
}

const handleQuickAdd = (product: Product) => {
  const productWithDetails: ProductWithDetails = {
    ...product,
    quantity: 1,
    selectedColor: product.selectedColor || 'default',
    selectedSize: product.selectedSize || 'M',
  }
  cartStore.addToCart(productWithDetails)
  triggerCartAnimation(product.id, product.mediaUrl)
}

const triggerCartAnimation = (id: string, imageUrl: string) => {
  cartAnimation.value = { id, imageUrl, x: 0, y: 0 }
  setTimeout(() => (cartAnimation.value = null), 800)
}

const handleZoomIn = () => (imageZoom.value = Math.min(imageZoom.value + 0.2, 2))
const handleZoomOut = () => (imageZoom.value = Math.max(imageZoom.value - 0.2, 0.5))

const closeImageView = () => {
  selectedImage.value = null
  selectedProduct.value = null
}

const selectMobileSection = (id: string) => {
  activeSection.value = id
  showMobileMenu.value = false
}
</script>

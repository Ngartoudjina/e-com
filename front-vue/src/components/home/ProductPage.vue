<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-2 z-50">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-7xl max-h-[90vh] overflow-hidden relative">
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
          <div class="w-1 h-1 bg-blue-300 rounded-full absolute opacity-100 animate-[float_3s_ease-in-out_infinite_alternate]" style="top: 10%; left: 10%"></div>
          <div class="w-2 h-2 bg-purple-300 rounded-full absolute opacity-100 animate-[float_2.4s_ease-in-out_infinite_alternate]" style="top: 20%; left: 20%"></div>
          <div class="w-1.5 h-1.5 bg-pink-300 rounded-full absolute opacity-100 animate-[float_2.8s_ease-in-out_infinite_alternate]" style="top: 15%; left: 30%"></div>
        </div>

        <div class="sticky top-0 bg-white/95 backdrop-blur-md border-b border-gray-100/50 px-6 py-4 flex justify-between items-center z-10">
          <nav class="hidden sm:flex text-sm text-gray-500 space-x-2">
            <template v-for="(item, index) in breadcrumb" :key="index">
              <span class="hover:text-purple-600 transition-all duration-300 hover:scale-105">
                {{ item }}
                <span v-if="index < breadcrumb.length - 1" class="mx-2 text-gray-300">→</span>
              </span>
            </template>
          </nav>
          <button
            type="button"
            @click="$emit('close')"
            class="group relative p-3 hover:bg-gradient-to-br hover:from-red-50 hover:to-red-100 rounded-xl transition-all duration-300 focus:outline-none hover:scale-110"
            aria-label="Fermer"
          >
            <X class="w-6 h-6 text-gray-400 group-hover:text-red-500 transition-colors duration-300" />
          </button>
        </div>

        <div class="p-6 overflow-y-auto max-h-[calc(90vh-88px)]">
          <div class="space-y-8">
            <div class="space-y-6">
              <div class="flex flex-col lg:flex-row gap-6">
                <div class="w-full lg:w-40 space-y-4">
                  <button
                    v-for="(image, index) in images"
                    :key="image.id"
                    type="button"
                    @click="selectedImage = index"
                    class="group relative w-full h-24 rounded-2xl overflow-hidden transition-all duration-300 hover:scale-105"
                    :class="selectedImage === index ? 'ring-4 ring-purple-500/30 shadow-lg shadow-purple-500/25' : 'ring-2 ring-gray-200/50 hover:ring-gray-300/80'"
                  >
                    <img :src="image.src" :alt="image.alt" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" />
                    <div class="absolute inset-0 transition-all duration-300" :class="selectedImage === index ? 'bg-purple-500/10' : 'bg-transparent group-hover:bg-black/5'"></div>
                  </button>
                </div>

                <div class="flex-1 relative">
                  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-gray-50 to-gray-100 shadow-inner">
                    <img
                      ref="imageRef"
                      :key="selectedImage"
                      :src="images[selectedImage].src"
                      :alt="images[selectedImage].alt"
                      class="w-full h-80 lg:h-96 object-cover transition-all duration-500 hover:scale-105"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/5 via-transparent to-transparent pointer-events-none"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-6">
              <div class="space-y-3">
                <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                  {{ product.name }}
                </h1>
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1">
                    <Star
                      v-for="i in 5"
                      :key="i"
                      class="w-5 h-5 transition-all duration-300"
                      :class="i <= (product.rating || 0) ? 'fill-yellow-400 text-yellow-400 scale-110' : 'text-gray-300'"
                    />
                  </div>
                  <span class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                    {{ product.rating || 0 }} ({{ reviews.length }} avis)
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-purple-50 to-blue-50 rounded-2xl border border-purple-100/50">
                <div class="flex items-baseline gap-3">
                  <span class="text-3xl lg:text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                    {{ product.price }}fcfa
                  </span>
                  <span class="text-lg text-gray-400 line-through">
                    {{ (product.price * 1.2).toFixed(2) }}fcfa
                  </span>
                </div>
                <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1.5 rounded-xl text-sm font-bold shadow-lg">
                  -17%
                </div>
              </div>

              <div class="space-y-3">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                  <Sparkles class="w-5 h-5 text-purple-500" />
                  Couleur
                </h3>
                <div class="flex gap-3 flex-wrap">
                  <button
                    v-for="color in colors"
                    :key="color.name"
                    type="button"
                    @click="selectedColor = color.name"
                    class="relative w-12 h-12 rounded-2xl transition-all duration-300 hover:scale-110"
                    :class="[color.color, color.name === selectedColor ? 'ring-4 ring-purple-500/30 shadow-lg' : 'ring-2 ring-gray-300/50 hover:ring-gray-400/80']"
                  >
                    <div v-if="color.name === selectedColor" class="absolute inset-0 rounded-2xl ring-4 ring-purple-500/30 scale-125 animate-pulse"></div>
                  </button>
                </div>
              </div>

              <div class="space-y-3">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                  <Zap class="w-5 h-5 text-blue-500" />
                  Taille
                </h3>
                <div class="flex gap-3 flex-wrap">
                  <button
                    v-for="size in sizes"
                    :key="size"
                    type="button"
                    @click="selectedSize = size"
                    class="w-14 h-14 rounded-2xl border-2 font-bold transition-all duration-300 hover:scale-105"
                    :class="selectedSize === size ? 'border-purple-500 bg-gradient-to-br from-purple-500 to-blue-500 text-white shadow-lg shadow-purple-500/25' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'"
                  >
                    {{ size }}
                  </button>
                </div>
              </div>

              <div class="space-y-3">
                <h3 class="text-lg font-semibold">Quantité</h3>
                <div class="flex items-center gap-4 bg-gray-50 p-2 rounded-2xl w-fit">
                  <button
                    type="button"
                    @click="quantity = Math.max(1, quantity - 1)"
                    class="p-3 bg-white rounded-xl hover:bg-gradient-to-br hover:from-red-50 hover:to-red-100 transition-all duration-300 shadow-sm hover:shadow-md"
                  >
                    <Minus class="w-5 h-5 text-gray-600" />
                  </button>
                  <span class="w-12 text-center text-lg font-bold">{{ quantity }}</span>
                  <button
                    type="button"
                    @click="quantity += 1"
                    class="p-3 bg-white rounded-xl hover:bg-gradient-to-br hover:from-green-50 hover:to-green-100 transition-all duration-300 shadow-sm hover:shadow-md"
                  >
                    <Plus class="w-5 h-5 text-gray-600" />
                  </button>
                </div>
              </div>

              <div class="flex flex-col sm:flex-row gap-4">
                <button
                  type="button"
                  @click="handleBuyNow"
                  class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 px-6 rounded-2xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all duration-300 flex items-center justify-center gap-3 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:scale-105"
                >
                  <ShoppingCart class="w-5 h-5" />
                  ACHETER MAINTENANT
                </button>
                <button
                  type="button"
                  @click="handleAddToCart"
                  :disabled="isAnimating"
                  class="flex-1 border-2 border-purple-500 text-purple-600 py-4 px-6 rounded-2xl font-bold hover:bg-gradient-to-br hover:from-purple-50 hover:to-blue-50 transition-all duration-300 hover:scale-105 disabled:opacity-50"
                >
                  {{ isAnimating ? 'AJOUT EN COURS...' : 'AJOUTER AU PANIER' }}
                </button>
              </div>

              <div class="bg-gradient-to-br from-green-50 to-blue-50 p-5 rounded-2xl border border-green-200/50">
                <div class="space-y-3">
                  <div class="flex items-center gap-3 text-sm font-medium">
                    <div class="w-3 h-3 bg-gradient-to-r from-green-400 to-green-500 rounded-full animate-pulse"></div>
                    <span class="text-gray-700">Livraison gratuite 24h</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm font-medium">
                    <Shield class="w-4 h-4 text-blue-500" />
                    <span class="text-gray-700">Garantie 2 ans</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm font-medium">
                    <div class="w-3 h-3 bg-gradient-to-r from-purple-400 to-purple-500 rounded-full"></div>
                    <span class="text-gray-700">Retour 30 jours</span>
                  </div>
                </div>
              </div>

              <div class="flex gap-6 pt-2">
                <button type="button" @click="isWishlisted = !isWishlisted" class="flex items-center gap-2 text-gray-600 hover:text-purple-600 transition-all duration-300 hover:scale-105">
                  <Heart class="w-6 h-6 transition-all duration-300" :class="isWishlisted ? 'fill-purple-600 text-purple-600 scale-110' : ''" />
                  <span class="font-medium">Favoris</span>
                </button>
                <button type="button" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 transition-all duration-300 hover:scale-105">
                  <Share2 class="w-6 h-6" />
                  <span class="font-medium">Partager</span>
                </button>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-200/50 pt-8 mt-8">
            <div class="flex flex-wrap gap-3 mb-6">
              <button
                v-for="tab in tabs"
                :key="tab"
                type="button"
                @click="activeTab = tab"
                class="px-6 py-3 rounded-2xl font-semibold transition-all duration-300 hover:scale-105"
                :class="activeTab === tab ? 'bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-lg shadow-purple-500/25' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              >
                {{ tab }}
              </button>
            </div>

            <div v-if="activeTab === 'Reviews'" class="space-y-6">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-2xl font-bold">Avis clients ({{ reviews.length }})</h2>
                <select
                  v-model="sortBy"
                  class="border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300"
                >
                  <option value="Newest">Plus récents</option>
                  <option value="Highest Rated">Mieux notés</option>
                  <option value="Most Helpful">Plus utiles</option>
                </select>
              </div>

              <div class="space-y-4">
                <div v-for="review in reviews" :key="review.id" class="bg-gradient-to-br from-gray-50 to-white p-5 rounded-2xl hover:shadow-lg transition-all duration-300 border border-gray-100/50">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full flex items-center justify-center">
                      <User class="w-5 h-5 text-purple-600" />
                    </div>
                    <div>
                      <p class="font-semibold text-gray-900">{{ review.author }}</p>
                      <p class="text-sm text-gray-500">{{ review.date }}</p>
                    </div>
                  </div>
                  <div class="flex items-center mb-3">
                    <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
                  </div>
                  <p class="text-gray-700 mb-4 leading-relaxed">{{ review.text }}</p>
                  <div class="flex gap-4">
                    <button type="button" class="flex items-center gap-2 text-sm text-gray-500 hover:text-green-600 transition-colors duration-300">
                      <ThumbsUp class="w-4 h-4" />
                      <span>{{ review.helpful }}</span>
                    </button>
                    <button type="button" class="flex items-center gap-2 text-sm text-gray-500 hover:text-red-600 transition-colors duration-300">
                      <ThumbsDown class="w-4 h-4" />
                      <span>{{ review.unhelpful }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="activeTab === 'Description'">
              <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-2xl border border-gray-100/50">
                <p class="text-gray-700 leading-relaxed">
                  {{ product.description || 'Ce produit technologique de haute qualité offre des performances exceptionnelles et un design moderne. Parfait pour les professionnels et les passionnés de technologie qui recherchent l\'excellence et l\'innovation dans chaque détail.' }}
                </p>
              </div>
            </div>

            <div v-else>
              <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-2xl border border-gray-100/50">
                <h3 class="font-bold text-xl mb-6">Caractéristiques techniques</h3>
                <div class="space-y-4">
                  <div
                    v-for="spec in specifications"
                    :key="spec.label"
                    class="flex justify-between items-center p-4 bg-white rounded-xl border border-gray-200/50 hover:shadow-md transition-all duration-300"
                  >
                    <span class="text-gray-600 font-medium">{{ spec.label }}</span>
                    <span class="font-bold text-gray-900">{{ spec.value }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Star, Heart, Share2, ThumbsUp, ThumbsDown, User, X, ShoppingCart, Plus, Minus, Sparkles, Zap, Shield } from 'lucide-vue-next'
import type { Product, ProductWithDetails } from '@/types'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'addToCart', product: ProductWithDetails): void
  (e: 'buyNow', product: ProductWithDetails): void
}>()

const router = useRouter()

const selectedImage = ref(0)
const selectedColor = ref('black')
const selectedSize = ref('M')
const quantity = ref(1)
const activeTab = ref('Reviews')
const sortBy = ref('Newest')
const isWishlisted = ref(false)
const isAnimating = ref(false)

const images = computed(() => [
  { id: 0, src: props.product.mediaUrl, alt: `${props.product.name} - Image principale` },
  { id: 1, src: props.product.mediaUrl, alt: `${props.product.name} - Variante 1` },
  { id: 2, src: props.product.mediaUrl, alt: `${props.product.name} - Variante 2` },
])

const colors = computed(() => [
  { name: 'black', color: 'bg-gradient-to-br from-gray-900 to-black', selected: selectedColor.value === 'black' },
  { name: 'silver', color: 'bg-gradient-to-br from-gray-300 to-gray-400', selected: selectedColor.value === 'silver' },
  { name: 'white', color: 'bg-gradient-to-br from-white to-gray-100 border-2', selected: selectedColor.value === 'white' },
  { name: 'blue', color: 'bg-gradient-to-br from-blue-400 to-blue-600', selected: selectedColor.value === 'blue' },
])

const sizes = ['S', 'M', 'L', 'XL']
const tabs = ['Reviews', 'Description', 'Spécifications']

const reviews = [
  { id: 1, author: 'Jean Dupont', rating: 5, date: '2 jours ago', text: 'Super produit tech, très performant! Je recommande vivement.', helpful: 12, unhelpful: 0 },
  { id: 2, author: 'Marie Leclerc', rating: 4, date: '5 jours ago', text: 'Bon rapport qualité/prix. Livraison rapide.', helpful: 8, unhelpful: 1 },
  { id: 3, author: 'Pierre Martin', rating: 5, date: '1 semaine ago', text: 'Excellent produit! Dépasse mes attentes.', helpful: 15, unhelpful: 0 },
]

const breadcrumb = computed(() => ['Tech', props.product.category || 'Produits', props.product.name])

const specifications = computed(() => [
  { label: 'Catégorie', value: props.product.category },
  { label: 'Garantie', value: '2 ans' },
  { label: 'Livraison', value: '24h gratuite' },
  { label: 'Retours', value: '30 jours' },
])

const handleAddToCart = () => {
  isAnimating.value = true
  setTimeout(() => {
    emit('addToCart', { ...props.product, quantity: quantity.value, selectedColor: selectedColor.value, selectedSize: selectedSize.value })
    isAnimating.value = false
    emit('close')
  }, 800)
}

const handleBuyNow = () => {
  emit('buyNow', { ...props.product, quantity: quantity.value, selectedColor: selectedColor.value, selectedSize: selectedSize.value })
  router.push('/panier')
  emit('close')
}
</script>

<style scoped>
@keyframes float {
  0% { transform: translateY(0px) rotate(0deg); }
  100% { transform: translateY(-10px) rotate(10deg); }
}
</style>

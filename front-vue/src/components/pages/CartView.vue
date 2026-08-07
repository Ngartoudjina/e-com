<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
      <Transition name="toast">
        <div
          v-if="showSuccess || verificationMessage"
          class="fixed top-4 right-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white px-4 py-3 rounded-xl shadow-2xl flex items-center space-x-2 z-50 animate-pulse"
        >
          <CheckCircle class="w-5 h-5" />
          <span class="text-sm font-medium">
            {{ verificationMessage || 'Action réussie !' }}
          </span>
        </div>
      </Transition>

      <div class="flex flex-col xl:flex-row gap-4 lg:gap-6">
        <div class="flex-1 bg-white/80 backdrop-blur-sm rounded-2xl lg:rounded-3xl p-4 sm:p-6 shadow-xl border border-white/50">
          <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <div class="flex items-center space-x-3">
              <button type="button" class="p-2 hover:bg-indigo-50 rounded-full transition-colors text-indigo-600" @click="$router.back()">
                <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
              </button>
              <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Panier</h1>
                <span class="bg-gradient-to-r from-indigo-500 to-blue-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                  {{ cartStore.totalCount }} articles
                </span>
              </div>
            </div>
            <div class="flex space-x-2">
              <button type="button" class="p-2 hover:bg-red-50 rounded-full transition-colors text-gray-600 hover:text-red-500" @click="handleBecomeAffiliate">
                <Heart class="w-5 h-5" />
              </button>
              <button type="button" class="p-2 hover:bg-blue-50 rounded-full transition-colors text-gray-600 hover:text-blue-500">
                <Share2 class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="bg-gradient-to-r from-indigo-50 via-blue-50 to-purple-50 p-4 sm:p-6 rounded-xl mb-6 shadow-inner border border-indigo-100">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-900 text-base sm:text-lg">
                Avantages d'achat
              </h3>
              <Sparkles class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" />
            </div>

            <div class="space-y-4 mb-6">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center" :class="subtotal >= 35 || affiliateDiscount > 0 ? 'bg-green-100' : 'bg-indigo-100'">
                  <Truck class="w-5 h-5 sm:w-6 sm:h-6" :class="subtotal >= 35 || affiliateDiscount > 0 ? 'text-green-600' : 'text-indigo-600'" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <span class="text-sm sm:text-base font-medium text-gray-800">
                      Livraison gratuite
                    </span>
                    <span class="text-sm text-gray-600">35.00 fcfa</span>
                  </div>
                  <div v-if="subtotal < 35 && affiliateDiscount === 0" class="text-xs sm:text-sm text-gray-500">
                    {{ (35 - subtotal).toFixed(2) }} fcfa de plus
                  </div>
                </div>
              </div>

              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center" :class="subtotal >= 40 ? 'bg-green-100' : 'bg-indigo-100'">
                  <Tag class="w-5 h-5 sm:w-6 sm:h-6" :class="subtotal >= 40 ? 'text-green-600' : 'text-indigo-600'" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <span class="text-sm sm:text-base font-medium text-gray-800">
                      5% de réduction
                    </span>
                    <span class="text-sm text-gray-600">40.00 fcfa</span>
                  </div>
                  <div v-if="subtotal < 40" class="text-xs sm:text-sm text-gray-500">
                    {{ (40 - subtotal).toFixed(2) }} fcfa de plus
                  </div>
                </div>
              </div>

              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center" :class="subtotal >= 50 ? 'bg-green-100' : 'bg-gray-100'">
                  <Gift class="w-5 h-5 sm:w-6 sm:h-6" :class="subtotal >= 50 ? 'text-green-600' : 'text-gray-400'" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <span class="text-sm sm:text-base font-medium text-gray-800">
                      Kit de savon gratuit
                    </span>
                    <span class="text-sm text-gray-600">50.00 fcfa</span>
                  </div>
                  <div v-if="subtotal < 50" class="text-xs sm:text-sm text-gray-500">
                    {{ (50 - subtotal).toFixed(2) }} fcfa de plus
                  </div>
                </div>
              </div>
            </div>

            <div>
              <div class="w-full bg-gray-200 rounded-full h-3 sm:h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-blue-500 h-full rounded-full transition-all duration-700 ease-out shadow-lg" :style="{ width: `${progressPercentage}%` }"></div>
              </div>
              <div class="text-xs sm:text-sm text-gray-600 mt-2 text-center font-medium">
                {{ amountToNext > 0 ? `${amountToNext.toFixed(2)} fcfa pour la prochaine récompense` : 'Toutes les récompenses débloquées ! 🎉' }}
              </div>
            </div>
          </div>

          <div v-if="cartStore.cartItems.length > 0" class="space-y-4 sm:space-y-6">
            <div v-for="item in cartStore.cartItems" :key="item.id" class="group p-4 sm:p-5 rounded-xl border border-gray-100 hover:border-indigo-200 hover:shadow-xl transition-all duration-300 bg-white/70 backdrop-blur-sm">
              <div class="flex items-start space-x-3 sm:space-x-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl flex items-center justify-center relative overflow-hidden shadow-md flex-shrink-0 bg-gray-200">
                  <img :src="item.mediaUrl || '/api/placeholder/80/80'" :alt="item.name" class="w-14 h-16 sm:w-16 sm:h-20 object-contain" />
                  <div v-if="item.isNew" class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full font-medium">
                    NEW
                  </div>
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex-1 pr-2">
                      <h3 class="font-semibold text-base sm:text-lg text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2">
                        {{ item.name }}
                      </h3>
                      <p class="text-xs sm:text-sm text-gray-500 capitalize mt-1">
                        {{ item.description ?? 'Pas de description' }}
                      </p>
                      <div class="flex items-center space-x-2 mt-2">
                        <span class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                          {{ item.category ?? 'Non catégorisé' }}
                        </span>
                        <div class="flex items-center text-xs text-yellow-600">
                          ⭐ {{ item.rating ?? 0 }}
                        </div>
                      </div>
                    </div>

                    <button type="button" @click="removeItem(item.id)" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all duration-200 flex-shrink-0">
                      <X class="w-4 h-4 sm:w-5 sm:h-5" />
                    </button>
                  </div>

                  <div class="flex items-center justify-between mt-3 sm:mt-4 flex-col lg:flex-row gap-2">
                    <div class="flex items-center space-x-2 bg-gray-50 rounded-full p-1.5">
                      <button
                        type="button"
                        @click="handleUpdateQuantity(item.id, (item.quantity || 1) - 1)"
                        class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center hover:bg-white rounded-full transition-colors text-indigo-600"
                        :disabled="(item.quantity || 1) <= 1"
                      >
                        <Minus class="w-4 h-4" />
                      </button>
                      <span class="w-8 text-center font-medium text-base sm:text-lg">
                        {{ item.quantity || 1 }}
                      </span>
                      <button
                        type="button"
                        @click="handleUpdateQuantity(item.id, (item.quantity || 1) + 1)"
                        class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center hover:bg-white rounded-full transition-colors text-indigo-600"
                      >
                        <Plus class="w-4 h-4" />
                      </button>
                    </div>

                    <div class="text-right">
                      <div class="flex items-center space-x-1 sm:space-x-2">
                        <span v-if="item.originalPrice && item.originalPrice > item.price" class="text-xs sm:text-sm text-gray-500 line-through">
                          {{ item.originalPrice.toFixed(2) }} fcfa
                        </span>
                        <span class="font-bold text-lg sm:text-xl text-gray-900">
                          {{ item.price.toFixed(2) }} fcfa
                        </span>
                      </div>
                      <div class="text-xs sm:text-sm text-gray-600">
                        Total: {{ (item.price * (item.quantity || 1)).toFixed(2) }} fcfa
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-16 sm:py-20">
            <ShoppingBag class="w-20 h-20 sm:w-24 sm:h-24 text-gray-300 mx-auto mb-4 sm:mb-6" />
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 sm:mb-4">
              Votre panier est vide
            </h3>
            <p class="text-gray-600 mb-4 sm:mb-6 text-sm sm:text-base">
              Parcourez nos produits et ajoutez des articles à votre panier.
            </p>
            <button type="button" class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-indigo-700 hover:to-blue-700 transition-colors shadow-lg hover:shadow-xl" @click="$router.push('/')">
              Commencer les achats
            </button>
          </div>
        </div>

        <div class="w-full xl:w-96 bg-white/80 backdrop-blur-sm rounded-2xl lg:rounded-3xl p-4 sm:p-6 shadow-xl border border-white/50 xl:sticky xl:top-6 xl:h-fit">
          <h2 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6 text-gray-900">
            Résumé
          </h2>

          <div class="space-y-4 sm:space-y-5 mb-4 sm:mb-6">
            <div class="border-b pb-4 sm:pb-5">
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm sm:text-base font-medium text-gray-800">
                  Code promo
                </span>
                <button type="button" @click="showCouponInput = !showCouponInput" class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                  {{ showCouponInput ? 'Annuler' : '+ Ajouter' }}
                </button>
              </div>

              <div v-if="showCouponInput" class="flex space-x-2 sm:space-x-3 mt-3">
                <input
                  v-model="couponCode"
                  type="text"
                  placeholder="Code promo"
                  class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button type="button" @click="applyCoupon" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors">
                  OK
                </button>
              </div>

              <div v-if="appliedCoupon" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-green-800 font-medium">
                    {{ appliedCoupon.code }} appliqué
                  </span>
                  <button type="button" @click="appliedCoupon = null" class="text-sm text-green-600 hover:text-green-700">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            <div class="border-b pb-4 sm:pb-5">
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm sm:text-base font-medium text-gray-800">
                  Code affilié
                </span>
                <button type="button" @click="showCouponInput = !showCouponInput" class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                  {{ showCouponInput ? 'Annuler' : '+ Ajouter' }}
                </button>
              </div>

              <div v-if="showCouponInput" class="flex space-x-2 sm:space-x-3 mt-3">
                <input
                  v-model="affiliateInput"
                  type="text"
                  placeholder="Entrez un code affilié"
                  class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button
                  type="button"
                  @click="verifyAffiliateLink"
                  :disabled="isLoading"
                  class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
                  <span v-else>OK</span>
                </button>
              </div>

              <div v-if="affiliateCode" class="mt-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-indigo-800 font-medium">
                    Réduction affilié ({{ affiliateCode }})
                  </span>
                  <button type="button" @click="clearAffiliate" class="text-sm text-indigo-600 hover:text-indigo-700">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            <div class="space-y-3 sm:space-y-4">
              <div class="flex justify-between text-sm">
                <span class="text-gray-700">Sous-total</span>
                <span class="font-medium text-gray-900">
                  {{ subtotal.toFixed(2) }} fcfa
                </span>
              </div>

              <div v-if="savings > 0" class="flex justify-between text-sm text-green-600">
                <span>Économies</span>
                <span class="font-medium">{{ savings.toFixed(2) }} fcfa</span>
              </div>

              <div class="flex justify-between text-sm">
                <span class="text-gray-700">Frais de traitement</span>
                <span class="text-gray-900">
                  {{ processingFee.toFixed(2) }} fcfa
                </span>
              </div>

              <div class="flex justify-between text-sm">
                <span class="text-gray-700">Livraison</span>
                <span :class="shippingFee === 0 ? 'text-green-600 font-medium' : 'text-gray-900'">
                  {{ shippingFee === 0 ? 'GRATUIT' : `${shippingFee.toFixed(2)} fcfa` }}
                </span>
              </div>

              <div v-if="appliedCoupon && discount > 0" class="flex justify-between text-sm text-green-600">
                <span>Réduction ({{ appliedCoupon.code }})</span>
                <span class="font-medium">-f{{ discount.toFixed(2) }}</span>
              </div>

              <div v-if="affiliateDiscountAmount > 0" class="flex justify-between text-sm text-indigo-600">
                <span>Réduction affilié</span>
                <span class="font-medium">
                  -{{ affiliateDiscountAmount.toFixed(2) }} fcfa
                </span>
              </div>
            </div>
          </div>

          <div class="border-t pt-4 sm:pt-5 mb-4 sm:mb-6">
            <div class="flex justify-between items-center">
              <span class="text-lg sm:text-xl font-bold text-gray-900">
                Total
              </span>
              <span class="text-xl sm:text-2xl font-bold text-indigo-600">
                {{ total.toFixed(2) }} fcfa
              </span>
            </div>
          </div>

          <button
            type="button"
            @click="handleCheckout"
            :disabled="cartStore.cartItems.length === 0 || isLoading"
            class="w-full py-3 px-4 sm:px-6 rounded-xl font-bold text-white transition-all duration-300 mb-3"
            :class="cartStore.cartItems.length === 0 || isLoading ? 'bg-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 hover:shadow-lg active:scale-95'"
          >
            <div v-if="isLoading" class="flex items-center justify-center space-x-2">
              <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <span class="text-sm font-medium">Traitement...</span>
            </div>
            <span v-else>Passer à la caisse</span>
          </button>

          <button
            type="button"
            @click="openWhatsApp"
            :disabled="cartStore.cartItems.length === 0"
            class="w-full py-3 px-4 sm:px-6 rounded-xl font-bold text-white transition-all duration-300 flex items-center justify-center space-x-2"
            :class="cartStore.cartItems.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 hover:shadow-lg active:scale-95'"
          >
            <MessageCircle class="w-5 h-5" />
            <span>Commander via WhatsApp</span>
          </button>

          <div class="mt-4 text-center">
            <p class="text-xs text-gray-500">
              🔒 Paiement sécurisé par Stripe
            </p>
            <p class="text-xs text-gray-500 mt-1">
              📱 Support WhatsApp disponible
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowLeft, Plus, Minus, X, ShoppingBag, Gift, Truck, Tag, Heart, Share2, Sparkles, CheckCircle, MessageCircle, Loader2 } from 'lucide-vue-next'
import { api } from '@/lib/api'
import { useCartStore } from '@/stores/cart'
import type { ProductWithDetails } from '@/types'

interface Coupon {
  discount: number
  type: 'percentage' | 'fixed' | 'shipping'
}

interface AppliedCoupon extends Coupon {
  code: string
}

const cartStore = useCartStore()

const couponCode = ref('')
const appliedCoupon = ref<AppliedCoupon | null>(null)
const showCouponInput = ref(false)
const showSuccess = ref(false)
const isLoading = ref(false)
const affiliateCode = ref<string | null>(null)
const affiliateDiscount = ref(0)
const verificationMessage = ref<string | null>(null)
const affiliateInput = ref('')

const coupons: Record<string, Coupon> = {
  'SAVE10': { discount: 0.1, type: 'percentage' },
  'WELCOME5': { discount: 5.0, type: 'fixed' },
  'FREESHIP': { discount: 0, type: 'shipping' },
}

let successTimer: ReturnType<typeof setTimeout> | undefined

const showToast = (message?: string) => {
  verificationMessage.value = message ?? null
  showSuccess.value = true
  clearTimeout(successTimer)
  successTimer = setTimeout(() => {
    showSuccess.value = false
    verificationMessage.value = null
  }, 3000)
}

const handleBecomeAffiliate = () => {
  const affiliateId = `USER-${Math.random().toString(36).substring(2, 9)}`
  const baseUrl = window.location.origin + window.location.pathname
  const uniqueCode = `AFF-${affiliateId}-${Date.now()}`.slice(0, 10)
  const link = `${baseUrl}?ref=${uniqueCode}`
  alert(
    `Votre lien d'affiliation : ${link}. Partagez-le pour bénéficier de 5% de réduction sur vos prochains achats !`
  )
}

const handleUpdateQuantity = (id: string, newQuantity: number) => {
  if (newQuantity === 0) {
    cartStore.removeFromCart(id)
  } else {
    cartStore.updateQuantity(id, newQuantity)
  }
}

const removeItem = (id: string) => {
  cartStore.removeFromCart(id)
  showSuccess.value = true
  setTimeout(() => (showSuccess.value = false), 2000)
}

const applyCoupon = () => {
  const couponCodeUpper = couponCode.value.toUpperCase()
  const coupon = coupons[couponCodeUpper]
  if (coupon) {
    appliedCoupon.value = { code: couponCodeUpper, ...coupon }
    showCouponInput.value = false
    couponCode.value = ''
    showToast()
  }
}

const verifyAffiliateLink = async () => {
  try {
    isLoading.value = true
    const response = await api.get(`/api/affiliate/verify-link?ref=${affiliateInput.value}`)
    affiliateCode.value = affiliateInput.value
    affiliateDiscount.value = 0.05
    showToast(response.data.message)
    affiliateInput.value = ''
  } catch (error: any) {
    showToast(error.response?.data?.message || 'Erreur lors de la vérification du lien.')
  } finally {
    isLoading.value = false
  }
}

const clearAffiliate = () => {
  affiliateCode.value = null
  affiliateDiscount.value = 0
}

const subtotal = computed(() =>
  cartStore.cartItems.reduce((sum, item) => sum + (item.price * (item.quantity || 1)), 0)
)

const savings = computed(() =>
  cartStore.cartItems.reduce(
    (sum, item) => sum + (((item.originalPrice ?? item.price) - item.price) * (item.quantity || 1)),
    0
  )
)

const processingFee = 2.0

const discount = computed(() => {
  if (!appliedCoupon.value) return 0
  if (appliedCoupon.value.type === 'percentage') return subtotal.value * appliedCoupon.value.discount
  if (appliedCoupon.value.type === 'fixed') return appliedCoupon.value.discount
  return 0
})

const affiliateDiscountAmount = computed(() => (affiliateDiscount.value > 0 ? subtotal.value * affiliateDiscount.value : 0))

const shippingFee = computed(() =>
  (appliedCoupon.value?.type === 'shipping' || subtotal.value >= 35 || affiliateDiscount.value > 0) ? 0 : 4.99
)

const total = computed(() => subtotal.value + processingFee + shippingFee.value - discount.value - affiliateDiscountAmount.value)

const progressPercentage = computed(() => Math.min((subtotal.value / 50) * 100, 100))

const nextMilestone = computed(() => (subtotal.value < 35 ? 35 : subtotal.value < 40 ? 40 : 50))
const amountToNext = computed(() => Math.max(0, nextMilestone.value - subtotal.value))

const handleCheckout = () => {
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
    alert('Redirection vers la caisse...')
  }, 2000)
}

const openWhatsApp = () => {
  const phoneNumber = '+22966754939'
  const message = `Bonjour! Je souhaite passer commande pour les articles suivants:\n\n${cartStore.cartItems
    .map(
      (item) =>
        `• ${item.name} (Quantité: ${item.quantity || 1}) - ${(item.price * (item.quantity || 1)).toFixed(2)} fcfa`
    )
    .join('\n')}\n\nTotal: ${total.value.toFixed(2)} fcfa`
  const whatsappUrl = `https://wa.me/${phoneNumber.replace('+', '')}?text=${encodeURIComponent(message)}`
  window.open(whatsappUrl, '_blank')
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
}
</style>

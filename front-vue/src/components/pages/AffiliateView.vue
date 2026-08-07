<template>
  <div>
    <div v-if="!isAuthenticated" class="text-center p-3 text-sm">
      Connectez-vous pour accéder.
    </div>

    <div v-else-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="w-6 h-6 border-4 border-purple-200 border-t-purple-500 rounded-full animate-spin" style="animation-duration: 1s"></div>
    </div>

    <div v-else-if="isAffiliate && affiliateData" class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 p-3">
      <div class="max-w-2xl mx-auto">
        <div class="mb-6">
          <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center hidden md:flex hover:rotate-180 transition-transform duration-500">
                  <Award class="w-6 h-6 text-white" />
                </div>
                <div>
                  <h1 class="text-xl font-bold text-gray-900">
                    Lien d'Affiliation
                  </h1>
                  <p class="text-gray-600 text-sm">
                    Code: <span class="font-mono font-semibold">{{ affiliateData.affiliateCode }}</span>
                  </p>
                </div>
              </div>
              <div class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                ✓ Actif
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
          <h3 class="text-base font-semibold text-gray-900 mb-3">
            Votre Lien
          </h3>
          <div class="flex items-center space-x-2">
            <div class="flex-1 bg-gray-50 rounded-lg p-2 font-mono text-xs text-gray-700 break-all">
              {{ affiliateData.referralLink }}
            </div>
            <button type="button" @click="copyToClipboard(affiliateData.referralLink)" class="bg-gray-100 hover:bg-gray-200 p-2 rounded-lg transition-colors hover:scale-105 active:scale-95">
              <CheckCircle v-if="copySuccess" class="w-4 h-4 text-green-500" />
              <Copy v-else class="w-4 h-4 text-gray-600" />
            </button>
            <button type="button" @click="shareLink" class="bg-gradient-to-r from-purple-500 to-blue-500 text-white p-2 rounded-lg hover:scale-105 active:scale-95 transition-transform">
              <Share2 class="w-4 h-4" />
            </button>
          </div>
          <Transition name="fade">
            <p v-if="copySuccess" class="text-green-600 text-xs mt-1">
              Lien copié!
            </p>
          </Transition>
          <p class="mt-3 text-gray-600 text-sm">
            Clients: {{ affiliateData.referralCount }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-4 mt-4">
          <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2 mb-3">
              <div class="p-2 bg-purple-100 rounded-lg">
                <DollarSign class="w-5 h-5 text-purple-600" />
              </div>
              <h3 class="text-base font-semibold text-gray-900">Commission</h3>
            </div>
            <p class="text-2xl font-bold text-gray-900">
              {{ affiliateData.commissionRate }}%
            </p>
            <p class="text-gray-500 text-xs mt-1">Par vente</p>
          </div>

          <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2 mb-3">
              <div class="p-2 bg-blue-100 rounded-lg">
                <Users class="w-5 h-5 text-blue-600" />
              </div>
              <h3 class="text-base font-semibold text-gray-900">Parrainages</h3>
            </div>
            <p class="text-2xl font-bold text-gray-900">
              {{ affiliateData.referralCount }}
            </p>
            <p class="text-gray-500 text-xs mt-1">Clients actifs</p>
          </div>

          <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2 mb-3">
              <div class="p-2 bg-green-100 rounded-lg">
                <TrendingUp class="w-5 h-5 text-green-600" />
              </div>
              <h3 class="text-base font-semibold text-gray-900">Gains</h3>
            </div>
            <p class="text-2xl font-bold text-gray-900">
              {{ (affiliateData.totalEarnings || 0).toFixed(2) }}€
            </p>
            <p class="text-gray-500 text-xs mt-1">Total</p>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="hasPendingRequest || requestStatus === 'pending'" class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 p-3">
      <div class="max-w-2xl mx-auto text-center py-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-6 shadow-lg">
          <Clock class="w-10 h-10 text-yellow-500" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-3">
          En Attente
        </h1>
        <p class="text-base text-gray-600 mb-6 max-w-lg mx-auto">
          Votre demande est en cours de traitement.
        </p>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <AlertCircle class="h-4 w-4 text-yellow-400" />
            </div>
            <div class="ml-2">
              <h3 class="text-xs font-medium text-yellow-800">
                Vérification
              </h3>
              <div class="mt-1 text-xs text-yellow-700">
                <p>
                  Examen en cours, jusqu'à 48h.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="requestStatus === 'rejected'" class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 p-3">
      <div class="max-w-2xl mx-auto text-center py-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-6 shadow-lg">
          <X class="w-10 h-10 text-red-500" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-3">
          Refusée
        </h1>
        <p class="text-base text-gray-600 mb-6 max-w-lg mx-auto">
          Votre demande n'a pas été approuvée.
        </p>

        <div v-if="rejectionReason" class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <AlertCircle class="h-4 w-4 text-red-400" />
            </div>
            <div class="ml-2">
              <h3 class="text-xs font-medium text-red-800">
                Raison
              </h3>
              <div class="mt-1 text-xs text-red-700">
                <p>{{ rejectionReason }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-100">
          <p class="text-gray-700 text-sm mb-4">
            Soumettez une nouvelle demande si nécessaire.
          </p>
          <button
            type="button"
            @click="resetRejected"
            class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white px-4 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 text-sm hover:scale-105 active:scale-95"
          >
            Nouvelle demande
          </button>
        </div>
      </div>
    </div>

    <div v-else class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 p-3">
      <div class="max-w-2xl mx-auto text-center py-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full mb-6 shadow-lg hover:scale-105 hover:rotate-5 transition-transform">
          <Star class="w-10 h-10 text-white" />
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-3">
          Affiliation
        </h1>

        <p class="text-base text-gray-600 mb-6 max-w-lg mx-auto">
          Rejoignez notre programme et gagnez 5% par vente!
        </p>

        <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-100">
          <div v-if="submitSuccess" class="text-center py-6">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-1">Demande envoyée!</h3>
            <p class="text-gray-600 text-sm">Nous vous contacterons bientôt.</p>
          </div>

          <form v-else @submit.prevent="handleSubmitRequest" class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">
                Motivation *
              </label>
              <textarea
                v-model="reason"
                :class="errors.reason ? 'border-red-300 bg-red-50' : 'border-gray-300'"
                class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-purple-500 transition-all duration-200 resize-none"
                rows="3"
                placeholder="Pourquoi devenir affilié? (expérience, canaux...)"
                :disabled="hasPendingRequest"
              ></textarea>
              <p v-if="errors.reason" class="text-red-600 text-xs mt-1 flex items-center">
                <AlertCircle class="w-3 h-3 mr-1" />
                {{ errors.reason }}
              </p>
              <p class="text-gray-500 text-xs mt-1">
                {{ reason.length }}/500
              </p>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">
                Carte d'identité *
              </label>
              <div v-if="!imagePreview">
                <div
                  class="relative border-2 border-dashed rounded-xl p-6 text-center transition-all duration-200"
                  :class="errors.identityCard ? 'border-red-300 bg-red-50' : hasPendingRequest ? 'border-gray-200 bg-gray-50 cursor-not-allowed' : 'border-gray-300 hover:border-purple-400 hover:bg-purple-50 cursor-pointer'"
                >
                  <input
                    type="file"
                    accept="image/*"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    :disabled="hasPendingRequest"
                    @change="handleFileChange"
                  />
                  <Upload class="w-8 h-8 mx-auto mb-3" :class="hasPendingRequest ? 'text-gray-300' : 'text-gray-400'" />
                  <p class="font-medium text-sm" :class="hasPendingRequest ? 'text-gray-400' : 'text-gray-600'">
                    {{ hasPendingRequest ? 'Upload désactivé' : 'Uploader image' }}
                  </p>
                  <p class="text-xs" :class="hasPendingRequest ? 'text-gray-300' : 'text-gray-500'">
                    PNG, JPG, max 5MB
                  </p>
                </div>
              </div>
              <div v-else class="relative">
                <div class="relative bg-gray-100 rounded-xl p-3">
                  <img :src="imagePreview" alt="Prévisualisation" class="w-full h-40 object-cover rounded-lg" />
                  <button
                    v-if="!hasPendingRequest"
                    type="button"
                    @click="removeImage"
                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 shadow-lg hover:bg-red-600 transition-colors hover:scale-110 active:scale-90"
                  >
                    <X class="w-3 h-3" />
                  </button>
                </div>
                <p class="text-gray-600 text-xs mt-1 text-center">
                  {{ identityCard?.name }} ({{ ((identityCard?.size || 0) / 1024 / 1024).toFixed(2) }} MB)
                </p>
              </div>
              <p v-if="errors.identityCard" class="text-red-600 text-xs mt-1 flex items-center">
                <AlertCircle class="w-3 h-3 mr-1" />
                {{ errors.identityCard }}
              </p>
            </div>

            <button
              type="submit"
              :disabled="submitLoading || hasPendingRequest"
              class="w-full px-4 py-3 rounded-xl font-semibold shadow-lg transition-all duration-200 text-sm"
              :class="hasPendingRequest ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-purple-500 to-blue-500 text-white hover:shadow-xl hover:scale-105 active:scale-95'"
            >
              <div v-if="submitLoading" class="flex items-center justify-center">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></div>
                Envoi...
              </div>
              <span v-else-if="hasPendingRequest" class="flex items-center justify-center">
                Demande en attente
              </span>
              <span v-else class="flex items-center justify-center">
                Soumettre
                <ChevronRight class="w-4 h-4 ml-1" />
              </span>
            </button>
          </form>
        </div>

        <div class="grid grid-cols-1 gap-4 mt-8 max-w-2xl mx-auto">
          <div v-for="item in benefits" :key="item.title" class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 hover:-translate-y-1 hover:scale-105 transition-transform">
            <component :is="item.icon" class="w-6 h-6 text-purple-500 mb-2 mx-auto" />
            <h3 class="font-semibold text-gray-900 text-sm">{{ item.title }}</h3>
            <p class="text-gray-600 text-xs">{{ item.desc }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { markRaw, onBeforeUnmount, onMounted, ref, type Component } from 'vue'
import { Copy, Share2, DollarSign, Users, TrendingUp, CheckCircle, Star, Award, ChevronRight, Upload, X, AlertCircle, Clock } from 'lucide-vue-next'
import { api } from '@/lib/api'

interface AffiliateData {
  uid: string
  affiliateCode: string
  referralLink: string
  identityCardUrl?: string
  commissionRate: number
  totalEarnings: number
  totalReferrals: number
  isActive: boolean
  createdAt: string
  referralCount: number
}

interface AffiliateStatusResponse {
  isAffiliate: boolean
  affiliateData?: AffiliateData
  requestStatus?: 'pending' | 'approved' | 'rejected' | null
  rejectionReason?: string
  hasPendingRequest?: boolean
}

const isAuthenticated = ref(false)
const isAffiliate = ref(false)
const affiliateData = ref<AffiliateData | null>(null)
const loading = ref(true)
const copySuccess = ref(false)
const reason = ref('')
const identityCard = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const userId = ref<string | null>(null)
const submitLoading = ref(false)
const errors = ref<{ reason?: string; identityCard?: string }>({})
const submitSuccess = ref(false)
const requestStatus = ref<'pending' | 'approved' | 'rejected' | null>(null)
const rejectionReason = ref<string | null>(null)
const hasPendingRequest = ref(false)

const benefits: { icon: Component; title: string; desc: string }[] = [
  { icon: markRaw(DollarSign), title: '5% Commission', desc: 'Par vente' },
  { icon: markRaw(Users), title: 'Support', desc: 'Assistance dédiée' },
  { icon: markRaw(TrendingUp), title: 'Outils', desc: 'Promotion' },
]

let copyTimer: ReturnType<typeof setTimeout> | undefined
let successTimer: ReturnType<typeof setTimeout> | undefined
let pollInterval: ReturnType<typeof setInterval> | undefined

const fetchStatus = async () => {
  loading.value = true
  try {
    const token = localStorage.getItem('token')
    const response = await api.get<AffiliateStatusResponse>('/api/affiliate/status', {
      headers: { Authorization: `Bearer ${token}` },
    })
    isAffiliate.value = response.data.isAffiliate
    affiliateData.value = response.data.affiliateData || null
    requestStatus.value = response.data.requestStatus || null
    rejectionReason.value = response.data.rejectionReason || null
    hasPendingRequest.value = response.data.hasPendingRequest || false
    if (response.data.hasPendingRequest) {
      reason.value = ''
      identityCard.value = null
      imagePreview.value = null
    }
  } catch (err) {
    console.error('Erreur statut:', err)
    isAffiliate.value = false
    requestStatus.value = null
    hasPendingRequest.value = false
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  const token = localStorage.getItem('token')
  if (!token) {
    loading.value = false
    isAuthenticated.value = false
    return
  }
  isAuthenticated.value = true
  try {
    const decoded = JSON.parse(atob(token.split('.')[1]))
    userId.value = decoded.uid
  } catch (e) {
    console.error('Token invalide:', e)
  }
  fetchStatus()
  pollInterval = setInterval(fetchStatus, 30000)
})

onBeforeUnmount(() => {
  clearInterval(pollInterval)
  clearTimeout(copyTimer)
  clearTimeout(successTimer)
})

const handleFileChange = (event: Event) => {
  if (hasPendingRequest.value) return
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      errors.value = { ...errors.value, identityCard: 'Fichier > 5MB' }
      return
    }
    if (!file.type.startsWith('image/')) {
      errors.value = { ...errors.value, identityCard: 'Image invalide' }
      return
    }
    identityCard.value = file
    errors.value = { ...errors.value, identityCard: undefined }
    const reader = new FileReader()
    reader.onload = (event) => {
      imagePreview.value = event.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

const removeImage = () => {
  if (hasPendingRequest.value) return
  identityCard.value = null
  imagePreview.value = null
  errors.value = { ...errors.value, identityCard: undefined }
}

const validateForm = () => {
  const newErrors: { reason?: string; identityCard?: string } = {}
  if (!reason.value.trim()) {
    newErrors.reason = 'Motivation requise'
  } else if (reason.value.trim().length < 50) {
    newErrors.reason = 'Min. 50 caractères'
  }
  if (!identityCard.value) {
    newErrors.identityCard = 'Carte d\'identité requise'
  }
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmitRequest = async () => {
  if (hasPendingRequest.value) return
  if (!validateForm()) return

  submitLoading.value = true
  const token = localStorage.getItem('token')
  if (!token || !userId.value) {
    errors.value = { reason: undefined, identityCard: 'Authentification requise' }
    submitLoading.value = false
    return
  }

  const formData = new FormData()
  formData.append('reason', reason.value)
  if (identityCard.value) formData.append('identityCard', identityCard.value)

  try {
    await api.post('/api/affiliate/request', formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data',
      },
    })
    submitSuccess.value = true
    requestStatus.value = 'pending'
    hasPendingRequest.value = true
    setTimeout(() => (submitSuccess.value = false), 3000)
  } catch (error: any) {
    console.error('Erreur soumission:', error)
    errors.value = { reason: error.response?.data?.error || 'Erreur soumission', identityCard: undefined }
  } finally {
    submitLoading.value = false
  }
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  copySuccess.value = true
  clearTimeout(copyTimer)
  copyTimer = setTimeout(() => (copySuccess.value = false), 2000)
}

const shareLink = async () => {
  if (navigator.share && affiliateData.value) {
    try {
      await navigator.share({
        title: 'Rejoignez-moi!',
        text: 'Découvrez avec réduction!',
        url: affiliateData.value.referralLink,
      })
    } catch (error) {
      copyToClipboard(affiliateData.value.referralLink)
    }
  } else if (affiliateData.value) {
    copyToClipboard(affiliateData.value.referralLink)
  }
}

const resetRejected = () => {
  requestStatus.value = null
  reason.value = ''
  identityCard.value = null
  imagePreview.value = null
  hasPendingRequest.value = false
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>

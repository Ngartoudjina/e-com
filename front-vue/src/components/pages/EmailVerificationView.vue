<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-950 to-cyan-900 flex items-center justify-center p-4">
    <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl p-6 max-w-md w-full text-center">
      <div class="mb-6 animate-[wobble_2s_ease-in-out_infinite]">
        <Mail class="h-16 w-16 text-purple-600 mx-auto" />
      </div>
      <h2 class="text-2xl font-bold text-gray-900 mb-4">Vérification de l'Email</h2>
      <p class="text-gray-600 mb-6">
        Un email de vérification a été envoyé à votre adresse email associée. Veuillez vérifier votre boîte de réception (y compris les spams) et cliquer sur le lien pour activer votre compte.
      </p>

      <div v-if="isLoading" class="flex items-center justify-center mb-6">
        <Loader2 class="h-8 w-8 text-purple-600 animate-spin mr-2" />
        <span class="text-gray-700">Vérification en cours...</span>
      </div>

      <button
        v-else
        type="button"
        @click="handleResendVerification"
        class="w-full bg-gradient-to-r from-purple-600 to-cyan-600 text-white font-semibold py-2 rounded-xl shadow-lg hover:from-purple-700 hover:to-cyan-700 transition-all duration-200 hover:scale-105 active:scale-95"
      >
        <RefreshCw class="h-5 w-5 mr-2 inline" />
        Renvoyer l'email
      </button>

      <p class="text-sm text-gray-500 mt-4">
        Si vous ne recevez pas l'email dans quelques minutes, vérifiez vos spams ou cliquez ci-dessus pour le renvoyer.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Mail, Loader2, RefreshCw } from 'lucide-vue-next'
import { API_BASE } from '@/lib/api'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const toastStore = useToastStore()

const historyState = history.state as { tempToken?: string; uid?: string } | null
const tempToken = historyState?.tempToken
const uid = historyState?.uid

const isLoading = ref(true)

let pollInterval: ReturnType<typeof setInterval> | undefined

const checkVerification = async () => {
  isLoading.value = true
  try {
    const response = await fetch(`${API_BASE}/api/verify-email`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${tempToken}`,
      },
    })
    const data = await response.json()
    if (response.ok) {
      toastStore.success(data.message || 'Email vérifié avec succès !')
      localStorage.setItem('token', data.token)
      clearInterval(pollInterval)
      router.push('/connexion')
    } else if (data.error === 'Veuillez vérifier votre email avant de continuer.') {
      isLoading.value = false
    } else {
      throw new Error(data.error || 'Erreur lors de la vérification')
    }
  } catch (error: any) {
    isLoading.value = false
    toastStore.error(error.message || 'Erreur lors de la vérification de l\'email.')
  }
}

if (!tempToken || !uid) {
  toastStore.error('Données de vérification manquantes. Veuillez réessayer.')
  router.push('/connexion')
} else {
  checkVerification()
  pollInterval = setInterval(checkVerification, 5000)
}

const handleResendVerification = async () => {
  if (!uid) {
    toastStore.error('UID manquant. Impossible de renvoyer l\'email.')
    return
  }
  isLoading.value = true
  try {
    const response = await fetch(`${API_BASE}/api/resend-verification`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ uid }),
    })
    const data = await response.json()
    if (response.ok) {
      toastStore.success(data.message || 'Un nouvel email de vérification a été envoyé.')
    } else {
      throw new Error(data.error || 'Erreur lors de l\'envoi de l\'email.')
    }
  } catch (error: any) {
    toastStore.error(error.message || 'Erreur lors de l\'envoi de l\'email de vérification.')
  } finally {
    isLoading.value = false
  }
}

onBeforeUnmount(() => clearInterval(pollInterval))
</script>

<style scoped>
@keyframes wobble {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(10deg); }
  75% { transform: rotate(-10deg); }
}
</style>

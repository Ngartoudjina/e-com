<template>
  <div class="google-signin-container">
    <button
      type="button"
      @click="handleGoogleSignIn"
      :disabled="!isGoogleReady || isLoading"
      class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <div v-if="isLoading" class="flex items-center">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900 mr-2"></div>
        Connexion en cours...
      </div>
      <div v-else class="flex items-center">
        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
          <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
          <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
          <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
          <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
        </svg>
        Se connecter avec Google
      </div>
    </button>

    <div id="google-signin-button" class="mt-2"></div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { API_BASE } from '@/lib/api'

const props = defineProps<{
  onSignIn: (token: string) => void
  onError: (error: string) => void
}>()

declare global {
  interface Window {
    google: any
    gapi: any
  }
}

const isGoogleReady = ref(false)
const isLoading = ref(false)

let scriptElement: HTMLScriptElement | null = null

const handleCredentialResponse = async (response: any) => {
  if (!response.credential) {
    props.onError('Réponse Google invalide')
    return
  }

  isLoading.value = true

  try {
    const backendResponse = await fetch(`${API_BASE}/api/google-login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ idToken: response.credential }),
    })

    if (!backendResponse.ok) {
      const errorData = await backendResponse.json()
      throw new Error(errorData.error || 'Erreur de connexion')
    }

    const data = await backendResponse.json()
    localStorage.setItem('authToken', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    props.onSignIn(data.token)
  } catch (error) {
    props.onError(error instanceof Error ? error.message : 'Erreur de connexion Google')
  } finally {
    isLoading.value = false
  }
}

const initializeGoogle = () => {
  if (window.google && window.google.accounts) {
    isGoogleReady.value = true
    return
  }

  const script = document.createElement('script')
  script.src = 'https://accounts.google.com/gsi/client'
  script.async = true
  script.defer = true

  script.onload = () => {
    if (window.google && window.google.accounts) {
      const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
      if (!clientId) {
        props.onError('Configuration Google manquante')
        return
      }
      window.google.accounts.id.initialize({
        client_id: clientId,
        callback: handleCredentialResponse,
        auto_select: false,
        cancel_on_tap_outside: true,
      })
      isGoogleReady.value = true
    } else {
      props.onError('Erreur de chargement Google')
    }
  }

  script.onerror = () => {
    props.onError('Impossible de charger Google Sign-In')
  }

  document.head.appendChild(script)
  scriptElement = script
}

const handleGoogleSignIn = () => {
  if (!isGoogleReady.value) {
    props.onError('Google Sign-In non disponible')
    return
  }

  if (isLoading.value) return

  try {
    window.google.accounts.id.prompt((notification: any) => {
      if (notification.isNotDisplayed()) {
        const buttonDiv = document.getElementById('google-signin-button')
        if (buttonDiv) {
          window.google.accounts.id.renderButton(buttonDiv, {
            theme: 'outline',
            size: 'large',
            width: '100%',
          })
        }
      }
    })
  } catch (error) {
    props.onError('Erreur lors de l\'ouverture de Google Sign-In')
  }
}

onMounted(initializeGoogle)
onBeforeUnmount(() => {
  if (scriptElement) {
    scriptElement.remove()
  }
})
</script>

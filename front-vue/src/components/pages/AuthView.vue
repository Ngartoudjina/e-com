<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-950 to-cyan-900 relative overflow-hidden flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="absolute inset-0">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,_#6b21a8_0%,_#1e293b_70%)] opacity-50"></div>
      <div class="absolute w-64 h-64 bg-purple-500/20 rounded-full blur-3xl animate-[float-bg_15s_ease-in-out_infinite]" style="top: 10%; left: 10%"></div>
      <div class="absolute w-72 h-72 bg-cyan-400/20 rounded-full blur-3xl animate-[float-bg2_12s_ease-in-out_infinite]" style="bottom: 15%; right: 15%"></div>
      <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-600 via-transparent to-cyan-600" />
      <div class="absolute bottom-0 right-0 w-1 h-full bg-gradient-to-b from-cyan-600 via-transparent to-purple-600" />
    </div>

    <div class="relative z-10 bg-white/95 backdrop-blur-md rounded-2xl lg:rounded-[2.5rem] shadow-2xl overflow-hidden w-full max-w-md sm:max-w-lg md:max-w-2xl mx-auto">
      <div class="flex flex-col md:flex-row min-h-[550px] sm:min-h-[600px] lg:min-h-[650px]">
        <div class="hidden md:flex flex-1 relative bg-gradient-to-br from-purple-900 to-cyan-900 overflow-hidden">
          <div class="absolute inset-0 bg-[url('/imglog.jpeg')] bg-cover bg-center opacity-80 mix-blend-overlay" />
          <div class="relative z-10 flex flex-col items-center justify-center h-full text-white px-6 py-8">
            <div class="bg-white/20 backdrop-blur-md rounded-full p-3 lg:p-4 inline-flex items-center justify-center mb-4 hover:scale-110 transition-transform">
              <Leaf class="w-6 h-6 lg:w-8 lg:h-8 text-purple-300" />
            </div>
            <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight drop-shadow-md">
              E-com
            </h1>
            <p class="text-sm lg:text-base mt-2 text-center font-light">
              Rejoignez une expérience d'achat innovante et durable
            </p>
          </div>
        </div>

        <div class="flex-1 p-4 sm:p-6 lg:p-8 flex flex-col justify-center">
          <Transition name="slide" mode="out-in">
            <div :key="isSignUp ? 'signup' : 'signin'" class="w-full">
              <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-6 text-center">
                {{ isSignUp ? 'Créer un compte' : 'Bienvenue à E-com !' }}
              </h2>

              <form @submit.prevent="isSignUp ? handleSignUp() : handleSignIn()" class="space-y-4 sm:space-y-5">
                <div>
                  <label for="email" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                    <Mail class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                    Email
                  </label>
                  <input
                    v-model="form.email"
                    type="email"
                    id="email"
                    placeholder="Entrez votre email"
                    class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                    required
                  />
                </div>

                <template v-if="isSignUp">
                  <div>
                    <label for="firstName" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                      <User class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                      Prénom
                    </label>
                    <input
                      v-model="form.firstName"
                      type="text"
                      id="firstName"
                      placeholder="Entrez votre prénom"
                      class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                      required
                    />
                  </div>

                  <div>
                    <label for="lastName" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                      <User class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                      Nom
                    </label>
                    <input
                      v-model="form.lastName"
                      type="text"
                      id="lastName"
                      placeholder="Entrez votre nom"
                      class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                      required
                    />
                  </div>

                  <div>
                    <label for="phone" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                      <Phone class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                      Téléphone
                    </label>
                    <input
                      v-model="form.phone"
                      type="tel"
                      id="phone"
                      placeholder="Entrez votre numéro"
                      class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                      required
                    />
                  </div>

                  <div>
                    <label for="address" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                      <Home class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                      Adresse
                    </label>
                    <input
                      v-model="form.address"
                      type="text"
                      id="address"
                      placeholder="Entrez votre adresse"
                      class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                      required
                    />
                  </div>
                </template>

                <div class="relative">
                  <label for="password" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                    <Lock class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                    Mot de passe
                  </label>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    placeholder="Entrez votre mot de passe"
                    class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                    required
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center top-8"
                  >
                    <EyeOff v-if="showPassword" class="h-5 w-5 text-gray-500" />
                    <Eye v-else class="h-5 w-5 text-gray-500" />
                  </button>
                </div>

                <div v-if="isSignUp" class="relative">
                  <label for="confirmPassword" class="block text-sm sm:text-base font-medium text-gray-700 mb-1">
                    <Lock class="inline h-4 w-4 sm:h-5 sm:w-5 mr-1 text-gray-500" />
                    Confirmer
                  </label>
                  <input
                    v-model="form.confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    id="confirmPassword"
                    placeholder="Confirmez votre mot de passe"
                    class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 text-sm sm:text-base"
                    required
                  />
                  <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center top-8"
                  >
                    <EyeOff v-if="showConfirmPassword" class="h-5 w-5 text-gray-500" />
                    <Eye v-else class="h-5 w-5 text-gray-500" />
                  </button>
                </div>

                <button
                  type="submit"
                  :disabled="isLoading"
                  class="w-full bg-gradient-to-r from-purple-600 to-cyan-600 text-white font-semibold py-2 sm:py-3 rounded-xl shadow-lg hover:from-purple-700 hover:to-cyan-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 active:scale-95"
                >
                  <div v-if="isLoading" class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-5 w-5 border-t-2 border-white mr-2"></div>
                    {{ isSignUp ? 'Création...' : 'Connexion...' }}
                  </div>
                  <span v-else>{{ isSignUp ? 'CRÉER UN COMPTE' : 'SE CONNECTER' }}</span>
                </button>

                <GoogleSignIn
                  v-if="!isSignUp"
                  :on-sign-in="handleGoogleToken"
                  :on-error="(msg: string) => toastStore.error(msg)"
                />

                <div v-if="!isSignUp" class="text-center mt-4">
                  <button
                    type="button"
                    @click="handleForgotPassword"
                    class="text-sm sm:text-base text-gray-500 hover:text-purple-600 transition-colors duration-200"
                  >
                    Mot de passe oublié ?
                  </button>
                </div>

                <div class="mt-6 text-center">
                  <p class="text-sm sm:text-base text-gray-600">
                    {{ isSignUp ? 'Déjà un compte ?' : 'Pas encore de compte ?' }}{' '}
                    <button
                      type="button"
                      @click="toggleMode"
                      class="text-purple-600 hover:text-purple-700 font-semibold transition-colors duration-200 hover:scale-110 inline-block"
                    >
                      {{ isSignUp ? 'Se connecter' : 'S\'inscrire' }}
                    </button>
                  </p>
                </div>
              </form>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { User, Lock, Leaf, Mail, Eye, EyeOff, Phone, Home } from 'lucide-vue-next'
import { signInWithEmailAndPassword } from 'firebase/auth'
import { auth } from '@/lib/firebaseConfig'
import { API_BASE } from '@/lib/api'
import { useToastStore } from '@/stores/toast'
import GoogleSignIn from './GoogleSignIn.vue'

interface AuthForm {
  email: string
  password: string
  confirmPassword: string
  firstName: string
  lastName: string
  phone: string
  address: string
}

const router = useRouter()
const toastStore = useToastStore()

const isSignUp = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)

const form = reactive<AuthForm>({
  email: '',
  password: '',
  confirmPassword: '',
  firstName: '',
  lastName: '',
  phone: '',
  address: '',
})

const validateEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)

const handleSignIn = async () => {
  if (!validateEmail(form.email) || form.password.length < 6) {
    toastStore.error(!validateEmail(form.email) ? 'Veuillez entrer un email valide' : 'Le mot de passe doit contenir au moins 6 caractères')
    return
  }
  isLoading.value = true
  try {
    const userCredential = await signInWithEmailAndPassword(auth, form.email, form.password)
    const idToken = await userCredential.user.getIdToken()
    const response = await fetch(`${API_BASE}/api/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ idToken }),
    })
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Erreur de connexion')
    localStorage.setItem('token', data.token)
    toastStore.success('Connexion réussie !')
    router.push('/')
  } catch (error: any) {
    toastStore.error(error.message || 'Erreur lors de la connexion')
  } finally {
    isLoading.value = false
  }
}

const handleSignUp = async () => {
  if (!validateEmail(form.email) || form.password.length < 6 || form.password !== form.confirmPassword || !form.firstName || !form.lastName || !form.phone || !form.address) {
    toastStore.error(!validateEmail(form.email) ? 'Veuillez entrer un email valide' : form.password !== form.confirmPassword ? 'Les mots de passe ne correspondent pas' : 'Tous les champs sont requis')
    return
  }
  isLoading.value = true
  try {
    const response = await fetch(`${API_BASE}/api/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: form.email,
        password: form.password,
        name: `${form.firstName} ${form.lastName}`,
        firstName: form.firstName,
        lastName: form.lastName,
        phone: form.phone,
        address: form.address,
      }),
    })
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Erreur d\'inscription')
    toastStore.success(data.message)
    router.push({ name: 'verify-email', state: { tempToken: data.tempToken, uid: data.uid } })
  } catch (error: any) {
    toastStore.error(error.message || 'Erreur lors de l\'inscription')
  } finally {
    isLoading.value = false
  }
}

const handleGoogleToken = (token: string) => {
  localStorage.setItem('token', token)
  toastStore.success('Connexion Google réussie !')
  router.push('/')
}

const handleForgotPassword = async () => {
  if (!validateEmail(form.email)) {
    toastStore.error('Veuillez entrer un email valide')
    return
  }
  isLoading.value = true
  try {
    const response = await fetch(`${API_BASE}/api/reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: form.email }),
    })
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Erreur lors de l\'envoi de l\'email')
    toastStore.success('Email de réinitialisation envoyé !')
  } catch (error: any) {
    toastStore.error(error.message || 'Erreur lors de l\'envoi de l\'email')
  } finally {
    isLoading.value = false
  }
}

const toggleMode = () => {
  isSignUp.value = !isSignUp.value
  Object.assign(form, { email: '', password: '', confirmPassword: '', firstName: '', lastName: '', phone: '', address: '' })
}
</script>

<style scoped>
@keyframes float-bg {
  0%, 100% { transform: translate(0, 0); }
  33% { transform: translate(50px, -30px); }
  66% { transform: translate(-50px, 30px); }
}
@keyframes float-bg2 {
  0%, 100% { transform: translate(0, 0); }
  33% { transform: translate(-40px, 20px); }
  66% { transform: translate(40px, -20px); }
}
.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
}
.slide-enter-from {
  opacity: 0;
  transform: translateX(300px) scale(0.95);
}
.slide-leave-to {
  opacity: 0;
  transform: translateX(-300px) scale(0.95);
}
</style>

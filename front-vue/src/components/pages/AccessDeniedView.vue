<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="mx-auto flex max-w-[600px] flex-col items-center px-5 py-24 text-center lg:py-32">
      <p class="t-label text-ink-500">Accès refusé</p>
      <h1 class="t-screen-title mt-5 text-ink-900">Cette page est réservée</h1>

      <p class="t-body mt-6 text-ink-700">
        <template v-if="authStore.loading">Vérification de vos droits…</template>
        <template v-else-if="authStore.estConnecte">
          Votre compte n’a pas les droits d’administration nécessaires.
        </template>
        <template v-else>Connectez-vous avec un compte administrateur pour continuer.</template>
      </p>

      <div class="mt-9 flex flex-wrap justify-center gap-3">
        <RouterLink v-if="!authStore.estConnecte" to="/connexion" class="btn btn-primary">Se connecter</RouterLink>
        <RouterLink to="/" class="btn" :class="authStore.estConnecte ? 'btn-primary' : 'btn-secondary'">
          Retour à l’accueil
        </RouterLink>
      </div>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
authStore.init()
</script>

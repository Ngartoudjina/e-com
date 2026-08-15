<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="mx-auto flex max-w-[600px] flex-col items-center px-5 py-24 text-center lg:py-32">
      <p class="t-label text-ink-500">Erreur 404</p>
      <h1 class="t-screen-title mt-5 text-ink-900">Cette page n’existe pas</h1>
      <p class="t-body mt-6 text-ink-700">
        Le lien est peut-être ancien, ou la pièce n’est plus au catalogue.
      </p>

      <div class="mt-9 flex flex-wrap justify-center gap-3">
        <RouterLink to="/catalogue" class="btn btn-primary">Voir le catalogue</RouterLink>
        <button type="button" class="btn btn-secondary" @click="$router.back()">Revenir en arrière</button>
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
</script>

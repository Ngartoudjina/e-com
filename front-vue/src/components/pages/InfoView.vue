<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="container-page">
      <nav aria-label="Fil d'Ariane" class="t-small flex items-center gap-2 pt-8 text-ink-500">
        <RouterLink to="/" class="hover:text-ink-900">Accueil</RouterLink>
        <span aria-hidden="true">/</span>
        <span class="text-ink-900">{{ rubrique }}</span>
      </nav>

      <div class="grid-page section">
        <div class="col-span-4 lg:col-span-7">
          <p class="t-label text-ink-500">{{ rubrique }}</p>
          <h1 class="t-screen-title mt-4">{{ titre }}</h1>

          <p class="t-body-l mt-8 text-ink-700">
            Cette page n’a pas encore de contenu. Elle existe pour que les liens du site
            aboutissent, et sera remplie quand sa maquette sera disponible.
          </p>

          <div class="mt-10 flex flex-wrap gap-3">
            <RouterLink to="/catalogue" class="btn btn-primary">Voir le catalogue</RouterLink>
            <RouterLink to="/faq" class="btn btn-secondary">Questions fréquentes</RouterLink>
          </div>
        </div>
      </div>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
/**
 * Page éditoriale générique.
 *
 * Le pied de page de la maquette annonce quinze rubriques d'aide et de marque
 * pour lesquelles aucun écran n'a été fourni. Plutôt que de laisser ces liens
 * tomber en 404, ils aboutissent ici. Le titre vient de la route, ce qui évite
 * un composant par rubrique.
 */
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'

const route = useRoute()

const titre = computed(() => (route.meta.titre as string) ?? 'Page')
const rubrique = computed(() => (route.meta.rubrique as string) ?? 'Informations')
</script>

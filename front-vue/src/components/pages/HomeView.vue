<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main>
      <!-- ---------------------------------------------- Ouverture -->
      <section class="border-b border-rule bg-surface">
        <div class="mx-auto grid max-w-[1200px] items-center gap-10 px-5 py-16 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
          <div>
            <p v-reveal class="t-label text-ink-500">Collection automne — hiver</p>
            <h1 v-reveal="1" class="t-screen-title mt-5 text-ink-900">
              Des pièces taillées pour durer plus d’une saison.
            </h1>
            <p v-reveal="2" class="t-body mt-6 max-w-md text-ink-700">
              Manteaux, vestes et mailles produits en petites séries dans nos ateliers.
              Livraison offerte dès {{ formatPrix(settingsStore.franco) }}, retours sous
              {{ settingsStore.reglages.returnDays ?? 30 }} jours.
            </p>

            <div v-reveal="3" class="mt-9 flex flex-wrap gap-3">
              <RouterLink to="/catalogue" class="btn btn-primary">Découvrir le catalogue</RouterLink>
              <RouterLink to="/maison/ateliers" class="btn btn-secondary">Nos ateliers</RouterLink>
            </div>
          </div>

          <!-- Le visuel garde le rapport 3/4 des fiches produit. -->
          <div v-reveal="2" class="relative aspect-[3/4] overflow-hidden bg-rule-soft lg:aspect-[4/5]">
            <img
              v-if="vedette?.mediaUrl"
              :src="vedette.mediaUrl"
              :alt="vedette.name"
              class="size-full object-cover"
            />
            <RouterLink
              v-if="vedette"
              :to="`/produit/${vedette.id}`"
              class="absolute inset-x-0 bottom-0 flex items-baseline justify-between gap-4 bg-surface/95 px-5 py-4 transition-colors hover:bg-surface"
            >
              <span class="t-body min-w-0 truncate text-ink-900">{{ vedette.name }}</span>
              <span data-numeric class="t-small shrink-0 text-ink-700">{{ formatPrix(vedette.price) }}</span>
            </RouterLink>
          </div>
        </div>
      </section>

      <!-- ---------------------------------------------- Sélection -->
      <section class="mx-auto max-w-[1200px] px-5 py-16 lg:px-8 lg:py-24">
        <div class="flex flex-wrap items-baseline justify-between gap-4">
          <h2 v-reveal class="t-h1 text-ink-900">Nouveautés</h2>
          <RouterLink to="/catalogue" class="t-small text-ink-700 underline underline-offset-4 hover:text-ink-900">
            Voir tout le catalogue
          </RouterLink>
        </div>

        <div v-if="chargement" class="mt-10 grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4 lg:gap-x-8">
          <div v-for="i in 4" :key="i" class="skeleton aspect-[3/4]" />
        </div>

        <p v-else-if="erreur" class="t-body mt-10 text-ink-500">{{ erreur }}</p>

        <p v-else-if="!selection.length" class="t-body mt-10 text-ink-500">
          Le catalogue est en cours de constitution.
        </p>

        <div v-else class="mt-10 grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4 lg:gap-x-8">
          <ProductCard
            v-for="(produit, index) in selection"
            :key="produit.id"
            v-reveal="index"
            :produit="produit"
            :favori="favoris.has(produit.id)"
            @basculer-favori="basculerFavori"
          />
        </div>
      </section>

      <!-- ---------------------------------------------- Rayons -->
      <section v-if="rayons.length" class="border-y border-rule bg-surface">
        <div class="mx-auto max-w-[1200px] px-5 py-16 lg:px-8 lg:py-24">
          <h2 v-reveal class="t-h1 text-ink-900">Par rayon</h2>

          <ul class="mt-10 grid gap-px bg-rule sm:grid-cols-2 lg:grid-cols-4">
            <li v-for="(rayon, index) in rayons" :key="rayon.nom" v-reveal="index">
              <RouterLink
                :to="{ path: '/catalogue', query: { categorie: rayon.nom } }"
                class="flex items-baseline justify-between gap-4 bg-surface px-5 py-6 transition-colors hover:bg-rule-soft/60"
              >
                <span class="t-body text-ink-900">{{ rayon.nom }}</span>
                <span data-numeric class="t-small text-ink-500">{{ rayon.total }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>
      </section>

      <!-- ---------------------------------------------- Engagements -->
      <section class="mx-auto max-w-[1200px] px-5 py-16 lg:px-8 lg:py-24">
        <ul class="grid gap-10 sm:grid-cols-3">
          <li v-for="(point, index) in engagements" :key="point.titre" v-reveal="index">
            <h3 class="t-h3 text-ink-900">{{ point.titre }}</h3>
            <p class="t-body mt-3 text-ink-700">{{ point.detail }}</p>
            <RouterLink :to="point.lien" class="t-small mt-4 inline-block text-ink-700 underline underline-offset-4 hover:text-ink-900">
              {{ point.action }}
            </RouterLink>
          </li>
        </ul>
      </section>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import ProductCard from '@/components/catalog/ProductCard.vue'
import { getCache } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { useSettingsStore } from '@/stores/settings'
import type { Product } from '@/types'

const settingsStore = useSettingsStore()

const produits = ref<Product[]>([])
const chargement = ref(true)
const erreur = ref<string | null>(null)
const favoris = ref<Set<string>>(new Set())

/** La pièce mise en avant : la première en stock, à défaut la première. */
const vedette = computed(() => produits.value.find((p) => (p.stock ?? 0) > 0) ?? produits.value[0])

const selection = computed(() => produits.value.slice(0, 4))

const rayons = computed(() => {
  const compte = new Map<string, number>()
  for (const produit of produits.value) {
    const nom = produit.category || 'Autres'
    compte.set(nom, (compte.get(nom) ?? 0) + 1)
  }
  return [...compte.entries()]
    .map(([nom, total]) => ({ nom, total }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 4)
})

const engagements = computed(() => [
  {
    titre: 'Livraison offerte',
    detail: `Dès ${formatPrix(settingsStore.franco)} d’achat, sans condition de destination.`,
    lien: '/aide/livraison',
    action: 'Délais et tarifs',
  },
  {
    titre: 'Retours simples',
    detail: `Vous disposez de ${settingsStore.reglages.returnDays ?? 30} jours pour changer d’avis.`,
    lien: '/aide/retours',
    action: 'Conditions de retour',
  },
  {
    titre: 'Fabriqué en petites séries',
    detail: 'Nos pièces sont produites en quantités limitées, dans des ateliers que nous visitons.',
    lien: '/maison/ateliers',
    action: 'Voir nos ateliers',
  },
])

const basculerFavori = (id: string) => {
  const suivant = new Set(favoris.value)
  suivant.has(id) ? suivant.delete(id) : suivant.add(id)
  favoris.value = suivant
}

onMounted(async () => {
  settingsStore.charger()

  try {
    const reponse = await getCache<{ products: Product[] }>('/api/products', { all: true })
    produits.value = reponse.data.products ?? []
  } catch (e) {
    console.error(e)
    erreur.value = 'Le catalogue n’a pas pu être chargé.'
  } finally {
    chargement.value = false
  }
})
</script>

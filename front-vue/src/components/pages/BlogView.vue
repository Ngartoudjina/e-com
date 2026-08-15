<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="mx-auto max-w-[1200px] px-5 py-12 lg:px-8 lg:py-20">
      <h1 class="t-screen-title text-ink-900">Journal</h1>
      <p class="t-body mt-6 max-w-xl text-ink-700">
        Nos notes sur le vêtement, les matières et la manière dont nous travaillons.
      </p>

      <div class="mt-10 flex flex-wrap gap-2">
        <button
          v-for="rubrique in rubriques"
          :key="rubrique"
          type="button"
          class="chip"
          :aria-pressed="rubrique === rubriqueActive"
          @click="rubriqueActive = rubrique"
        >
          {{ rubrique }}
        </button>
      </div>

      <ul class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
        <li v-for="(article, index) in articlesFiltres" :key="article.id" v-reveal="index">
          <article>
            <div class="aspect-[4/3] overflow-hidden bg-rule-soft">
              <img :src="article.image" :alt="article.titre" loading="lazy" class="size-full object-cover" />
            </div>

            <p class="t-label mt-5 text-ink-500">{{ article.rubrique }}</p>
            <h2 class="t-h3 mt-2 text-ink-900">{{ article.titre }}</h2>
            <p class="t-body mt-3 text-ink-700">{{ article.chapo }}</p>
            <time :datetime="article.date" class="t-small mt-4 block text-ink-500">
              {{ formatDateLongue(article.date) }}
            </time>
          </article>
        </li>
      </ul>

      <p v-if="!articlesFiltres.length" class="t-body mt-10 text-ink-500">
        Aucun article dans cette rubrique.
      </p>

      <!-- ---------------------------------------------- Lettre -->
      <section class="mt-16 border border-rule bg-surface p-8">
        <h2 class="t-h3">Recevoir le journal</h2>
        <p class="t-body mt-3 max-w-md text-ink-700">
          Un message quand nous publions, et rien d’autre. Désinscription en un clic.
        </p>

        <form class="mt-6 flex flex-wrap gap-3" @submit.prevent="abonner">
          <label class="min-w-0 flex-1">
            <span class="sr-only">Adresse e-mail</span>
            <input v-model="courriel" type="email" required class="field" placeholder="vous@exemple.fr" />
          </label>
          <button type="submit" class="btn btn-primary shrink-0" :disabled="envoi">
            {{ envoi ? 'Envoi…' : 'S’abonner' }}
          </button>
        </form>
      </section>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import { api } from '@/lib/api'
import { formatDateLongue } from '@/lib/commandes'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

/*
 * Ces articles sont écrits dans le composant : aucune table ni route ne les
 * expose côté serveur. Le jour où un back-office éditorial existera, seule
 * cette constante sera à remplacer.
 */
const articles = [
  {
    id: 1,
    titre: 'Ce que veut dire « petite série »',
    chapo: 'Pourquoi nous produisons peu, et ce que cela change sur la pièce que vous recevez.',
    date: '2025-07-10',
    image: '/blog1.jpeg',
    rubrique: 'Atelier',
  },
  {
    id: 2,
    titre: 'Entretenir un manteau de laine',
    chapo: 'Brossage, aération, repassage : les gestes qui font durer une pièce dix ans.',
    date: '2025-07-05',
    image: '/blog2.jpg',
    rubrique: 'Entretien',
  },
  {
    id: 3,
    titre: 'Choisir sa taille sans se tromper',
    chapo: 'Lire un tableau de mesures, et savoir quand prendre au-dessus.',
    date: '2025-06-28',
    image: '/blog3.jpg',
    rubrique: 'Conseils',
  },
]

const rubriques = ['Tous', ...new Set(articles.map((a) => a.rubrique))]
const rubriqueActive = ref('Tous')

const articlesFiltres = computed(() =>
  rubriqueActive.value === 'Tous' ? articles : articles.filter((a) => a.rubrique === rubriqueActive.value)
)

const courriel = ref('')
const envoi = ref(false)

const abonner = async () => {
  envoi.value = true
  try {
    await api.post('/api/subscribe', { email: courriel.value })
    courriel.value = ''
    toastStore.success('Inscription enregistrée.')
  } catch (e: any) {
    toastStore.error(e?.response?.data?.error ?? 'L’inscription a échoué.')
  } finally {
    envoi.value = false
  }
}
</script>

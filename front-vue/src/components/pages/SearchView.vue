<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <!-- Bandeau de saisie : sur surface blanche, la requête en Cormorant. -->
    <div class="border-b border-rule bg-surface">
      <div class="container-page">
        <div class="flex items-center gap-4 py-6">
          <Search class="size-6 shrink-0 text-ink-900" />
          <input
            ref="champ"
            v-model="requete"
            type="search"
            placeholder="Rechercher une pièce, une matière, une couleur"
            aria-label="Rechercher"
            class="min-w-0 flex-1 border-0 bg-transparent font-display text-[28px] leading-tight text-ink-900 outline-none placeholder:text-ink-300 lg:text-[40px]"
          />
          <p v-if="requete" data-numeric class="t-small hidden shrink-0 text-ink-500 sm:block">
            {{ resultats.length }} résultat{{ resultats.length > 1 ? 's' : '' }}
          </p>
          <button
            v-if="requete"
            type="button"
            class="shrink-0 text-ink-900 transition-colors hover:text-ink-500"
            aria-label="Effacer la recherche"
            @click="effacer"
          >
            <X class="size-5" />
          </button>
        </div>

        <!-- Portées de recherche -->
        <div v-if="requete" class="flex flex-wrap gap-2 pb-6">
          <button
            v-for="portee in portees"
            :key="portee.cle"
            type="button"
            class="chip"
            :aria-pressed="portee.cle === porteeActive"
            @click="porteeActive = portee.cle"
          >
            {{ portee.libelle }} · {{ portee.total }}
          </button>
        </div>
      </div>
    </div>

    <main class="container-page pb-24">
      <!-- Suggestions, tant qu'aucune recherche n'a été lancée -->
      <section v-if="!requete" class="section">
        <div class="grid-page">
          <div class="col-span-4 lg:col-span-4">
            <h2 class="t-label text-ink-500">Catégories</h2>
            <ul class="mt-6 space-y-4">
              <li v-for="categorie in categories" :key="categorie.nom">
                <button
                  type="button"
                  class="flex w-full items-baseline justify-between gap-4 text-left"
                  @click="requete = categorie.nom"
                >
                  <span class="t-body text-ink-900 hover:underline">{{ categorie.nom }}</span>
                  <span data-numeric class="t-small text-ink-500">{{ categorie.total }}</span>
                </button>
              </li>
            </ul>
          </div>

          <div class="col-span-4 mt-12 lg:col-span-4 lg:mt-0">
            <h2 class="t-label text-ink-500">Recherches récentes</h2>
            <ul v-if="recentes.length" class="mt-6 space-y-4">
              <li v-for="terme in recentes" :key="terme">
                <button type="button" class="flex items-center gap-3 text-left" @click="requete = terme">
                  <Clock class="size-4 shrink-0 text-ink-300" />
                  <span class="t-body text-ink-900 hover:underline">{{ terme }}</span>
                </button>
              </li>
            </ul>
            <p v-else class="t-body mt-6 text-ink-500">Aucune recherche pour l’instant.</p>
          </div>

          <div class="col-span-4 mt-12 lg:col-span-4 lg:mt-0">
            <h2 class="t-label text-ink-500">Recherches associées</h2>
            <div class="mt-6 flex flex-wrap gap-2">
              <button
                v-for="terme in suggestions"
                :key="terme"
                type="button"
                class="chip"
                @click="requete = terme"
              >
                {{ terme }}
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Résultats -->
      <section v-else class="section">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <h2 class="t-h2">Produits · {{ resultats.length }}</h2>
          <select v-model="tri" class="field h-11 w-[190px]" aria-label="Trier les résultats">
            <option value="pertinence">Trier : Pertinence</option>
            <option value="prix-asc">Trier : Prix croissant</option>
            <option value="prix-desc">Trier : Prix décroissant</option>
          </select>
        </div>

        <div v-if="chargement" class="mt-10 grid grid-cols-2 gap-x-8 gap-y-12 lg:grid-cols-4">
          <div v-for="i in 8" :key="i">
            <div class="skeleton aspect-[3/4]" />
            <div class="skeleton mt-4 h-4 w-2/3" />
          </div>
        </div>

        <!-- Grille de 4 colonnes, conformément à « 4 × 3 col » du système. -->
        <div v-else-if="resultats.length" class="mt-10 grid grid-cols-2 gap-x-8 gap-y-12 lg:grid-cols-4">
          <article v-for="produit in resultats" :key="produit.id">
            <RouterLink :to="`/produit/${produit.id}`" class="block">
              <div class="aspect-[3/4] overflow-hidden bg-rule-soft">
                <img
                  v-if="produit.mediaUrl"
                  :src="produit.mediaUrl"
                  :alt="produit.name"
                  loading="lazy"
                  class="size-full object-cover transition-transform duration-[320ms] hover:scale-[1.03]"
                />
              </div>
              <div class="mt-4 flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                <!-- Le terme cherché est surligné dans le nom. -->
                <h3 class="t-body text-ink-900" v-html="surligner(produit.name)" />
                <p class="t-price sm:shrink-0">{{ formatPrix(produit.price) }}</p>
              </div>
              <p class="t-small mt-1 text-ink-500">{{ produit.category }}</p>
            </RouterLink>
          </article>
        </div>

        <!-- Aucun résultat -->
        <div v-else class="mt-10 border border-rule bg-surface p-12 text-center">
          <p class="t-h3">Aucune pièce pour « {{ requete }} »</p>
          <p class="t-body mt-2 text-ink-500">
            Vérifiez l’orthographe, ou explorez le catalogue complet.
          </p>
          <RouterLink to="/catalogue" class="btn btn-secondary mt-6">Voir le catalogue</RouterLink>
        </div>
      </section>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Clock, Search, X } from 'lucide-vue-next'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import { api } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import type { Product } from '@/types'

const route = useRoute()
const router = useRouter()

const CLE_RECENTES = 'goldshop:recherches'

const champ = ref<HTMLInputElement | null>(null)
const requete = ref((route.query.q as string) ?? '')
const tri = ref<'pertinence' | 'prix-asc' | 'prix-desc'>('pertinence')
const produits = ref<Product[]>([])
const chargement = ref(true)
const recentes = ref<string[]>([])
const porteeActive = ref('tout')

const suggestions = ['manteau long noir', 'caban laine', 'manteau croisé', 'laine bouillie']

const categories = computed(() => {
  const compte = new Map<string, number>()
  for (const produit of produits.value) {
    const nom = produit.category || 'Autres'
    compte.set(nom, (compte.get(nom) ?? 0) + 1)
  }
  return [...compte.entries()]
    .map(([nom, total]) => ({ nom, total }))
    .sort((a, b) => b.total - a.total)
})

const resultats = computed(() => {
  const terme = requete.value.trim().toLowerCase()
  if (!terme) return []

  // Chaque mot doit se retrouver dans le nom, la catégorie ou la description.
  const mots = terme.split(/\s+/)
  let liste = produits.value.filter((produit) => {
    const champs = `${produit.name} ${produit.category ?? ''} ${produit.description ?? ''}`.toLowerCase()
    return mots.every((mot) => champs.includes(mot))
  })

  if (tri.value === 'prix-asc') liste = [...liste].sort((a, b) => a.price - b.price)
  if (tri.value === 'prix-desc') liste = [...liste].sort((a, b) => b.price - a.price)

  return liste
})

const portees = computed(() => [
  { cle: 'tout', libelle: 'Tout', total: resultats.value.length },
  { cle: 'produits', libelle: 'Produits', total: resultats.value.length },
])

/**
 * Surligne le terme cherché dans le nom.
 * Le texte est échappé avant insertion : un nom de produit ne doit jamais
 * pouvoir injecter de balise.
 */
const surligner = (texte: string) => {
  const echappe = texte.replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] as string
  )

  const terme = requete.value.trim()
  if (!terme) return echappe

  const motifs = terme
    .split(/\s+/)
    .filter((mot) => mot.length > 1)
    .map((mot) => mot.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))

  if (!motifs.length) return echappe

  return echappe.replace(
    new RegExp(`(${motifs.join('|')})`, 'gi'),
    '<mark class="bg-[#E4EAFF] text-ink-900">$1</mark>'
  )
}

const effacer = () => {
  requete.value = ''
  champ.value?.focus()
}

const memoriser = (terme: string) => {
  const propre = terme.trim()
  if (propre.length < 2) return
  recentes.value = [propre, ...recentes.value.filter((t) => t !== propre)].slice(0, 5)
  localStorage.setItem(CLE_RECENTES, JSON.stringify(recentes.value))
}

const charger = async () => {
  chargement.value = true
  try {
    const reponse = await api.get('/api/products', { params: { all: true } })
    produits.value = reponse.data.products ?? []
  } catch (e) {
    console.error(e)
  } finally {
    chargement.value = false
  }
}

// L'URL reflète la recherche : le résultat est partageable et revient au retour arrière.
let minuteur: ReturnType<typeof setTimeout> | undefined
watch(requete, (valeur) => {
  clearTimeout(minuteur)
  minuteur = setTimeout(() => {
    router.replace({ query: valeur ? { q: valeur } : {} })
    memoriser(valeur)
  }, 400)
})

onMounted(() => {
  try {
    recentes.value = JSON.parse(localStorage.getItem(CLE_RECENTES) ?? '[]')
  } catch {
    recentes.value = []
  }
  charger()
  champ.value?.focus()
})
</script>

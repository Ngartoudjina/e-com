<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="container-page pb-24">
      <!-- Fil d'Ariane -->
      <nav aria-label="Fil d'Ariane" class="t-small flex items-center gap-2 pt-8 text-ink-500">
        <RouterLink to="/" class="hover:text-ink-900">Accueil</RouterLink>
        <span aria-hidden="true">/</span>
        <RouterLink to="/catalogue" class="hover:text-ink-900">Catalogue</RouterLink>
        <span aria-hidden="true">/</span>
        <span class="text-ink-900">{{ categorieActive ?? 'Toutes les pièces' }}</span>
      </nav>

      <!--
        Titre de rayon. En desktop, le titre à gauche et la promesse à droite ;
        en mobile, le titre en Cormorant 34 suivi du seul décompte, la promesse
        étant reléguée pour ne pas repousser la grille sous la ligne de flottaison.
      -->
      <header class="grid-page mt-8 items-end lg:mt-12">
        <h1 class="t-screen-title col-span-4 lg:t-h1 lg:col-span-7">
          {{ categorieActive ?? 'Toutes les pièces' }}
        </h1>
        <p data-numeric class="t-body col-span-4 mt-1 text-ink-500 lg:hidden">
          {{ total }} pièce{{ total > 1 ? 's' : '' }}
        </p>
        <p class="t-body-l col-span-5 hidden text-ink-700 lg:block">
          {{ total }} pièce{{ total > 1 ? 's' : '' }}. Matières choisies, coupes conçues pour durer.
          Livrées sous 24 h.
        </p>
      </header>

      <!-- Rangée de filtres mobile : puces horizontales, feuille au clic. -->
      <div class="mt-6 flex items-center gap-2 overflow-x-auto pb-1 lg:hidden">
        <button
          type="button"
          class="chip shrink-0"
          :aria-pressed="filtresActifs.length > 0"
          @click="feuilleOuverte = true"
        >
          <SlidersHorizontal class="size-4" />
          Filtres<template v-if="filtresActifs.length"> · {{ filtresActifs.length }}</template>
        </button>
        <button type="button" class="chip shrink-0" @click="feuilleOuverte = true">Trier</button>
        <button
          v-for="filtre in filtresActifs"
          :key="filtre.cle"
          type="button"
          class="chip shrink-0"
          aria-pressed="true"
          @click="filtre.retirer()"
        >
          {{ filtre.libelle }}
          <X class="size-3.5" />
        </button>
      </div>

      <div class="mt-12 border-t border-rule pt-8 lg:mt-16">
        <div class="grid-page">
          <!-- Colonne de filtres : 3 colonnes sur 12, masquée en mobile -->
          <div class="col-span-4 hidden lg:col-span-3 lg:block">
            <CatalogFilters
              :categories="categories"
              :categorie-active="categorieActive"
              :tailles="tailles"
              :taille-active="tailleActive"
              :couleurs="couleurs"
              :couleur-active="couleurActive"
              :prix-min="prixMin"
              :prix-max="prixMax"
              :en-stock="enStockUniquement"
              :recherche="recherche"
              @update:categorie="(v) => appliquer(() => (categorieActive = v))"
              @update:taille="(v) => (tailleActive = v)"
              @update:couleur="(v) => (couleurActive = v)"
              @update:prix-min="(v) => appliquer(() => (prixMin = v))"
              @update:prix-max="(v) => appliquer(() => (prixMax = v))"
              @update:en-stock="(v) => (enStockUniquement = v)"
              @update:recherche="(v) => appliquer(() => (recherche = v))"
              @tout-effacer="toutEffacer"
            />
          </div>

          <!-- Produits : 9 colonnes sur 12 -->
          <div class="col-span-4 lg:col-span-9">
            <!-- Barre d'outils : desktop uniquement, le mobile a sa rangée de puces. -->
            <div class="hidden flex-wrap items-center justify-between gap-4 lg:flex">
              <div class="flex flex-wrap items-center gap-3">
                <p data-numeric class="t-small text-ink-500">
                  {{ produitsAffiches.length }} sur {{ total }}
                </p>
                <button
                  v-for="filtre in filtresActifs"
                  :key="filtre.cle"
                  type="button"
                  class="chip"
                  aria-pressed="true"
                  @click="filtre.retirer()"
                >
                  {{ filtre.libelle }}
                  <X class="size-3.5" />
                </button>
              </div>

              <div class="flex items-center gap-2">
                <label class="sr-only" for="tri">Trier</label>
                <select id="tri" v-model="tri" class="field h-11 w-[190px] pr-8">
                  <option value="nouveautes">Trier : Nouveautés</option>
                  <option value="prix-asc">Trier : Prix croissant</option>
                  <option value="prix-desc">Trier : Prix décroissant</option>
                  <option value="nom">Trier : A → Z</option>
                </select>

                <div class="flex">
                  <button
                    type="button"
                    class="flex size-11 items-center justify-center border transition-colors duration-[120ms]"
                    :class="vue === 'grille' ? 'border-ink-900 bg-ink-900 text-white' : 'border-rule bg-surface text-ink-900'"
                    aria-label="Vue grille"
                    :aria-pressed="vue === 'grille'"
                    @click="vue = 'grille'"
                  >
                    <LayoutGrid class="size-4" />
                  </button>
                  <button
                    type="button"
                    class="-ml-px flex size-11 items-center justify-center border transition-colors duration-[120ms]"
                    :class="vue === 'liste' ? 'border-ink-900 bg-ink-900 text-white' : 'border-rule bg-surface text-ink-900'"
                    aria-label="Vue liste"
                    :aria-pressed="vue === 'liste'"
                    @click="vue = 'liste'"
                  >
                    <Menu class="size-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Chargement -->
            <div v-if="chargement" class="mt-8 grid grid-cols-2 gap-8 lg:grid-cols-3">
              <div v-for="i in 6" :key="i">
                <div class="skeleton aspect-[3/4]" />
                <div class="skeleton mt-4 h-4 w-2/3" />
                <div class="skeleton mt-2 h-3 w-1/3" />
              </div>
            </div>

            <!-- Erreur -->
            <div v-else-if="erreur" class="mt-16 border border-rule bg-surface p-12 text-center">
              <p class="t-body text-ink-900">{{ erreur }}</p>
              <button type="button" class="btn btn-secondary mt-6" @click="charger">Réessayer</button>
            </div>

            <!-- Aucun résultat -->
            <div v-else-if="!produitsAffiches.length" class="mt-16 border border-rule bg-surface p-12 text-center">
              <p class="t-h3 text-ink-900">Aucune pièce ne correspond</p>
              <p class="t-body mt-2 text-ink-500">Élargissez les critères pour retrouver des résultats.</p>
              <button type="button" class="btn btn-secondary mt-6" @click="toutEffacer">Tout effacer</button>
            </div>

            <!-- Grille -->
            <div v-else-if="vue === 'grille'" class="mt-8 grid grid-cols-2 gap-x-8 gap-y-12 lg:grid-cols-3">
              <ProductCard
                v-for="(produit, index) in produitsAffiches"
                :key="produit.id"
                v-reveal="Math.min(index, 5) * 60"
                :produit="produit"
                :favori="favoris.has(produit.id)"
                :coloris="colorisDe(produit)"
                @basculer-favori="basculerFavori"
                @ajout-rapide="ouvrirApercu"
                @apercu="ouvrirApercu"
              />
            </div>

            <!-- Liste -->
            <div v-else class="mt-8 space-y-4">
              <ProductListItem
                v-for="produit in produitsAffiches"
                :key="produit.id"
                :produit="produit"
                :favori="favoris.has(produit.id)"
                :coloris="colorisDe(produit)"
                @basculer-favori="basculerFavori"
                @apercu="ouvrirApercu"
                @ajouter="ajouterAuPanier"
              />
            </div>

            <!-- Pagination -->
            <div v-if="!chargement && produitsAffiches.length" class="mt-16 flex flex-col items-center">
              <div class="h-px w-full max-w-[360px] bg-rule">
                <div class="h-px bg-ink-900" :style="{ width: `${progression}%` }" />
              </div>
              <p data-numeric class="t-small mt-5 text-ink-500">
                {{ produitsAffiches.length }} pièces sur {{ total }}
              </p>
              <button
                v-if="produitsAffiches.length < total"
                type="button"
                class="btn btn-secondary mt-6"
                @click="pageSuivante"
              >
                Voir {{ Math.min(parPage, total - produitsAffiches.length) }} pièces de plus
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <SiteFooter />
    <BottomTabBar />

    <QuickViewModal
      :produit="produitApercu"
      :coloris="produitApercu ? colorisDe(produitApercu) : []"
      @fermer="produitApercu = null"
      @ajouter="ajouterAuPanier"
    />

    <!-- Les mêmes contrôles que la colonne desktop, dans une feuille. -->
    <FilterSheet
      :ouvert="feuilleOuverte"
      :resultats="produitsFiltres.length"
      @fermer="feuilleOuverte = false"
      @reinitialiser="toutEffacer"
    >
      <CatalogFilters
        :categories="categories"
        :categorie-active="categorieActive"
        :tailles="tailles"
        :taille-active="tailleActive"
        :couleurs="couleurs"
        :couleur-active="couleurActive"
        :prix-min="prixMin"
        :prix-max="prixMax"
        :en-stock="enStockUniquement"
        :recherche="recherche"
        @update:categorie="(v) => appliquer(() => (categorieActive = v))"
        @update:taille="(v) => (tailleActive = v)"
        @update:couleur="(v) => (couleurActive = v)"
        @update:prix-min="(v) => appliquer(() => (prixMin = v))"
        @update:prix-max="(v) => appliquer(() => (prixMax = v))"
        @update:en-stock="(v) => (enStockUniquement = v)"
        @update:recherche="(v) => appliquer(() => (recherche = v))"
        @tout-effacer="toutEffacer"
      />
    </FilterSheet>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { LayoutGrid, Menu, SlidersHorizontal, X } from 'lucide-vue-next'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import CatalogFilters from '@/components/catalog/CatalogFilters.vue'
import FilterSheet from '@/components/catalog/FilterSheet.vue'
import ProductCard from '@/components/catalog/ProductCard.vue'
import ProductListItem from '@/components/catalog/ProductListItem.vue'
import QuickViewModal from '@/components/catalog/QuickViewModal.vue'
import { api } from '@/lib/api'
import { useCartStore } from '@/stores/cart'
import type { Product, ProductWithDetails } from '@/types'

const route = useRoute()
const cartStore = useCartStore()

const parPage = 12

const produits = ref<Product[]>([])
const total = ref(0)
const chargement = ref(true)
const erreur = ref<string | null>(null)
const page = ref(1)

const vue = ref<'grille' | 'liste'>('grille')
const tri = ref<'nouveautes' | 'prix-asc' | 'prix-desc' | 'nom'>('nouveautes')
const recherche = ref('')
const categorieActive = ref<string | null>(null)
const tailleActive = ref<string | null>(null)
const couleurActive = ref<string | null>(null)
const prixMin = ref(0)
const prixMax = ref(1000)
const enStockUniquement = ref(false)
const favoris = ref<Set<string>>(new Set())
const produitApercu = ref<Product | null>(null)
const feuilleOuverte = ref(false)

/** Le catalogue ne connaît pas encore les variantes : palette de référence. */
const couleurs = [
  { nom: 'Noir', valeur: '#101418' },
  { nom: 'Sable', valeur: '#C9B08A' },
  { nom: 'Forêt', valeur: '#2F5E46' },
  { nom: 'Brique', valeur: '#A32C22' },
  { nom: 'Marine', valeur: '#2C3E5B' },
  { nom: 'Écru', valeur: '#EFE9DE' },
]

const tailles = [
  { valeur: 'XS' },
  { valeur: 'S' },
  { valeur: 'M' },
  { valeur: 'L' },
  { valeur: 'XL' },
  { valeur: 'XXL', indisponible: true },
]

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

const produitsFiltres = computed(() => {
  let liste = [...produits.value]

  if (categorieActive.value) {
    liste = liste.filter((p) => (p.category || 'Autres') === categorieActive.value)
  }
  if (recherche.value.trim()) {
    const terme = recherche.value.trim().toLowerCase()
    liste = liste.filter((p) => p.name.toLowerCase().includes(terme))
  }
  if (enStockUniquement.value) {
    liste = liste.filter((p) => (p.stock ?? 0) > 0)
  }
  liste = liste.filter((p) => p.price >= prixMin.value && p.price <= prixMax.value)

  switch (tri.value) {
    case 'prix-asc':
      liste.sort((a, b) => a.price - b.price)
      break
    case 'prix-desc':
      liste.sort((a, b) => b.price - a.price)
      break
    case 'nom':
      liste.sort((a, b) => a.name.localeCompare(b.name, 'fr'))
      break
  }

  return liste
})

const produitsAffiches = computed(() => produitsFiltres.value.slice(0, page.value * parPage))

const progression = computed(() =>
  total.value ? Math.min(100, (produitsAffiches.value.length / total.value) * 100) : 0
)

const filtresActifs = computed(() => {
  const actifs: { cle: string; libelle: string; retirer: () => void }[] = []
  if (tailleActive.value) {
    actifs.push({ cle: 'taille', libelle: `Taille ${tailleActive.value}`, retirer: () => (tailleActive.value = null) })
  }
  if (couleurActive.value) {
    actifs.push({ cle: 'couleur', libelle: couleurActive.value, retirer: () => (couleurActive.value = null) })
  }
  if (categorieActive.value) {
    actifs.push({ cle: 'categorie', libelle: categorieActive.value, retirer: () => (categorieActive.value = null) })
  }
  if (enStockUniquement.value) {
    actifs.push({ cle: 'stock', libelle: 'En stock', retirer: () => (enStockUniquement.value = false) })
  }
  return actifs
})

/** Couleurs de démonstration tant que l'API n'expose pas les variantes. */
const colorisDe = (produit: Product) => {
  const nombre = (produit.id.charCodeAt(0) % 3) + 1
  return couleurs.slice(0, nombre).map((c) => c.valeur)
}

const charger = async () => {
  chargement.value = true
  erreur.value = null
  try {
    const reponse = await api.get('/api/products', { params: { all: true } })
    produits.value = reponse.data.products ?? []
    total.value = reponse.data.pagination?.totalItems ?? produits.value.length
  } catch (e) {
    console.error(e)
    erreur.value = 'Le catalogue n’a pas pu être chargé.'
  } finally {
    chargement.value = false
  }
}

/** Toute modification de critère ramène à la première page. */
const appliquer = (mutation: () => void) => {
  mutation()
  page.value = 1
}

const pageSuivante = () => (page.value += 1)

const toutEffacer = () => {
  categorieActive.value = null
  tailleActive.value = null
  couleurActive.value = null
  recherche.value = ''
  enStockUniquement.value = false
  prixMin.value = 0
  prixMax.value = 1000
  page.value = 1
}

const basculerFavori = (id: string) => {
  const suivant = new Set(favoris.value)
  suivant.has(id) ? suivant.delete(id) : suivant.add(id)
  favoris.value = suivant
}

const ouvrirApercu = (produit: Product) => (produitApercu.value = produit)

const ajouterAuPanier = (produit: ProductWithDetails) => {
  cartStore.addToCart(produit)
  produitApercu.value = null
}

watch(() => route.query.categorie, (valeur) => {
  categorieActive.value = typeof valeur === 'string' ? valeur : null
}, { immediate: true })

onMounted(charger)
</script>

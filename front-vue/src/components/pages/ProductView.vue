<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main v-if="produit" class="container-page">
      <!-- Fil d'Ariane, et navigation entre pièces à droite. -->
      <div class="flex items-center justify-between gap-4 pt-8">
        <nav aria-label="Fil d'Ariane" class="t-small flex items-center gap-2 text-ink-500">
          <RouterLink to="/" class="hover:text-ink-900">Accueil</RouterLink>
          <span aria-hidden="true">/</span>
          <RouterLink to="/catalogue" class="hover:text-ink-900">Catalogue</RouterLink>
          <span aria-hidden="true">/</span>
          <span class="text-ink-900">{{ produit.name }}</span>
        </nav>
        <div class="t-small hidden items-center gap-6 text-ink-500 lg:flex">
          <button type="button" class="hover:text-ink-900">‹ Précédent</button>
          <button type="button" class="hover:text-ink-900">Suivant ›</button>
        </div>
      </div>

      <div class="grid-page mt-8">
        <!-- Galerie : 7 colonnes -->
        <div class="col-span-4 lg:col-span-7">
          <ProductGallery
            :nom="produit.name"
            :visuels="visuels"
            :badge="badge"
          />
        </div>

        <!-- Bloc d'achat : 5 colonnes -->
        <div class="col-span-4 mt-8 lg:col-span-5 lg:mt-0">
          <p class="t-label text-ink-500">
            {{ produit.category || 'Collection' }}
          </p>

          <h1 class="t-screen-title mt-4 lg:t-h1">{{ produit.name }}</h1>

          <!-- Note et avis -->
          <div class="mt-4 flex items-center gap-3">
            <span class="flex items-center gap-0.5" :aria-label="`Note ${note} sur 5`">
              <Star
                v-for="i in 5"
                :key="i"
                class="size-4"
                :class="i <= Math.round(note) ? 'fill-ink-900 text-ink-900' : 'text-ink-300'"
              />
            </span>
            <span data-numeric class="t-small text-ink-900">{{ note.toFixed(1).replace('.', ',') }}</span>
            <button type="button" class="t-small text-action hover:underline">{{ nombreAvis }} avis</button>
          </div>

          <!-- Prix -->
          <div class="mt-8 flex flex-wrap items-center gap-3">
            <p data-numeric class="font-sans text-[36px] leading-none text-ink-900">
              {{ formatPrix(produit.price) }}
            </p>
            <template v-if="produit.originalPrice && produit.originalPrice > produit.price">
              <p data-numeric class="t-price text-ink-300 line-through">
                {{ formatPrix(produit.originalPrice) }}
              </p>
              <ProductBadge variante="remise">−{{ remise }} %</ProductBadge>
            </template>
          </div>
          <p class="t-small mt-3 text-ink-500">
            ou {{ formatFractionne(produit.price) }} · TVA incluse
          </p>

          <div class="mt-8 border-t border-rule pt-8">
            <!-- Coloris -->
            <div v-if="coloris.length">
              <div class="flex items-baseline justify-between">
                <p class="t-label text-ink-500">Coloris</p>
                <p class="t-small text-ink-900">{{ coloris[colorisChoisi].nom }}</p>
              </div>
              <div class="mt-4 flex items-center gap-3">
                <button
                  v-for="(couleur, index) in coloris"
                  :key="couleur.nom"
                  type="button"
                  class="size-9 rounded-full transition-transform duration-[200ms]"
                  :class="index === colorisChoisi
                    ? 'ring-1 ring-ink-900 ring-offset-2 ring-offset-paper'
                    : 'ring-1 ring-rule hover:ring-ink-300'"
                  :style="{ background: couleur.valeur }"
                  :aria-label="couleur.nom"
                  :aria-pressed="index === colorisChoisi"
                  @click="colorisChoisi = index"
                />
              </div>
            </div>

            <!-- Taille -->
            <div class="mt-8">
              <div class="flex items-baseline justify-between gap-4">
                <p class="t-label text-ink-500">Taille</p>
                <button type="button" class="t-small text-action hover:underline">
                  Guide des tailles &amp; mensurations
                </button>
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <button
                  v-for="taille in tailles"
                  :key="taille.valeur"
                  type="button"
                  :disabled="taille.indisponible"
                  class="flex h-12 w-[68px] items-center justify-center border text-[15px] transition-colors duration-[200ms]"
                  :class="classeTaille(taille)"
                  :aria-pressed="taille.valeur === tailleChoisie"
                  @click="choisirTaille(taille.valeur)"
                >
                  {{ taille.valeur }}
                </button>
              </div>
              <p v-if="erreurTaille" class="t-small mt-3 text-error">
                Choisissez une taille pour continuer.
              </p>
              <p v-else-if="statut" class="t-small mt-3 flex items-center gap-2" :class="statut.classe">
                <span aria-hidden="true">●</span>{{ statut.libelle }}
              </p>
            </div>

            <!-- Action : quantité, ajout, favori -->
            <div class="mt-8 flex flex-wrap gap-3">
              <div class="flex h-13 items-center border border-rule bg-surface" style="height: var(--size-control-lg)">
                <button type="button" class="flex size-12 items-center justify-center text-ink-900" aria-label="Diminuer" @click="quantite = Math.max(1, quantite - 1)">
                  <Minus class="size-4" />
                </button>
                <span data-numeric class="w-8 text-center text-[15px]">{{ quantite }}</span>
                <button type="button" class="flex size-12 items-center justify-center text-ink-900" aria-label="Augmenter" @click="quantite += 1">
                  <Plus class="size-4" />
                </button>
              </div>

              <button type="button" class="btn btn-lg btn-primary flex-1" :disabled="epuise" @click="ajouter">
                Ajouter au panier
              </button>

              <button
                type="button"
                class="btn btn-icon"
                style="height: var(--size-control-lg); width: var(--size-control-lg)"
                :aria-pressed="favori"
                aria-label="Ajouter aux favoris"
                @click="favori = !favori"
              >
                <Heart class="size-4" :class="favori ? 'fill-current' : ''" />
              </button>
            </div>

            <!-- L'engagement financier reste secondaire ici : la maquette met
                 « Acheter maintenant » en contour, pas en bleu. -->
            <button type="button" class="btn btn-lg btn-secondary mt-3 w-full" :disabled="epuise" @click="acheter">
              Acheter maintenant
            </button>
          </div>

          <!-- Réassurance -->
          <ul class="mt-8 border border-rule bg-surface">
            <li
              v-for="(garantie, index) in garanties"
              :key="garantie.titre"
              class="flex gap-4 p-5"
              :class="index > 0 ? 'border-t border-rule' : ''"
            >
              <component :is="garantie.icone" class="mt-0.5 size-5 shrink-0 text-ink-900" />
              <div>
                <p class="t-body text-ink-900">{{ garantie.titre }}</p>
                <p class="t-small mt-1 text-ink-500">{{ garantie.detail }}</p>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Section éditoriale et caractéristiques -->
      <section class="section mt-8 border-t border-rule">
        <div class="grid-page">
          <div class="col-span-4 lg:col-span-6">
            <div class="aspect-[4/3] bg-rule-soft">
              <img v-if="visuels[1]" :src="visuels[1]" :alt="produit.name" class="size-full object-cover" loading="lazy" />
            </div>
          </div>

          <div class="col-span-4 mt-8 lg:col-span-5 lg:col-start-8 lg:mt-0">
            <p class="t-label text-ink-500">La pièce</p>
            <h2 class="t-h2 mt-4">{{ produit.name }}</h2>
            <p class="t-body-l mt-6 text-ink-700">
              {{ produit.description || 'Une pièce choisie pour sa matière et sa coupe, pensée pour durer plusieurs saisons.' }}
            </p>

            <!-- Caractéristiques : libellé à gauche, valeur à droite. -->
            <dl class="mt-10 border-t border-rule">
              <div
                v-for="caracteristique in caracteristiques"
                :key="caracteristique.libelle"
                class="grid grid-cols-2 gap-4 border-b border-rule py-4"
              >
                <dt class="t-body text-ink-500">{{ caracteristique.libelle }}</dt>
                <dd class="t-body text-ink-900">{{ caracteristique.valeur }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </section>
    </main>

    <!-- Chargement / absence -->
    <div v-else-if="chargement" class="container-page py-24">
      <div class="grid-page">
        <div class="col-span-4 lg:col-span-7"><div class="skeleton aspect-[4/5]" /></div>
        <div class="col-span-4 lg:col-span-5">
          <div class="skeleton h-4 w-24" />
          <div class="skeleton mt-4 h-10 w-3/4" />
          <div class="skeleton mt-8 h-8 w-40" />
        </div>
      </div>
    </div>
    <div v-else class="container-page py-24 text-center">
      <p class="t-h2">Pièce introuvable</p>
      <RouterLink to="/catalogue" class="btn btn-secondary mt-8">Retour au catalogue</RouterLink>
    </div>

    <!--
      Barre d'action ancrée : « sur les écrans qui vendent, la barre d'action
      reste ancrée en bas ». Mobile uniquement, le bloc d'achat suffit au-delà.
    -->
    <div v-if="produit" class="action-bar lg:hidden">
      <div class="min-w-0 flex-1">
        <p data-numeric class="t-price truncate text-ink-900">{{ formatPrix(produit.price) }}</p>
        <p class="t-small text-ink-500">Livraison offerte</p>
      </div>
      <button type="button" class="btn btn-lg btn-primary flex-1" :disabled="epuise" @click="ajouter">
        Ajouter au panier
      </button>
    </div>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, markRaw, onMounted, ref, watch, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Heart, Minus, Plus, RotateCcw, ShieldCheck, Star, Truck } from 'lucide-vue-next'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import ProductGallery from '@/components/product/ProductGallery.vue'
import ProductBadge, { type VarianteBadge } from '@/components/catalog/ProductBadge.vue'
import { formatPrix, formatFractionne } from '@/lib/format'
import { api } from '@/lib/api'
import { useCartStore } from '@/stores/cart'
import type { Product } from '@/types'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const produit = ref<Product | null>(null)
const chargement = ref(true)
const quantite = ref(1)
const colorisChoisi = ref(0)
const tailleChoisie = ref<string | null>(null)
const erreurTaille = ref(false)
const favori = ref(false)

const coloris = [
  { nom: 'Anthracite', valeur: '#101418' },
  { nom: 'Sable', valeur: '#C9B08A' },
  { nom: 'Marine', valeur: '#2C3E5B' },
]

const tailles = [
  { valeur: 'XS' },
  { valeur: 'S' },
  { valeur: 'M' },
  { valeur: 'L' },
  { valeur: 'XL', indisponible: true },
]

const garanties: { icone: Component; titre: string; detail: string }[] = [
  {
    icone: markRaw(Truck),
    titre: 'Livraison offerte, expédié sous 24 h',
    detail: 'Réception estimée sous 2 à 4 jours ouvrés',
  },
  {
    icone: markRaw(RotateCcw),
    titre: 'Retours gratuits sous 30 jours',
    detail: 'Étiquette prépayée incluse dans le colis',
  },
  {
    icone: markRaw(ShieldCheck),
    titre: 'Garantie 5 ans sur les coutures',
    detail: 'Réparation ou remplacement en atelier',
  },
]

const note = computed(() => produit.value?.rating || 4.8)
const nombreAvis = computed(() => 128)

const visuels = computed(() => (produit.value?.mediaUrl ? [produit.value.mediaUrl] : []))

const epuise = computed(() => (produit.value?.stock ?? 0) <= 0)

const remise = computed(() => {
  const initial = produit.value?.originalPrice
  if (!produit.value || !initial || initial <= produit.value.price) return null
  return Math.round((1 - produit.value.price / initial) * 100)
})

const badge = computed<{ libelle: string; variante: VarianteBadge } | null>(() => {
  if (remise.value) return { libelle: `−${remise.value} %`, variante: 'remise' }
  if (produit.value?.isNew) return { libelle: 'Nouveau', variante: 'nouveau' }
  return null
})

const statut = computed(() => {
  const stock = produit.value?.stock ?? 0
  if (stock <= 0) return { libelle: 'Épuisé', classe: 'text-error' }
  if (stock <= 3) {
    const suffixe = tailleChoisie.value ? ` en taille ${tailleChoisie.value}` : ''
    return { libelle: `Plus que ${stock} pièces${suffixe}`, classe: 'text-warning' }
  }
  return { libelle: 'En stock · expédié sous 24 h', classe: 'text-success' }
})

const caracteristiques = computed(() => [
  { libelle: 'Catégorie', valeur: produit.value?.category || '—' },
  { libelle: 'Référence', valeur: produit.value?.id.slice(0, 8).toUpperCase() || '—' },
  { libelle: 'Disponibilité', valeur: epuise.value ? 'Épuisé' : `${produit.value?.stock} en stock` },
  { libelle: 'Entretien', valeur: 'Nettoyage à sec, brosse douce' },
  { libelle: 'Origine', valeur: 'Tissé en Italie, assemblé au Portugal' },
])

const classeTaille = (taille: { valeur: string; indisponible?: boolean }) => {
  if (taille.indisponible) return 'cursor-not-allowed border-rule-soft text-ink-300 line-through'
  return taille.valeur === tailleChoisie.value
    ? 'border-ink-900 bg-ink-900 text-white'
    : 'border-rule bg-surface text-ink-900 hover:border-ink-900'
}

const choisirTaille = (valeur: string) => {
  tailleChoisie.value = valeur
  erreurTaille.value = false
}

/** La taille est obligatoire : on la réclame plutôt que d'en supposer une. */
const valider = () => {
  if (!tailleChoisie.value) {
    erreurTaille.value = true
    return false
  }
  return true
}

const ajouter = () => {
  if (!produit.value || !valider()) return
  cartStore.addToCart({
    ...produit.value,
    quantity: quantite.value,
    selectedColor: coloris[colorisChoisi.value].nom,
    selectedSize: tailleChoisie.value!,
  })
}

const acheter = () => {
  if (!produit.value || !valider()) return
  ajouter()
  router.push('/panier')
}

const charger = async () => {
  chargement.value = true
  try {
    const reponse = await api.get(`/api/products/${route.params.id}`)
    produit.value = reponse.data
  } catch (e) {
    console.error(e)
    produit.value = null
  } finally {
    chargement.value = false
  }
}

watch(() => route.params.id, charger)
onMounted(charger)
</script>

<template>
  <div>
    <!-- Indicateurs clés -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="indicateur in indicateurs" :key="indicateur.libelle" class="border border-rule bg-surface p-6">
        <div class="flex items-baseline justify-between gap-4">
          <h2 class="t-label text-ink-500">{{ indicateur.libelle }}</h2>
          <p v-if="indicateur.variation !== null" class="t-small" :class="indicateur.variation >= 0 ? 'text-success' : 'text-error'">
            {{ indicateur.variation >= 0 ? '+' : '' }}{{ indicateur.variation }} {{ indicateur.unite }}
          </p>
        </div>
        <p data-numeric class="mt-5 font-display text-[34px] leading-none">{{ indicateur.affichage }}</p>

        <!-- Une seule couleur d'accent dans les graphiques : le bleu d'action. -->
        <div class="mt-6 flex h-8 items-end gap-1.5">
          <span
            v-for="(hauteur, index) in indicateur.serie"
            :key="index"
            class="flex-1"
            :class="index === indicateur.serie.length - 1 ? 'bg-action' : 'bg-action/15'"
            :style="{ height: `${Math.max(12, hauteur)}%` }"
            data-barre
          />
        </div>
      </article>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[1.6fr_1fr]">
      <!-- Courbe -->
      <section class="border border-rule bg-surface p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="t-h3">Répartition du catalogue</h2>
            <p class="t-small mt-1 text-ink-500">Nombre de pièces par catégorie</p>
          </div>
        </div>

        <div v-if="chargement" class="mt-8 space-y-3">
          <div v-for="i in 5" :key="i" class="skeleton h-8" />
        </div>

        <div v-else-if="!categories.length" class="mt-8 text-center">
          <p class="t-body text-ink-500">Aucun produit au catalogue.</p>
        </div>

        <!--
          Barres horizontales plutôt qu'une courbe : les données réellement
          disponibles sont une répartition par catégorie, pas une série
          temporelle. Le backend n'expose aucun historique de chiffre d'affaires.
        -->
        <ul v-else class="mt-8 space-y-5">
          <li v-for="categorie in categories" :key="categorie.nom">
            <div class="flex items-baseline justify-between gap-4">
              <span class="t-body text-ink-900">{{ categorie.nom }}</span>
              <span data-numeric class="t-small text-ink-500">{{ categorie.total }}</span>
            </div>
            <div class="mt-2 h-2 bg-rule-soft">
              <div class="h-2 bg-action transition-all duration-[320ms]" :style="{ width: `${categorie.part}%` }" />
            </div>
          </li>
        </ul>
      </section>

      <!-- Entonnoir de stock -->
      <section class="border border-rule bg-surface p-6 lg:p-8">
        <h2 class="t-h3">État du stock</h2>
        <p class="t-small mt-1 text-ink-500">Sur l’ensemble du catalogue</p>

        <ul class="mt-8 space-y-6">
          <li v-for="palier in paliersStock" :key="palier.libelle">
            <div class="flex items-baseline justify-between gap-4">
              <span class="t-body text-ink-900">{{ palier.libelle }}</span>
              <span data-numeric class="t-body text-ink-900">{{ palier.valeur }}</span>
            </div>
            <div class="mt-2 h-2 bg-rule-soft">
              <div class="h-2 bg-ink-900" :style="{ width: `${palier.part}%` }" />
            </div>
          </li>
        </ul>

        <p v-if="rupture > 0" class="t-small mt-8 bg-[#F5E6C8] p-4 text-warning">
          {{ rupture }} pièce{{ rupture > 1 ? 's' : '' }} en rupture. Elles restent visibles au catalogue
          avec une alerte de réassort.
        </p>
      </section>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
      <!-- Meilleures ventes -->
      <section class="border border-rule bg-surface">
        <div class="flex items-baseline justify-between gap-4 border-b border-rule p-6">
          <h2 class="t-h3">Meilleures ventes</h2>
          <RouterLink to="/admin/produits" class="t-small text-action hover:underline">Tout voir</RouterLink>
        </div>

        <div v-if="chargement" class="space-y-3 p-6">
          <div v-for="i in 4" :key="i" class="skeleton h-11" />
        </div>

        <p v-else-if="!meilleuresVentes.length" class="t-body p-6 text-ink-500">
          Aucune vente enregistrée.
        </p>

        <ul v-else>
          <li
            v-for="(produit, index) in meilleuresVentes"
            :key="produit.id"
            class="flex items-center gap-4 px-6 py-4"
            :class="index > 0 ? 'border-t border-rule' : ''"
          >
            <span class="size-11 shrink-0 overflow-hidden bg-rule-soft">
              <img v-if="produit.mediaUrl" :src="produit.mediaUrl" :alt="produit.name" class="size-full object-cover" />
            </span>
            <span class="min-w-0 flex-1">
              <span class="block truncate text-[13px] text-ink-900">{{ produit.name }}</span>
              <span data-numeric class="block text-[11px] text-ink-500">
                {{ produit.soldCount ?? 0 }} vendus · stock {{ produit.stock ?? 0 }}
              </span>
            </span>
            <span data-numeric class="shrink-0 text-[13px]">{{ formatPrix(produit.price) }}</span>
          </li>
        </ul>
      </section>

      <!-- À traiter -->
      <section class="border border-rule bg-surface">
        <div class="flex items-baseline justify-between gap-4 border-b border-rule p-6">
          <h2 class="t-h3">À traiter</h2>
          <span data-numeric class="t-small text-ink-500">{{ aTraiter.length }} point{{ aTraiter.length > 1 ? 's' : '' }}</span>
        </div>

        <p v-if="!aTraiter.length" class="t-body p-6 text-ink-500">Rien à signaler.</p>

        <ul v-else>
          <li
            v-for="(point, index) in aTraiter"
            :key="point.libelle"
            class="flex items-center gap-4 px-6 py-4"
            :class="index > 0 ? 'border-t border-rule' : ''"
          >
            <span class="size-2 shrink-0 rounded-full" :class="point.couleur" />
            <span class="min-w-0 flex-1">
              <span class="block text-[13px] text-ink-900">{{ point.libelle }}</span>
              <span class="block text-[11px] text-ink-500">{{ point.detail }}</span>
            </span>
            <RouterLink :to="point.to" class="btn btn-sm btn-secondary shrink-0">{{ point.action }}</RouterLink>
          </li>
        </ul>
      </section>
    </div>

    <p class="t-small mt-8 text-ink-500">
      Le chiffre d’affaires, les commandes et le taux de conversion ne sont pas affichés :
      aucune table de commandes n’existe encore côté serveur. Les indicateurs ci-dessus
      proviennent tous de données réelles.
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { getCache } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { COURBE, DUREE, gsap, mouvementReduit } from '@/lib/motion'
import type { Product } from '@/types'

interface ProduitAdmin extends Product {
  soldCount?: number
}

const produits = ref<ProduitAdmin[]>([])
const chargement = ref(true)

const total = computed(() => produits.value.length)
const enStock = computed(() => produits.value.filter((p) => (p.stock ?? 0) > 3).length)
const stockFaible = computed(() => produits.value.filter((p) => (p.stock ?? 0) > 0 && (p.stock ?? 0) <= 3).length)
const rupture = computed(() => produits.value.filter((p) => (p.stock ?? 0) <= 0).length)

const valeurStock = computed(() =>
  produits.value.reduce((somme, p) => somme + p.price * (p.stock ?? 0), 0)
)

/** Série courte, purement indicative : aucun historique n'est disponible. */
const serieNeutre = [30, 45, 40, 60, 55, 70, 85]

/**
 * Chaque indicateur se compte jusqu'à sa valeur : le chiffre est
 * l'information principale de la carte, l'animation le désigne.
 * `compte` est mis à jour par GSAP, `affichage` le met en forme.
 */
const comptes = reactive({ total: 0, valeur: 0, faible: 0, rupture: 0 })

const indicateurs = computed(() => [
  {
    libelle: 'Pièces au catalogue',
    affichage: Math.round(comptes.total).toString(),
    variation: null as number | null,
    unite: '',
    serie: serieNeutre,
  },
  {
    libelle: 'Valeur du stock',
    affichage: formatPrix(comptes.valeur),
    variation: null as number | null,
    unite: '',
    serie: serieNeutre,
  },
  {
    libelle: 'Stock faible',
    affichage: Math.round(comptes.faible).toString(),
    variation: null as number | null,
    unite: '',
    serie: serieNeutre,
  },
  {
    libelle: 'En rupture',
    affichage: Math.round(comptes.rupture).toString(),
    variation: null as number | null,
    unite: '',
    serie: serieNeutre,
  },
])

/** Lance le comptage et l'apparition des barres une fois les données là. */
const animerIndicateurs = async () => {
  const cibles = {
    total: total.value,
    valeur: valeurStock.value,
    faible: stockFaible.value,
    rupture: rupture.value,
  }

  if (mouvementReduit()) {
    Object.assign(comptes, cibles)
    return
  }

  gsap.to(comptes, { ...cibles, duration: DUREE.revelation, ease: COURBE.sortie })

  await nextTick()
  const barres = document.querySelectorAll('[data-barre]')
  if (barres.length) {
    gsap.from(barres, {
      scaleY: 0,
      transformOrigin: 'bottom',
      duration: DUREE.panneau,
      ease: COURBE.sortie,
      stagger: { each: 0.02 },
    })
  }
}

watch(chargement, (enCours) => {
  if (!enCours) animerIndicateurs()
})

const categories = computed(() => {
  const compte = new Map<string, number>()
  for (const produit of produits.value) {
    const nom = produit.category || 'Autres'
    compte.set(nom, (compte.get(nom) ?? 0) + 1)
  }
  const maximum = Math.max(1, ...compte.values())
  return [...compte.entries()]
    .map(([nom, valeur]) => ({ nom, total: valeur, part: (valeur / maximum) * 100 }))
    .sort((a, b) => b.total - a.total)
})

const paliersStock = computed(() => {
  const base = Math.max(1, total.value)
  return [
    { libelle: 'En stock', valeur: enStock.value, part: (enStock.value / base) * 100 },
    { libelle: 'Stock faible', valeur: stockFaible.value, part: (stockFaible.value / base) * 100 },
    { libelle: 'En rupture', valeur: rupture.value, part: (rupture.value / base) * 100 },
  ]
})

const meilleuresVentes = computed(() =>
  [...produits.value].sort((a, b) => (b.soldCount ?? 0) - (a.soldCount ?? 0)).slice(0, 4)
)

const aTraiter = computed(() => {
  const points: { libelle: string; detail: string; action: string; to: string; couleur: string }[] = []

  if (rupture.value > 0) {
    points.push({
      libelle: `${rupture.value} pièce${rupture.value > 1 ? 's' : ''} en rupture`,
      detail: 'À réapprovisionner ou à retirer du catalogue',
      action: 'Voir',
      to: '/admin/produits',
      couleur: 'bg-error',
    })
  }

  if (stockFaible.value > 0) {
    points.push({
      libelle: `${stockFaible.value} pièce${stockFaible.value > 1 ? 's' : ''} en stock faible`,
      detail: 'Moins de 4 exemplaires disponibles',
      action: 'Voir',
      to: '/admin/produits',
      couleur: 'bg-warning',
    })
  }

  return points
})

onMounted(async () => {
  try {
    const reponse = await getCache<{ products: ProduitAdmin[] }>('/api/admin/products')
    produits.value = reponse.data.products ?? []
  } catch (e) {
    console.error(e)
  } finally {
    chargement.value = false
  }
})
</script>

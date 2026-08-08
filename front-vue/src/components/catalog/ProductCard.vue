<template>
  <article class="group relative">
    <!-- Visuel : rayon 0, comme toute image pleine largeur du système. -->
    <div class="relative aspect-[3/4] overflow-hidden bg-rule-soft">
      <RouterLink :to="`/produit/${produit.id}`" class="block size-full">
        <img
          v-if="produit.mediaUrl"
          :src="produit.mediaUrl"
          :alt="produit.name"
          loading="lazy"
          class="size-full object-cover transition-transform duration-[320ms] ease-[cubic-bezier(.2,.8,.2,1)] group-hover:scale-[1.03]"
          :class="epuise ? 'opacity-45' : ''"
        />
        <span v-else class="flex size-full items-center justify-center text-ink-300">
          <ImageIcon class="size-8" />
        </span>
      </RouterLink>

      <!-- Signaux, en haut à gauche -->
      <div class="pointer-events-none absolute left-3 top-3 flex flex-col items-start gap-2">
        <ProductBadge v-if="remise" variante="remise">−{{ remise }} %</ProductBadge>
        <ProductBadge v-else-if="produit.isNew" variante="nouveau">Nouveau</ProductBadge>
        <ProductBadge v-if="stockFaible" variante="stock">Plus que {{ produit.stock }}</ProductBadge>
      </div>

      <!-- Favori -->
      <button
        type="button"
        class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-full transition-colors duration-[120ms]"
        :class="favori ? 'bg-ink-900 text-white' : 'bg-surface text-ink-900 hover:bg-white'"
        :aria-pressed="favori"
        :aria-label="favori ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        @click.prevent="$emit('basculer-favori', produit.id)"
      >
        <Heart class="size-4" :class="favori ? 'fill-current' : ''" />
      </button>

      <!-- Rupture : la carte reste lisible mais visiblement inerte. -->
      <span
        v-if="epuise"
        class="t-label absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-surface px-4 py-2 text-ink-900"
      >
        Épuisé
      </span>

      <!-- Survol : nuancier + ajout rapide, sur un voile dégradé -->
      <div
        v-if="!epuise"
        class="pointer-events-none absolute inset-x-0 bottom-0 translate-y-2 bg-gradient-to-t from-ink-900/45 to-transparent p-3 opacity-0 transition-all duration-[200ms] ease-[cubic-bezier(.2,.8,.2,1)] group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0 group-focus-within:opacity-100"
      >
        <div v-if="coloris.length" class="mb-3 flex items-center gap-2">
          <span
            v-for="couleur in coloris"
            :key="couleur"
            class="size-5 rounded-full border border-white/70"
            :style="{ background: couleur }"
          />
        </div>
        <div class="flex gap-2">
          <button type="button" class="btn btn-sm flex-1 bg-surface text-ink-900 hover:bg-white" @click="$emit('ajout-rapide', produit)">
            Ajout rapide
          </button>
          <button
            type="button"
            class="btn btn-sm w-11 shrink-0 bg-surface text-ink-900 hover:bg-white"
            aria-label="Aperçu rapide"
            @click="$emit('apercu', produit)"
          >
            <Eye class="size-4" />
          </button>
        </div>
      </div>
    </div>

    <!--
      Légende. En desktop le nom et le prix partagent une ligne ; en mobile ils
      s'empilent, la largeur de colonne ne permettant pas de les juxtaposer sans
      tronquer le nom.
    -->
    <div class="mt-4">
      <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
        <h3 class="t-body text-ink-900" :class="epuise ? 'text-ink-500' : ''">
          <RouterLink :to="`/produit/${produit.id}`" class="hover:underline">{{ produit.name }}</RouterLink>
        </h3>
        <p class="t-price sm:shrink-0" :class="epuise ? 'text-ink-500' : 'text-ink-900'">
          <span>{{ prix(produit.price) }}</span>
          <span v-if="produit.originalPrice" class="ml-2 text-ink-300 line-through">
            {{ prix(produit.originalPrice) }}
          </span>
        </p>
      </div>

      <p v-if="epuise" class="mt-1">
        <button type="button" class="t-small text-action hover:underline" @click="$emit('prevenir', produit)">
          Me prévenir du réassort
        </button>
      </p>
      <p v-else class="t-small mt-1 text-ink-500">{{ sousTitre }}</p>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Eye, Heart, Image as ImageIcon } from 'lucide-vue-next'
import ProductBadge from './ProductBadge.vue'
import { formatPrix } from '@/lib/format'
import type { Product } from '@/types'

const props = withDefaults(
  defineProps<{
    produit: Product
    favori?: boolean
    /** Couleurs disponibles, en valeurs CSS. */
    coloris?: string[]
  }>(),
  { favori: false, coloris: () => [] }
)

defineEmits<{
  'basculer-favori': [id: string]
  'ajout-rapide': [produit: Product]
  apercu: [produit: Product]
  prevenir: [produit: Product]
}>()

const prix = formatPrix

const epuise = computed(() => (props.produit.stock ?? 0) <= 0)

/** Le seuil de 3 correspond au signal « Plus que N » de la maquette. */
const stockFaible = computed(() => {
  const stock = props.produit.stock ?? 0
  return stock > 0 && stock <= 3
})

const remise = computed(() => {
  const initial = props.produit.originalPrice
  if (!initial || initial <= props.produit.price) return null
  return Math.round((1 - props.produit.price / initial) * 100)
})

const sousTitre = computed(() => {
  const parties: string[] = []
  if (props.produit.category) parties.push(props.produit.category)
  if (props.coloris.length) {
    parties.push(`${props.coloris.length} coloris`)
  }
  return parties.join(' · ')
})
</script>

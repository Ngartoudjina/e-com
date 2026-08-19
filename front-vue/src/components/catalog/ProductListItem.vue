<template>
  <article class="flex gap-6 border border-rule bg-surface p-4">
    <!-- Visuel en portrait, comme dans la vue liste de la maquette. -->
    <RouterLink :to="`/produit/${produit.id}`" class="block w-[132px] shrink-0">
      <div class="aspect-[3/4] overflow-hidden bg-rule-soft">
        <img
          v-if="produit.mediaUrl"
          :src="produit.mediaUrl"
          :alt="produit.name"
          loading="lazy"
          class="size-full object-cover"
        />
        <span v-else class="flex size-full items-center justify-center text-ink-300">
          <ImageIcon class="size-6" />
        </span>
      </div>
    </RouterLink>

    <div class="flex min-w-0 flex-1 flex-col">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-3">
            <h3 class="t-h3 truncate text-ink-900">
              <RouterLink :to="`/produit/${produit.id}`" class="hover:underline">{{ produit.name }}</RouterLink>
            </h3>
            <ProductBadge v-if="remise" variante="remise">−{{ remise }} %</ProductBadge>
            <ProductBadge v-else-if="produit.isNew" variante="nouveau">Nouveau</ProductBadge>
          </div>
          <p class="t-body mt-1 text-ink-500">{{ description }}</p>
        </div>

        <div class="shrink-0 text-right">
          <p class="t-price text-ink-900">
            {{ prix(produit.price) }}
            <span v-if="produit.originalPrice" class="ml-2 text-ink-300 line-through">
              {{ prix(produit.originalPrice) }}
            </span>
          </p>
          <p class="t-small text-ink-500">ou {{ fractionne(produit.price) }}</p>
        </div>
      </div>

      <!-- Coloris et amplitude de tailles -->
      <div v-if="coloris.length" class="mt-4 flex items-center gap-3">
        <span
          v-for="(couleur, index) in coloris"
          :key="couleur"
          class="size-6 rounded-full"
          :class="index === 0 ? 'ring-1 ring-ink-900 ring-offset-2 ring-offset-surface' : 'ring-1 ring-rule'"
          :style="{ background: couleur }"
        />
        <span class="t-small ml-1 text-ink-500">XS — XL</span>
      </div>

      <!-- Actions : une seule action primaire, le reste en contour ou icône. -->
      <div class="mt-auto flex flex-wrap items-center gap-3 pt-6">
        <button
          type="button"
          class="btn btn-primary"
          :disabled="epuise"
          @click="$emit('ajouter', { ...produit, quantity: 1, selectedColor: 'default', selectedSize: 'M' })"
        >
          Ajouter au panier
        </button>
        <button type="button" class="btn btn-secondary" @click="$emit('apercu', produit)">
          Aperçu rapide
        </button>
        <button
          type="button"
          class="btn btn-icon"
          :aria-pressed="favori"
          :aria-label="favori ? 'Retirer des favoris' : 'Ajouter aux favoris'"
          @click="$emit('basculer-favori', produit.id)"
        >
          <Heart class="size-4" :class="favori ? 'fill-current' : ''" />
        </button>

        <p class="t-small flex items-center gap-2" :class="statut.classe">
          <span aria-hidden="true">●</span>{{ statut.libelle }}
        </p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Heart, Image as ImageIcon } from 'lucide-vue-next'
import ProductBadge from './ProductBadge.vue'
import { formatPrix, formatFractionne } from '@/lib/format'
import type { Product, ProductWithDetails } from '@/types'

const props = withDefaults(
  defineProps<{ produit: Product; favori?: boolean; coloris?: string[] }>(),
  { favori: false, coloris: () => [] }
)

defineEmits<{
  'basculer-favori': [id: string]
  apercu: [produit: Product]
  ajouter: [produit: ProductWithDetails]
}>()

const prix = formatPrix
const fractionne = formatFractionne

const epuise = computed(() => (props.produit.stock ?? 0) <= 0)

const remise = computed(() => {
  const initial = props.produit.originalPrice
  if (!initial || initial <= props.produit.price) return null
  return Math.round((1 - props.produit.price / initial) * 100)
})

const description = computed(
  () => props.produit.description || props.produit.category || 'Pièce de la collection'
)

/** Trois états de disponibilité, aux trois couleurs sémantiques du système. */
const statut = computed(() => {
  const stock = props.produit.stock ?? 0
  if (stock <= 0) return { libelle: 'Épuisé', classe: 'text-error' }
  if (stock <= 3) return { libelle: `Plus que ${stock} pièce${stock > 1 ? 's' : ''}`, classe: 'text-warning' }
  return { libelle: 'En stock · expédié sous 24 h', classe: 'text-success' }
})
</script>

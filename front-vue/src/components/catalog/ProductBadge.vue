<template>
  <span class="t-label inline-flex items-center px-2 py-[6px]" :class="styles[variante]">
    <slot />
  </span>
</template>

<script lang="ts">
/**
 * Signaux de la grille produit.
 * Cinq teintes seulement, reprises de la planche « Badges & signaux » :
 * l'encre pour la nouveauté, le rouge pour la remise, l'ambre pour le stock
 * faible, le bleu pour la preuve sociale, le contour pour la rareté.
 */
export type VarianteBadge = 'nouveau' | 'remise' | 'stock' | 'best' | 'contour'
</script>

<script setup lang="ts">
withDefaults(defineProps<{ variante?: VarianteBadge }>(), { variante: 'nouveau' })

const styles: Record<VarianteBadge, string> = {
  nouveau: 'bg-ink-900 text-white',
  remise: 'bg-error text-white',
  stock: 'bg-[#F5E6C8] text-warning',
  best: 'bg-[#E4EAFF] text-action',
  contour: 'border border-ink-900 bg-paper text-ink-900',
}
</script>

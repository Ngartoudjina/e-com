<template>
  <!--
    Grille locale : le composant occupe 7 des 12 colonnes de la page et les
    répartit en 1 (vignettes) + 6 (visuel principal), gouttière 32.
  -->
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-7 lg:gap-8">
    <!-- Rail de vignettes : 1 colonne, desktop seulement. -->
    <div class="hidden lg:col-span-1 lg:block">
      <ul class="flex flex-col gap-3">
        <li v-for="(visuel, index) in visuels" :key="visuel">
          <button
            type="button"
            class="block w-full overflow-hidden border transition-colors duration-[120ms]"
            :class="index === actif ? 'border-ink-900' : 'border-rule hover:border-ink-300'"
            :aria-label="`Voir le visuel ${index + 1}`"
            :aria-pressed="index === actif"
            @click="actif = index"
          >
            <span class="block aspect-[4/5] bg-rule-soft">
              <img :src="visuel" :alt="`${nom} — visuel ${index + 1}`" class="size-full object-cover" />
            </span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Visuel principal : ratio 4:5, rayon 0. -->
    <div class="lg:col-span-6">
      <div class="relative aspect-[4/5] bg-rule-soft">
        <img
          v-if="visuels[actif]"
          :src="visuels[actif]"
          :alt="nom"
          class="size-full object-cover"
        />
        <span v-else class="flex size-full items-center justify-center text-ink-300">
          <ImageIcon class="size-10" />
        </span>

        <ProductBadge v-if="badge" :variante="badge.variante" class="absolute left-4 top-4">
          {{ badge.libelle }}
        </ProductBadge>

        <!-- Zoom et plein écran, en surimpression basse à droite. -->
        <div class="absolute bottom-4 right-4 hidden gap-2 lg:flex">
          <button type="button" class="btn btn-sm bg-surface text-ink-900" @click="$emit('zoom', actif)">
            <ZoomIn class="size-4" />
            Zoom
          </button>
          <button
            type="button"
            class="btn btn-sm w-11 bg-surface text-ink-900"
            aria-label="Plein écran"
            @click="$emit('plein-ecran', actif)"
          >
            <Maximize2 class="size-4" />
          </button>
        </div>

        <!-- Pastilles de progression, mobile uniquement. -->
        <div v-if="visuels.length > 1" class="absolute inset-x-0 bottom-4 flex justify-center gap-1.5 lg:hidden">
          <button
            v-for="(_, index) in visuels"
            :key="index"
            type="button"
            class="h-[3px] transition-all duration-[200ms]"
            :class="index === actif ? 'w-6 bg-ink-900' : 'w-3 bg-ink-300'"
            :aria-label="`Visuel ${index + 1}`"
            @click="actif = index"
          />
        </div>
      </div>

      <!-- Détails : deux visuels côte à côte sous le principal. -->
      <div v-if="details.length" class="mt-4 grid grid-cols-2 gap-4">
        <div v-for="detail in details" :key="detail" class="aspect-square bg-rule-soft">
          <img :src="detail" :alt="`${nom} — détail`" class="size-full object-cover" loading="lazy" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Image as ImageIcon, Maximize2, ZoomIn } from 'lucide-vue-next'
import ProductBadge, { type VarianteBadge } from '@/components/catalog/ProductBadge.vue'

withDefaults(
  defineProps<{
    nom: string
    visuels: string[]
    details?: string[]
    badge?: { libelle: string; variante: VarianteBadge } | null
  }>(),
  { details: () => [], badge: null }
)

defineEmits<{ zoom: [index: number]; 'plein-ecran': [index: number] }>()

const actif = ref(0)
</script>

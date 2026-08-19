<template>
  <Transition name="voile">
    <div
      v-if="produit"
      class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/90 p-4"
      role="dialog"
      aria-modal="true"
      :aria-label="`Aperçu rapide — ${produit.name}`"
      @click="$emit('fermer')"
    >
      <div
        class="grid max-h-[90dvh] w-full max-w-[980px] grid-cols-1 overflow-auto bg-surface shadow-[var(--shadow-e3)] md:grid-cols-2"
        style="border-radius: var(--radius-4)"
        @click.stop
      >
        <!-- Visuel -->
        <div class="aspect-square bg-rule-soft">
          <img
            v-if="produit.mediaUrl"
            :src="produit.mediaUrl"
            :alt="produit.name"
            class="size-full object-cover"
          />
          <span v-else class="flex size-full items-center justify-center text-ink-300">
            <ImageIcon class="size-10" />
          </span>
        </div>

        <!-- Détail -->
        <div class="relative p-8 lg:p-12">
          <button
            type="button"
            class="absolute right-6 top-6 text-ink-900 transition-colors hover:text-ink-500"
            aria-label="Fermer"
            @click="$emit('fermer')"
          >
            <X class="size-5" />
          </button>

          <p class="t-label text-ink-500">{{ produit.category || 'Collection' }}</p>
          <h2 class="t-h2 mt-3 text-ink-900">{{ produit.name }}</h2>

          <div class="mt-6 flex flex-wrap items-baseline gap-3">
            <p class="font-sans text-[28px] leading-none text-ink-900" data-numeric>
              {{ formatPrix(produit.price) }}
            </p>
            <p class="t-small text-ink-500">ou {{ formatFractionne(produit.price) }}</p>
          </div>

          <p class="t-small mt-3 flex items-center gap-2" :class="statut.classe">
            <span aria-hidden="true">●</span>{{ statut.libelle }}
          </p>

          <!-- Coloris -->
          <div v-if="coloris.length" class="mt-8">
            <p class="t-label text-ink-500">Coloris</p>
            <div class="mt-3 flex items-center gap-3">
              <button
                v-for="(couleur, index) in coloris"
                :key="couleur"
                type="button"
                class="size-8 rounded-full transition-transform duration-[200ms]"
                :class="index === colorisChoisi
                  ? 'ring-1 ring-ink-900 ring-offset-2 ring-offset-surface'
                  : 'ring-1 ring-rule hover:ring-ink-300'"
                :style="{ background: couleur }"
                :aria-label="`Coloris ${index + 1}`"
                :aria-pressed="index === colorisChoisi"
                @click="colorisChoisi = index"
              />
            </div>
          </div>

          <!-- Taille -->
          <div class="mt-8">
            <div class="flex items-baseline justify-between">
              <p class="t-label text-ink-500">Taille</p>
              <button type="button" class="t-small text-action hover:underline">Guide des tailles</button>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                v-for="taille in tailles"
                :key="taille.valeur"
                type="button"
                :disabled="taille.indisponible"
                class="flex h-11 w-[52px] items-center justify-center border text-[15px] transition-colors duration-[200ms]"
                :class="classeTaille(taille)"
                :aria-pressed="taille.valeur === tailleChoisie"
                @click="tailleChoisie = taille.valeur"
              >
                {{ taille.valeur }}
              </button>
            </div>
            <p v-if="erreurTaille" class="t-small mt-2 text-error">Choisissez une taille.</p>
          </div>

          <!-- Action -->
          <div class="mt-8 flex gap-3">
            <button type="button" class="btn btn-lg btn-primary flex-1" :disabled="epuise" @click="ajouter">
              Ajouter au panier — {{ formatPrix(produit.price) }}
            </button>
            <button
              type="button"
              class="btn btn-icon"
              style="height: var(--size-control-lg); width: var(--size-control-lg)"
              aria-label="Ajouter aux favoris"
            >
              <Heart class="size-4" />
            </button>
          </div>

          <RouterLink
            :to="`/produit/${produit.id}`"
            class="t-body mt-6 block text-center text-action hover:underline"
          >
            Voir la fiche complète
          </RouterLink>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Heart, Image as ImageIcon, X } from 'lucide-vue-next'
import { formatPrix, formatFractionne } from '@/lib/format'
import type { Product, ProductWithDetails } from '@/types'

const props = withDefaults(
  defineProps<{ produit: Product | null; coloris?: string[] }>(),
  { coloris: () => [] }
)

const emit = defineEmits<{ fermer: []; ajouter: [produit: ProductWithDetails] }>()

const tailles = [
  { valeur: 'XS' },
  { valeur: 'S' },
  { valeur: 'M' },
  { valeur: 'L' },
  { valeur: 'XL', indisponible: true },
]

const colorisChoisi = ref(0)
const tailleChoisie = ref<string | null>(null)
const erreurTaille = ref(false)

const epuise = computed(() => (props.produit?.stock ?? 0) <= 0)

const statut = computed(() => {
  const stock = props.produit?.stock ?? 0
  if (stock <= 0) return { libelle: 'Épuisé', classe: 'text-error' }
  if (stock <= 3) return { libelle: `Plus que ${stock} pièce${stock > 1 ? 's' : ''}`, classe: 'text-warning' }
  return { libelle: 'En stock · expédié sous 24 h', classe: 'text-success' }
})

const classeTaille = (taille: { valeur: string; indisponible?: boolean }) => {
  if (taille.indisponible) return 'cursor-not-allowed border-rule-soft text-ink-300 line-through'
  return taille.valeur === tailleChoisie.value
    ? 'border-ink-900 bg-ink-900 text-white'
    : 'border-rule bg-surface text-ink-900 hover:border-ink-900'
}

/** La taille est obligatoire : on la réclame plutôt que d'en choisir une au hasard. */
const ajouter = () => {
  if (!props.produit) return
  if (!tailleChoisie.value) {
    erreurTaille.value = true
    return
  }
  emit('ajouter', {
    ...props.produit,
    quantity: 1,
    selectedColor: props.coloris[colorisChoisi.value] ?? 'default',
    selectedSize: tailleChoisie.value,
  })
}

// Chaque ouverture repart d'un choix vierge.
watch(
  () => props.produit?.id,
  () => {
    colorisChoisi.value = 0
    tailleChoisie.value = null
    erreurTaille.value = false
  }
)

// Une modale ouverte fige le défilement de la page.
watch(
  () => props.produit,
  (ouvert) => {
    document.body.style.overflow = ouvert ? 'hidden' : ''
  }
)

const surEchap = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.produit) emit('fermer')
}

onMounted(() => window.addEventListener('keydown', surEchap))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', surEchap)
  document.body.style.overflow = ''
})
</script>

<style scoped>
@reference "../../index.css";

.voile-enter-active,
.voile-leave-active {
  transition: opacity var(--duration-panel) var(--ease-exit);
}
.voile-enter-from,
.voile-leave-to {
  opacity: 0;
}
</style>

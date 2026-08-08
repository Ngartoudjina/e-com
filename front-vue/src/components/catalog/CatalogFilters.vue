<template>
  <aside>
    <div class="flex items-baseline justify-between">
      <h2 class="t-label text-ink-900">Filtrer</h2>
      <button type="button" class="t-small text-ink-500 hover:text-ink-900 hover:underline" @click="$emit('tout-effacer')">
        Tout effacer
      </button>
    </div>

    <div class="mt-4 border-t border-rule pt-6">
      <label class="relative block">
        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-500" />
        <input
          :value="recherche"
          type="search"
          placeholder="Affiner dans cette page"
          class="field pl-9"
          @input="$emit('update:recherche', ($event.target as HTMLInputElement).value)"
        />
      </label>
    </div>

    <!-- Catégorie -->
    <section class="mt-8 border-t border-rule pt-6">
      <h3 class="t-label text-ink-900">Catégorie</h3>
      <ul class="mt-4 space-y-3">
        <li v-for="categorie in categories" :key="categorie.nom">
          <button
            type="button"
            class="flex w-full items-baseline justify-between gap-3 text-left transition-colors duration-[120ms]"
            @click="$emit('update:categorie', categorie.nom === categorieActive ? null : categorie.nom)"
          >
            <span
              class="t-body"
              :class="categorie.nom === categorieActive ? 'text-ink-900 underline underline-offset-4' : 'text-ink-700 hover:text-ink-900'"
            >
              {{ categorie.nom }}
            </span>
            <span data-numeric class="t-small shrink-0 text-ink-500">{{ categorie.total }}</span>
          </button>
        </li>
      </ul>
    </section>

    <!-- Taille -->
    <section class="mt-8 border-t border-rule pt-6">
      <h3 class="t-label text-ink-900">Taille</h3>
      <div class="mt-4 flex flex-wrap gap-2">
        <button
          v-for="taille in tailles"
          :key="taille.valeur"
          type="button"
          :disabled="taille.indisponible"
          class="flex h-11 w-[52px] items-center justify-center border text-[15px] transition-colors duration-[200ms]"
          :class="classeTaille(taille)"
          :aria-pressed="taille.valeur === tailleActive"
          @click="$emit('update:taille', taille.valeur === tailleActive ? null : taille.valeur)"
        >
          {{ taille.valeur }}
        </button>
      </div>
    </section>

    <!-- Couleur -->
    <section class="mt-8 border-t border-rule pt-6">
      <h3 class="t-label text-ink-900">Couleur</h3>
      <div class="mt-4 flex flex-wrap gap-3">
        <button
          v-for="couleur in couleurs"
          :key="couleur.nom"
          type="button"
          class="relative size-8 rounded-full transition-transform duration-[200ms]"
          :style="{ background: couleur.valeur }"
          :aria-label="couleur.nom"
          :aria-pressed="couleur.nom === couleurActive"
          :class="couleur.nom === couleurActive
            ? 'ring-1 ring-ink-900 ring-offset-2 ring-offset-paper'
            : 'ring-1 ring-rule hover:ring-ink-300'"
          @click="$emit('update:couleur', couleur.nom === couleurActive ? null : couleur.nom)"
        />
      </div>
    </section>

    <!-- Prix -->
    <section class="mt-8 border-t border-rule pt-6">
      <h3 class="t-label text-ink-900">Prix</h3>
      <div class="mt-5 flex gap-3">
        <label class="flex-1">
          <span class="sr-only">Prix minimum</span>
          <input
            :value="prixMin"
            type="number"
            min="0"
            data-numeric
            class="field"
            @change="$emit('update:prixMin', Number(($event.target as HTMLInputElement).value))"
          />
        </label>
        <label class="flex-1">
          <span class="sr-only">Prix maximum</span>
          <input
            :value="prixMax"
            type="number"
            min="0"
            data-numeric
            class="field"
            @change="$emit('update:prixMax', Number(($event.target as HTMLInputElement).value))"
          />
        </label>
      </div>
    </section>

    <!-- Bascules -->
    <section class="mt-8 border-t border-rule pt-6 space-y-4">
      <label class="flex items-center justify-between gap-4">
        <span class="t-body text-ink-900">En stock uniquement</span>
        <button
          type="button"
          role="switch"
          :aria-checked="enStock"
          class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-[200ms]"
          :class="enStock ? 'bg-action' : 'bg-rule'"
          @click="$emit('update:enStock', !enStock)"
        >
          <span
            class="absolute top-1 size-4 rounded-full bg-white transition-all duration-[200ms]"
            :class="enStock ? 'left-6' : 'left-1'"
          />
        </button>
      </label>
    </section>
  </aside>
</template>

<script setup lang="ts">
import { Search } from 'lucide-vue-next'

interface Taille {
  valeur: string
  indisponible?: boolean
}

const props = withDefaults(
  defineProps<{
    categories: { nom: string; total: number }[]
    categorieActive: string | null
    tailles: Taille[]
    tailleActive: string | null
    couleurs: { nom: string; valeur: string }[]
    couleurActive: string | null
    prixMin: number
    prixMax: number
    enStock: boolean
    recherche: string
  }>(),
  {}
)

defineEmits<{
  'update:categorie': [valeur: string | null]
  'update:taille': [valeur: string | null]
  'update:couleur': [valeur: string | null]
  'update:prixMin': [valeur: number]
  'update:prixMax': [valeur: number]
  'update:enStock': [valeur: boolean]
  'update:recherche': [valeur: string]
  'tout-effacer': []
}>()

/** Actif : encre pleine. Indisponible : texte estompé et interaction coupée. */
const classeTaille = (taille: Taille) => {
  if (taille.indisponible) {
    return 'cursor-not-allowed border-rule-soft text-ink-300'
  }
  return taille.valeur === props.tailleActive
    ? 'border-ink-900 bg-ink-900 text-white'
    : 'border-rule bg-surface text-ink-900 hover:border-ink-900'
}
</script>

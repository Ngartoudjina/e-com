<template>
  <ol class="flex items-center gap-2 sm:gap-4">
    <li v-for="(etape, index) in etapes" :key="etape.cle" class="flex flex-1 items-center gap-2 last:flex-none sm:gap-4">
      <span class="flex items-center gap-3">
        <!-- Étape franchie : encre pleine et coche. En cours : contour. À venir : estompée. -->
        <span
          class="flex size-8 shrink-0 items-center justify-center rounded-full border text-[13px]"
          :class="classeePastille(index)"
        >
          <Check v-if="index < courante" class="size-4" />
          <span v-else data-numeric>{{ index + 1 }}</span>
        </span>
        <span
          class="t-body hidden sm:inline"
          :class="index === courante ? 'text-ink-900' : index < courante ? 'text-ink-900' : 'text-ink-300'"
        >
          {{ etape.libelle }}
        </span>
      </span>

      <span
        v-if="index < etapes.length - 1"
        class="h-px flex-1"
        :class="index < courante ? 'bg-ink-900' : 'bg-rule'"
        aria-hidden="true"
      />
    </li>
  </ol>
</template>

<script setup lang="ts">
import { Check } from 'lucide-vue-next'

const props = defineProps<{
  etapes: { cle: string; libelle: string }[]
  courante: number
}>()

const classeePastille = (index: number) => {
  if (index < props.courante) return 'border-ink-900 bg-ink-900 text-white'
  if (index === props.courante) return 'border-ink-900 bg-paper text-ink-900'
  return 'border-rule bg-paper text-ink-300'
}
</script>

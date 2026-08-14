<template>
  <div class="inline-flex items-center border border-rule bg-surface">
    <button
      type="button"
      class="flex size-11 items-center justify-center text-ink-900 transition-colors hover:bg-rule-soft disabled:text-ink-300"
      :disabled="quantite <= min"
      aria-label="Diminuer la quantité"
      @click="$emit('modifier', quantite - 1)"
    >
      <Minus class="size-4" />
    </button>

    <span data-numeric class="w-10 text-center text-[15px]" aria-live="polite">{{ quantite }}</span>

    <button
      type="button"
      class="flex size-11 items-center justify-center text-ink-900 transition-colors hover:bg-rule-soft disabled:text-ink-300"
      :disabled="max !== undefined && quantite >= max"
      aria-label="Augmenter la quantité"
      @click="$emit('modifier', quantite + 1)"
    >
      <Plus class="size-4" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { Minus, Plus } from 'lucide-vue-next'

withDefaults(defineProps<{ quantite: number; min?: number; max?: number }>(), { min: 1 })

defineEmits<{ modifier: [quantite: number] }>()
</script>

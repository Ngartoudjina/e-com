<template>
  <button
    type="button"
    role="switch"
    :aria-checked="model"
    :class="cn('peer data-[state=checked]:bg-primary data-[state=unchecked]:bg-input focus-visible:border-ring focus-visible:ring-ring/50 dark:data-[state=unchecked]:bg-input/80 inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50', $attrs.class)"
    :data-state="model ? 'checked' : 'unchecked'"
    @click="model = !model"
  >
    <span
      class="bg-background dark:data-[state=unchecked]:bg-foreground dark:data-[state=checked]:bg-primary-foreground pointer-events-none block size-4 rounded-full ring-0 transition-transform data-[state=checked]:translate-x-[calc(100%-2px)] data-[state=unchecked]:translate-x-0"
      :data-state="model ? 'checked' : 'unchecked'"
    />
  </button>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const model = defineModel<boolean>({ default: false })
const $attrs = useAttrs()
</script>

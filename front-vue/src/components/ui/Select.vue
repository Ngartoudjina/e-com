<template>
  <div class="relative">
    <select
      v-model="model"
      :class="cn('border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 data-[size=default]:h-9 data-[size=sm]:h-8 flex w-full min-w-0 items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs whitespace-nowrap outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1', $attrs.class)"
    >
      <slot />
    </select>
    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 opacity-50" />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const model = defineModel<string | number | undefined>({ default: undefined })
const $attrs = useAttrs()
</script>

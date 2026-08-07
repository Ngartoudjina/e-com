<template>
  <input
    data-slot="input"
    :type="type"
    :class="cn('flex h-11 w-full min-w-0 rounded-xl border border-input bg-card px-3.5 py-1 text-base outline-none transition-[color,border-color,box-shadow] placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30 focus-visible:border-primary/50 focus-visible:ring-[3px] focus-visible:ring-ring/25 aria-invalid:border-destructive aria-invalid:ring-destructive/20', $attrs.class)"
  />
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

withDefaults(defineProps<{ type?: string }>(), { type: 'text' })
const $attrs = useAttrs()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'flex flex-col gap-6 rounded-2xl border border-border bg-card py-6 text-card-foreground shadow-[var(--shadow-soft)]',
        $attrs.class
      )
    "
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

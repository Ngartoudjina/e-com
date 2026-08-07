<template>
  <div data-slot="card-description" :class="cn('text-muted-foreground text-sm', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

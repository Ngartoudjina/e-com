<template>
  <caption data-slot="table-caption" :class="cn('text-muted-foreground mt-4 text-sm', $attrs.class)">
    <slot />
  </caption>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

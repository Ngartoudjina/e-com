<template>
  <div data-slot="avatar-fallback" :class="cn('bg-muted flex size-full items-center justify-center rounded-full', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

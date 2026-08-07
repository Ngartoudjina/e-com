<template>
  <div data-slot="card-footer" :class="cn('flex items-center px-6 [.border-t]:pt-6', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

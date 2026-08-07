<template>
  <div data-slot="card-content" :class="cn('px-6', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

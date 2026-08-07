<template>
  <div data-slot="card-title" :class="cn('leading-none font-semibold', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

<template>
  <thead data-slot="table-header" :class="cn('[&_tr]:border-b', $attrs.class)">
    <slot />
  </thead>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

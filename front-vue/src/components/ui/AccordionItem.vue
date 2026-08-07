<template>
  <div data-slot="accordion-item" :class="cn('border-b last:border-b-0', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { provide, useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ value: string }>()

provide('accordion-value', props.value)

const $attrs = useAttrs()
</script>

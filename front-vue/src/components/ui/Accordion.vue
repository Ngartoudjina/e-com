<template>
  <div data-slot="accordion" :class="cn('w-full', $attrs.class)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { provide, ref } from 'vue'
import { cn } from '@/lib/utils'

const model = ref<string | undefined>(undefined)

const toggle = (value: string) => {
  model.value = model.value === value ? undefined : value
}

provide('accordion', { model, toggle })
</script>

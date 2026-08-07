<template>
  <div :data-state="isOpen() ? 'open' : 'closed'" class="overflow-hidden text-sm transition-all">
    <div :class="cn('pt-0 pb-4', $attrs.class)">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject, useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{ value?: string }>(), { value: undefined })

type AccordionCtx = {
  model: string | undefined
}

const ctx = inject<AccordionCtx>('accordion')
const itemValue = inject<string>('accordion-value', '')
const value = props.value ?? itemValue
const isOpen = () => ctx?.model === value

const $attrs = useAttrs()
</script>

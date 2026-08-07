<template>
  <h3>
    <button
      type="button"
      :data-state="isOpen() ? 'open' : 'closed'"
      :class="cn('focus-visible:border-ring focus-visible:ring-ring/50 flex flex-1 items-start justify-between gap-4 rounded-md py-4 text-left text-sm font-medium transition-all outline-none hover:underline focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50 [&[data-state=open]>svg]:rotate-180', $attrs.class)"
      @click="toggle"
    >
      <slot />
      <ChevronDown class="size-4 shrink-0 transition-transform duration-200" />
    </button>
  </h3>
</template>

<script setup lang="ts">
import { inject, useAttrs } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{ value?: string }>(), { value: undefined })

type AccordionCtx = {
  model: string | undefined
  toggle: (value: string) => void
}

const ctx = inject<AccordionCtx>('accordion')
const itemValue = inject<string>('accordion-value', '')
const value = props.value ?? itemValue
const isOpen = () => ctx?.model === value
const toggle = () => ctx?.toggle(value)

const $attrs = useAttrs()
</script>

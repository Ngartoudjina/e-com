<template>
  <span
    data-slot="separator"
    :class="cn('bg-border shrink-0', orientation === 'horizontal' ? 'data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full' : 'data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px', $attrs.class)"
    :data-orientation="orientation"
  />
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

withDefaults(defineProps<{ orientation?: 'horizontal' | 'vertical' }>(), { orientation: 'horizontal' })
const $attrs = useAttrs()
</script>

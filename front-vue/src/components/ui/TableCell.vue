<template>
  <td
    data-slot="table-cell"
    :class="cn('p-2 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]', $attrs.class)"
  >
    <slot />
  </td>
</template>

<script setup lang="ts">
import { useAttrs } from 'vue'
import { cn } from '@/lib/utils'

const $attrs = useAttrs()
</script>

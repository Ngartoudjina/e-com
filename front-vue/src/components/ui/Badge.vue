<template>
  <RouterLink
    v-if="to"
    :to="to"
    :class="cn(badgeVariants({ variant }), $attrs.class)"
  >
    <slot />
  </RouterLink>
  <span v-else :class="cn(badgeVariants({ variant }), $attrs.class)">
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    variant?: VariantProps<typeof badgeVariants>['variant']
    to?: string
  }>(),
  { variant: 'default' }
)

const badgeVariants = cva(
  'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors [&>svg]:pointer-events-none [&>svg]:size-3 focus-visible:ring-[3px] focus-visible:ring-ring/40 aria-invalid:border-destructive',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-primary-foreground [a&]:hover:brightness-110',
        secondary: 'border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/70',
        destructive: 'border-transparent bg-destructive/10 text-destructive [a&]:hover:bg-destructive/15',
        success: 'border-transparent bg-success/12 text-success',
        warning: 'border-transparent bg-warning/15 text-warning',
        outline: 'border-border text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
)

const classes = computed(() => cn(badgeVariants({ variant: props.variant })))
</script>

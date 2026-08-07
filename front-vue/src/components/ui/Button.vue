<template>
  <RouterLink
    v-if="to"
    :to="to"
    :class="classes"
  >
    <slot />
  </RouterLink>
  <component :is="as" v-else :class="classes">
    <slot />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    variant?: VariantProps<typeof buttonVariants>['variant']
    size?: VariantProps<typeof buttonVariants>['size']
    to?: string
    as?: string
  }>(),
  {
    variant: 'default',
    size: 'default',
    as: 'button',
  }
)

const buttonVariants = cva(
  "inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-semibold outline-none transition-all duration-200 active:scale-[0.97] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 focus-visible:ring-[3px] focus-visible:ring-ring/40 aria-invalid:border-destructive aria-invalid:ring-destructive/20",
  {
    variants: {
      variant: {
        default:
          'bg-primary text-primary-foreground shadow-[var(--shadow-brand)] hover:-translate-y-0.5 hover:brightness-110 active:translate-y-0',
        destructive:
          'bg-destructive text-white shadow-[var(--shadow-soft)] hover:brightness-110 focus-visible:ring-destructive/30',
        outline:
          'border border-border bg-card text-foreground shadow-[var(--shadow-soft)] hover:border-primary/40 hover:bg-accent hover:text-accent-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/70',
        ghost: 'hover:bg-accent hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2 has-[>svg]:px-3.5',
        sm: 'h-9 gap-1.5 rounded-lg px-3 text-[13px] has-[>svg]:px-2.5',
        lg: 'h-12 rounded-2xl px-7 text-base has-[>svg]:px-5',
        icon: 'size-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
)

const classes = computed(() => cn(buttonVariants({ variant: props.variant, size: props.size })))
</script>

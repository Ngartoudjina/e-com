<template>
  <div class="flex min-h-screen items-center justify-center bg-background">
    <div class="flex flex-col items-center gap-5">
      <span class="relative flex size-14 items-center justify-center">
        <span class="absolute inset-0 rounded-full border-[3px] border-border" />
        <span class="absolute inset-0 animate-spin rounded-full border-[3px] border-transparent border-t-primary" />
      </span>
      <span class="text-sm font-medium text-muted-foreground">{{ label }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ label?: string }>(), { label: 'Chargement…' })
</script>

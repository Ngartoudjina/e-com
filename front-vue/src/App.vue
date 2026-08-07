<template>
  <div class="app-shell">
    <RouterView />
    <Toaster />
  </div>
</template>

<script setup lang="ts">
import Toaster from '@/components/common/Toaster.vue'
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  background:
    radial-gradient(circle at top left, oklch(0.714 0.162 305 / 0.12), transparent 30%),
    radial-gradient(circle at bottom right, oklch(0.68 0.2 350 / 0.12), transparent 28%),
    var(--background);
}
</style>

<template>
  <img :src="src" :alt="alt" data-slot="avatar-image" class="aspect-square size-full" />
</template>

<script setup lang="ts">
withDefaults(defineProps<{ src?: string; alt?: string }>(), { alt: '' })
</script>

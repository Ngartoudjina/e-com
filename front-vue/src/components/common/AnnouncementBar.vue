<template>
  <div class="bg-ink-900 text-white">
    <div class="container-page">
      <ul class="flex items-center justify-center gap-6 py-[10px] sm:gap-10">
        <li
          v-for="(message, index) in messages"
          :key="message"
          class="flex items-center gap-6 sm:gap-10"
          :class="index > 0 ? 'hidden sm:flex' : ''"
        >
          <!-- Séparateur en point, comme sur la maquette. -->
          <span v-if="index > 0" aria-hidden="true" class="text-ink-500">·</span>
          <span class="t-label whitespace-nowrap">{{ message }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Les messages viennent des réglages du serveur.
 * Ils étaient écrits en dur ici, y compris « Livraison offerte dès 150 € » —
 * un seuil qui pouvait diverger de celui réellement appliqué au panier.
 */
import { computed, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'

const settings = useSettingsStore()

const messages = computed(() =>
  settings.annonces.length
    ? settings.annonces
    : ['Livraison offerte dès ' + settings.franco + ' €', 'Retours gratuits sous 30 jours']
)

onMounted(() => settings.charger())
</script>

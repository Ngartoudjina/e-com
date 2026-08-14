<template>
  <section class="section relative overflow-hidden bg-background">
    <div class="pointer-events-none absolute left-1/2 top-0 h-px w-[70%] -translate-x-1/2 bg-gradient-to-r from-transparent via-border to-transparent" />

    <div class="container-x">
      <!-- En-tête de section -->
      <div class="mx-auto max-w-2xl text-center">
        <span v-reveal class="eyebrow">À propos de GOLDSHOP</span>
        <h2 v-reveal="80" class="display-2 mt-5">
          Une boutique pensée pour<br class="hidden sm:block" />
          <span class="text-gradient">acheter en confiance</span>
        </h2>
        <p v-reveal="140" class="mt-5 text-lg leading-relaxed text-muted-foreground">
          Large catalogue, paiements protégés et livraison rapide.
          Tout est fait pour que votre commande arrive sans mauvaise surprise.
        </p>
      </div>

      <!-- Grille bento -->
      <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:mt-16 lg:grid-cols-4">
        <!-- Visuel principal : occupe la moitié gauche, les 4 atouts remplissent la droite en 2×2 -->
        <div v-reveal class="group relative overflow-hidden rounded-3xl sm:col-span-2 lg:row-span-2">
          <div class="relative aspect-[4/3] sm:aspect-auto sm:h-full sm:min-h-[26rem]">
            <img
              src="/akk.jpeg"
              alt="Notre sélection de produits"
              class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-105"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent" />
          </div>

          <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
              <Sparkles class="size-3.5" />
              Sélection du moment
            </span>
            <h3 class="mt-4 text-2xl font-bold text-white sm:text-3xl">
              Des produits choisis un par un
            </h3>
            <p class="mt-2 max-w-md text-sm leading-relaxed text-white/75">
              Chaque référence est vérifiée avant d'entrer au catalogue : qualité, disponibilité, prix juste.
            </p>
            <RouterLink
              to="/catalogue"
              class="mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-neutral-900 transition-transform duration-300 hover:-translate-y-0.5"
            >
              Voir le catalogue
              <ArrowRight class="size-4" />
            </RouterLink>
          </div>

          <!-- Pastille clients -->
          <div class="glass absolute right-5 top-5 flex items-center gap-3 rounded-2xl px-4 py-3">
            <img src="/ass.jpeg" alt="" class="size-10 rounded-full object-cover" />
            <span>
              <span class="block text-sm font-bold leading-tight">10 000+</span>
              <span class="block text-xs text-muted-foreground">clients servis</span>
            </span>
          </div>
        </div>

        <!-- Cartes d'atouts -->
        <article
          v-for="(feature, index) in features"
          :key="feature.title"
          v-reveal="index * 90"
          class="surface surface-hover p-6"
        >
          <span
            class="inline-flex size-11 items-center justify-center rounded-2xl"
            :class="feature.tint"
          >
            <component :is="feature.icon" class="size-5" />
          </span>
          <h3 class="mt-4 text-lg font-bold">{{ feature.title }}</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
            {{ feature.description }}
          </p>
        </article>
      </div>

      <!-- Chiffres clés -->
      <div v-reveal class="surface mt-4 grid grid-cols-2 divide-border sm:divide-x lg:grid-cols-4">
        <div v-for="stat in stats" :key="stat.label" class="px-6 py-8 text-center">
          <p class="text-3xl font-extrabold tracking-tight sm:text-4xl">
            <span class="text-gradient">{{ stat.value }}</span>
          </p>
          <p class="mt-1.5 text-sm text-muted-foreground">{{ stat.label }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { markRaw, type Component } from 'vue'
import { ArrowRight, CreditCard, Headphones, ShieldCheck, Sparkles, Truck } from 'lucide-vue-next'

const stats = [
  { value: '5+', label: "Années d'expérience" },
  { value: '10 000+', label: 'Clients satisfaits' },
  { value: '2 500+', label: 'Produits disponibles' },
  { value: '4,9/5', label: 'Note moyenne' },
]

const features: { icon: Component; title: string; description: string; tint: string }[] = [
  {
    icon: markRaw(ShieldCheck),
    title: 'Achat protégé',
    description: "Vos données et vos commandes sont sécurisées de bout en bout, du panier à la livraison.",
    tint: 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400',
  },
  {
    icon: markRaw(CreditCard),
    title: 'Paiement flexible',
    description: 'Mobile money, carte bancaire ou paiement à la livraison : vous choisissez.',
    tint: 'bg-brand-500/12 text-brand-600 dark:text-brand-300',
  },
  {
    icon: markRaw(Truck),
    title: 'Livraison suivie',
    description: 'Expédition sous 24 h et suivi de votre colis jusqu’à votre porte.',
    tint: 'bg-sky-500/12 text-sky-600 dark:text-sky-400',
  },
  {
    icon: markRaw(Headphones),
    title: 'Support 7j/7',
    description: 'Une question, un souci ? Notre équipe répond tous les jours de la semaine.',
    tint: 'bg-amber-500/12 text-amber-600 dark:text-amber-400',
  },
]
</script>

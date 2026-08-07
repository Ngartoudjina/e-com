<template>
  <section class="mesh-bg grain relative overflow-hidden pt-28 pb-16 sm:pt-32 lg:pt-40 lg:pb-24">
    <!-- Halos décoratifs -->
    <div class="pointer-events-none absolute -left-32 top-24 size-[28rem] rounded-full bg-brand-400/20 blur-[120px]" />
    <div class="pointer-events-none absolute -right-24 bottom-0 size-[26rem] rounded-full bg-fuchsia-400/15 blur-[120px]" />

    <div class="container-x">
      <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_1fr] lg:gap-8">
        <!-- Colonne texte -->
        <div class="relative z-10 text-center lg:text-left">
          <span v-reveal class="eyebrow">
            <Sparkles class="size-3.5" />
            Grande vente — jusqu'à −70 %
          </span>

          <h1 v-reveal="80" class="display-1 mt-6">
            Découvrez nos<br class="hidden sm:block" />
            <span class="text-gradient">meilleures offres</span>
          </h1>

          <p v-reveal="160" class="mx-auto mt-6 max-w-lg text-lg leading-relaxed text-muted-foreground lg:mx-0">
            Une sélection exigeante de produits tendance, livrés rapidement partout,
            avec un paiement 100 % sécurisé.
          </p>

          <div v-reveal="240" class="mt-9 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center lg:justify-start">
            <button
              type="button"
              @click="$router.push('/product')"
              class="group inline-flex h-14 items-center justify-center gap-2.5 rounded-2xl bg-primary px-8 text-base font-semibold text-primary-foreground shadow-[var(--shadow-brand)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_40px_oklch(0.558_0.229_302_/_0.4)] active:translate-y-0"
            >
              <ShoppingBag class="size-5" />
              Acheter maintenant
              <ChevronRight class="size-4 transition-transform duration-300 group-hover:translate-x-1" />
            </button>

            <button
              type="button"
              @click="$router.push('/affiliation')"
              class="inline-flex h-14 items-center justify-center rounded-2xl border border-border bg-card/70 px-8 text-base font-semibold backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:bg-card"
            >
              Devenir affilié
            </button>
          </div>

          <!-- Preuve sociale -->
          <div v-reveal="320" class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4 lg:justify-start">
            <div class="flex items-center gap-3">
              <div class="flex -space-x-2.5">
                <img
                  v-for="avatar in avatars"
                  :key="avatar"
                  :src="avatar"
                  alt=""
                  class="size-9 rounded-full object-cover ring-2 ring-background"
                />
                <span class="flex size-9 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground ring-2 ring-background">
                  +10k
                </span>
              </div>
              <div class="text-left">
                <div class="flex items-center gap-0.5">
                  <Star v-for="i in 5" :key="i" class="size-3.5 fill-amber-400 text-amber-400" />
                </div>
                <p class="text-xs text-muted-foreground">Clients satisfaits</p>
              </div>
            </div>

            <div class="hidden h-10 w-px bg-border sm:block" />

            <div v-for="stat in stats" :key="stat.label" class="text-left">
              <p class="text-xl font-extrabold tracking-tight">{{ stat.value }}</p>
              <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
            </div>
          </div>
        </div>

        <!-- Colonne visuel -->
        <div v-reveal="120" class="relative mx-auto w-full max-w-md lg:max-w-none">
          <div class="relative aspect-square overflow-hidden rounded-[2rem] bg-muted shadow-[var(--shadow-float)] ring-1 ring-border">
            <Transition name="slide-fade" mode="out-in">
              <img
                :key="currentImageIndex"
                :src="images[currentImageIndex]"
                :alt="`Produit en vedette ${currentImageIndex + 1}`"
                class="size-full object-cover"
              />
            </Transition>

            <!-- Dégradé bas pour asseoir les contrôles -->
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/45 to-transparent" />

            <!-- Indicateurs -->
            <div class="absolute inset-x-0 bottom-5 flex items-center justify-center gap-2">
              <button
                v-for="(_, index) in images"
                :key="index"
                type="button"
                :aria-label="`Voir le visuel ${index + 1}`"
                @click="goTo(index)"
                class="h-1.5 rounded-full transition-all duration-500"
                :class="index === currentImageIndex ? 'w-7 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80'"
              />
            </div>
          </div>

          <!-- Carte flottante : livraison -->
          <div class="glass absolute -left-3 top-8 hidden items-center gap-3 rounded-2xl px-4 py-3 shadow-[var(--shadow-lift)] sm:flex lg:-left-8" style="animation: float-y 6s ease-in-out infinite">
            <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/12 text-emerald-600">
              <Truck class="size-5" />
            </span>
            <span>
              <span class="block text-sm font-semibold">Livraison rapide</span>
              <span class="block text-xs text-muted-foreground">24–72 h</span>
            </span>
          </div>

          <!-- Carte flottante : paiement -->
          <div class="glass absolute -right-3 bottom-10 hidden items-center gap-3 rounded-2xl px-4 py-3 shadow-[var(--shadow-lift)] sm:flex lg:-right-6" style="animation: float-y 7s ease-in-out 1.2s infinite">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-500/12 text-brand-600">
              <ShieldCheck class="size-5" />
            </span>
            <span>
              <span class="block text-sm font-semibold">Paiement sécurisé</span>
              <span class="block text-xs text-muted-foreground">Mobile money & carte</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bandeau défilant d'arguments -->
    <div class="fade-x mt-16 overflow-hidden border-y border-border/70 py-4 lg:mt-24">
      <div class="marquee gap-10">
        <span
          v-for="(perk, index) in [...perks, ...perks]"
          :key="index"
          class="flex shrink-0 items-center gap-2.5 text-sm font-medium text-muted-foreground"
        >
          <component :is="perk.icon" class="size-4 text-primary" />
          {{ perk.label }}
          <span class="ml-8 text-border">◆</span>
        </span>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { markRaw, onBeforeUnmount, onMounted, ref } from 'vue'
import {
  BadgePercent,
  ChevronRight,
  Headphones,
  RefreshCw,
  ShieldCheck,
  ShoppingBag,
  Sparkles,
  Star,
  Truck,
} from 'lucide-vue-next'

const images = ['/head1.png', '/head2.png', '/head3.png', '/head4.png', '/head5.png', '/head6.png']
const avatars = ['/ass.jpeg', '/akk.jpeg', '/imglog.jpeg']

const stats = [
  { value: '2 500+', label: 'Produits' },
  { value: '4,9/5', label: 'Note moyenne' },
]

const perks = [
  { icon: markRaw(Truck), label: 'Livraison offerte dès 50 000 F' },
  { icon: markRaw(ShieldCheck), label: 'Paiement 100 % sécurisé' },
  { icon: markRaw(RefreshCw), label: 'Retours gratuits sous 14 jours' },
  { icon: markRaw(Headphones), label: 'Support 7j/7' },
  { icon: markRaw(BadgePercent), label: 'Nouvelles offres chaque semaine' },
]

const currentImageIndex = ref(0)
let interval: ReturnType<typeof setInterval> | undefined

const start = () => {
  interval = setInterval(() => {
    currentImageIndex.value = (currentImageIndex.value + 1) % images.length
  }, 4000)
}

// Un clic sur un indicateur relance le compte à rebours plutôt que de couper court au visuel choisi.
const goTo = (index: number) => {
  currentImageIndex.value = index
  clearInterval(interval)
  start()
}

onMounted(start)
onBeforeUnmount(() => clearInterval(interval))
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 0.6s var(--ease-out-expo), transform 0.6s var(--ease-out-expo);
}
.slide-fade-enter-from {
  opacity: 0;
  transform: scale(1.06) translateX(24px);
}
.slide-fade-leave-to {
  opacity: 0;
  transform: scale(0.98) translateX(-24px);
}
</style>

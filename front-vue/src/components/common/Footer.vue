<template>
  <footer class="relative overflow-hidden border-t border-border bg-muted/40">
    <div class="container-x pb-10 pt-16 lg:pt-20">
      <!-- Newsletter -->
      <section
        v-reveal
        class="relative overflow-hidden rounded-3xl bg-neutral-950 p-8 text-white sm:p-10 lg:p-14 dark:bg-card dark:ring-1 dark:ring-border"
      >
        <!-- Halos -->
        <div class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-brand-500/35 blur-[90px]" />
        <div class="pointer-events-none absolute -bottom-24 -left-10 size-72 rounded-full bg-fuchsia-500/25 blur-[90px]" />

        <div class="relative grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
          <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-white/85">
              <Mail class="size-3.5" />
              Newsletter
            </span>
            <h2 class="mt-5 text-3xl font-extrabold tracking-tight sm:text-4xl">
              −15 % sur votre<br class="hidden sm:block" />
              première commande
            </h2>
            <p class="mt-4 max-w-md leading-relaxed text-white/65">
              Nouveautés, ventes privées et bons plans. Un e-mail par semaine, pas plus.
            </p>
          </div>

          <form class="w-full" @submit.prevent="handleSubscribe">
            <div class="flex flex-col gap-3 sm:flex-row">
              <div class="relative flex-1">
                <input
                  v-model="email"
                  type="email"
                  placeholder="votre@email.com"
                  aria-label="Adresse e-mail"
                  class="h-14 w-full rounded-2xl border border-white/15 bg-white/10 px-5 text-base text-white outline-none backdrop-blur transition-colors placeholder:text-white/40 focus:border-white/40 focus:bg-white/15"
                />
              </div>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="inline-flex h-14 shrink-0 items-center justify-center gap-2 rounded-2xl bg-white px-7 text-base font-semibold text-neutral-900 transition-all duration-300 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
              >
                <Loader2 v-if="isSubmitting" class="size-4 animate-spin" />
                <span>{{ isSubmitting ? 'Envoi…' : "S'abonner" }}</span>
                <ArrowRight v-if="!isSubmitting" class="size-4" />
              </button>
            </div>

            <p
              v-if="subscribeStatus"
              class="mt-3 flex items-center gap-2 text-sm"
              :class="subscribeError ? 'text-rose-300' : 'text-emerald-300'"
              role="status"
            >
              <component :is="subscribeError ? TriangleAlert : CheckCircle2" class="size-4 shrink-0" />
              {{ subscribeStatus }}
            </p>

            <p class="mt-4 text-xs leading-relaxed text-white/45">
              Désinscription en un clic. Voir notre
              <a href="#" class="underline underline-offset-2 hover:text-white/70">politique de confidentialité</a>.
            </p>
          </form>
        </div>
      </section>

      <!-- Liens -->
      <div class="mt-14 grid gap-10 md:grid-cols-[1.4fr_repeat(3,1fr)] lg:gap-14">
        <div>
          <div class="flex items-center gap-2.5">
            <img src="/logo.jpg" alt="" class="size-9 rounded-xl object-cover ring-1 ring-border" />
            <span class="text-lg font-extrabold tracking-tight">
              GOLD<span class="text-gradient">SHOP</span>
            </span>
          </div>
          <p class="mt-4 max-w-xs text-sm leading-relaxed text-muted-foreground">
            Les dernières technologies et produits tendance, livrés rapidement et payés en toute sécurité.
          </p>

          <div class="mt-6 flex items-center gap-2">
            <a
              v-for="social in socials"
              :key="social.label"
              :href="social.href"
              :aria-label="social.label"
              class="inline-flex size-10 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:text-primary"
            >
              <component :is="social.icon" class="size-[18px]" />
            </a>
          </div>
        </div>

        <!-- Colonnes de liens : accordéon sur mobile, liste sur desktop -->
        <div v-for="column in columns" :key="column.title" class="border-b border-border pb-3 md:border-0 md:pb-0">
          <button
            type="button"
            class="flex w-full items-center justify-between py-2 text-left md:pointer-events-none md:py-0"
            :aria-expanded="openColumn === column.title"
            @click="openColumn = openColumn === column.title ? null : column.title"
          >
            <span class="text-sm font-bold uppercase tracking-[0.1em]">{{ column.title }}</span>
            <ChevronDown
              class="size-4 text-muted-foreground transition-transform duration-300 md:hidden"
              :class="openColumn === column.title ? 'rotate-180' : ''"
            />
          </button>

          <ul
            class="space-y-2.5 overflow-hidden text-sm text-muted-foreground transition-all duration-300 md:mt-5 md:max-h-none md:opacity-100"
            :class="openColumn === column.title ? 'mt-4 max-h-72 opacity-100' : 'max-h-0 opacity-0 md:max-h-none md:opacity-100'"
          >
            <li v-for="link in column.links" :key="link.label">
              <RouterLink
                v-if="link.to"
                :to="link.to"
                class="inline-block transition-colors hover:text-foreground"
              >
                {{ link.label }}
              </RouterLink>
              <a v-else href="#" class="inline-block transition-colors hover:text-foreground">{{ link.label }}</a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Contact -->
      <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 rounded-2xl border border-border bg-card px-5 py-4 text-sm">
        <a href="tel:+22959334483" class="inline-flex items-center gap-2 transition-colors hover:text-primary">
          <Phone class="size-4 text-primary" />
          (+229) 59 33 44 83
        </a>
        <a href="mailto:abelbeingar@gmail.com" class="inline-flex items-center gap-2 transition-colors hover:text-primary">
          <Mail class="size-4 text-primary" />
          abelbeingar@gmail.com
        </a>
        <span class="inline-flex items-center gap-2 text-muted-foreground">
          <ShieldCheck class="size-4 text-emerald-500" />
          Paiement sécurisé
        </span>
      </div>

      <!-- Bas de page -->
      <div class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-border pt-6 text-sm text-muted-foreground md:flex-row">
        <p>© {{ new Date().getFullYear() }} abelbeingar@codingspace. Tous droits réservés.</p>
        <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
          <a v-for="legal in legals" :key="legal" href="#" class="transition-colors hover:text-foreground">{{ legal }}</a>
        </nav>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { markRaw, ref, type Component } from 'vue'
import axios from 'axios'
import {
  ArrowRight,
  CheckCircle2,
  ChevronDown,
  Facebook,
  Globe,
  Instagram,
  Linkedin,
  Loader2,
  Mail,
  Phone,
  ShieldCheck,
  TriangleAlert,
  Twitter,
} from 'lucide-vue-next'
import { API_BASE } from '@/lib/api'

const email = ref('')
const subscribeStatus = ref<string | null>(null)
const subscribeError = ref(false)
const isSubmitting = ref(false)
const openColumn = ref<string | null>(null)

const socials: { icon: Component; label: string; href: string }[] = [
  { icon: markRaw(Facebook), label: 'Facebook', href: '#' },
  { icon: markRaw(Twitter), label: 'Twitter', href: '#' },
  { icon: markRaw(Instagram), label: 'Instagram', href: '#' },
  { icon: markRaw(Linkedin), label: 'LinkedIn', href: '#' },
  { icon: markRaw(Globe), label: 'Site web', href: '#' },
]

const columns: { title: string; links: { label: string; to?: string }[] }[] = [
  {
    title: 'Boutique',
    links: [
      { label: 'À propos', to: '/propos' },
      { label: 'Produits', to: '/product' },
      { label: 'Communauté' },
      { label: 'Avis', to: '/product' },
    ],
  },
  {
    title: 'Support',
    links: [
      { label: "Centre d'aide" },
      { label: 'Contactez-nous' },
      { label: 'Guides' },
      { label: 'Suggestions' },
    ],
  },
  {
    title: 'Liens',
    links: [
      { label: 'Nouveautés' },
      { label: 'Devenir vendeur', to: '/affiliation' },
      { label: 'Produits', to: '/product' },
      { label: 'Blog', to: '/blog' },
    ],
  },
]

const legals = ['Politique de confidentialité', "Conditions d'utilisation", 'Légal', 'Plan du site']

const handleSubscribe = async () => {
  if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    subscribeError.value = true
    subscribeStatus.value = 'Adresse e-mail invalide'
    return
  }

  isSubmitting.value = true
  try {
    const response = await axios.post(`${API_BASE}/api/subscribe`, { email: email.value })
    subscribeError.value = false
    subscribeStatus.value = response.data.message || 'Merci ! Votre inscription est confirmée.'
    email.value = ''
  } catch (error) {
    let errorMessage = "Erreur lors de l'abonnement. Veuillez réessayer."
    if (axios.isAxiosError(error)) {
      errorMessage = error.response?.data?.error || errorMessage
    }
    subscribeError.value = true
    subscribeStatus.value = errorMessage
  } finally {
    isSubmitting.value = false
  }
}
</script>

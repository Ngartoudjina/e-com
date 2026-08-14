<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="container-page pb-24">
      <div class="flex flex-wrap items-end justify-between gap-4 pt-12">
        <h1 class="t-screen-title">Votre panier</h1>
        <RouterLink to="/catalogue" class="t-body text-ink-500 transition-colors hover:text-ink-900">
          Continuer mes achats ›
        </RouterLink>
      </div>

      <!-- Panier vide -->
      <div v-if="!articles.length" class="mt-12 border-t border-rule pt-16 text-center">
        <p class="t-h2">Votre panier est vide</p>
        <p class="t-body-l mx-auto mt-4 max-w-md text-ink-500">
          Les pièces que vous ajoutez apparaissent ici, avec leur taille et leur coloris.
        </p>
        <RouterLink to="/catalogue" class="btn btn-lg btn-primary mt-8">Découvrir le catalogue</RouterLink>
      </div>

      <div v-else class="mt-12 grid-page border-t border-rule pt-10">
        <!-- Articles : 7 colonnes -->
        <div class="col-span-4 lg:col-span-7">
          <!-- Palier de livraison offerte -->
          <div class="flex items-center gap-4 bg-[#E4EAFF] p-4">
            <Truck class="size-5 shrink-0 text-action" />
            <div class="min-w-0 flex-1">
              <p class="t-body text-action">
                {{ livraisonOfferte ? 'Livraison offerte — palier atteint' : `Plus que ${formatPrix(resteAvantFranco)} pour la livraison offerte` }}
              </p>
              <div class="mt-2 h-px bg-action/25">
                <div class="h-px bg-action transition-all duration-[320ms]" :style="{ width: `${progressionFranco}%` }" />
              </div>
            </div>
            <p data-numeric class="t-small shrink-0 text-action">
              {{ formatPrix(sousTotal) }} / {{ formatPrix(SEUIL_FRANCO) }}
            </p>
          </div>

          <!-- En-têtes de colonnes -->
          <div class="mt-10 hidden border-b border-rule pb-3 lg:grid lg:grid-cols-[1fr_auto_auto_auto] lg:items-baseline lg:gap-8">
            <p class="t-label text-ink-500">Article</p>
            <p class="t-label text-ink-500">Quantité</p>
            <p class="t-label w-24 text-right text-ink-500">Total</p>
            <span class="w-8" />
          </div>

          <!-- Lignes -->
          <article
            v-for="article in articles"
            :key="ligneId(article)"
            class="grid grid-cols-[96px_1fr] gap-4 border-b border-rule py-8 lg:grid-cols-[96px_1fr_auto_auto_auto] lg:items-start lg:gap-8"
          >
            <RouterLink :to="`/produit/${article.id}`" class="block">
              <div class="aspect-[3/4] overflow-hidden bg-rule-soft">
                <img
                  v-if="article.mediaUrl"
                  :src="article.mediaUrl"
                  :alt="article.name"
                  class="size-full object-cover"
                />
              </div>
            </RouterLink>

            <div class="min-w-0">
              <h2 class="font-display text-[22px] leading-tight text-ink-900">
                <RouterLink :to="`/produit/${article.id}`" class="hover:underline">{{ article.name }}</RouterLink>
              </h2>
              <p class="t-small mt-2 text-ink-500">
                {{ article.selectedColor }} · Taille {{ article.selectedSize }}
              </p>
              <p class="t-small mt-1 text-ink-500">Réf. {{ reference(article) }}</p>

              <p v-if="statut(article)" class="t-small mt-3 flex items-center gap-2" :class="statut(article)!.classe">
                <span aria-hidden="true">●</span>{{ statut(article)!.libelle }}
              </p>

              <div class="mt-4 flex flex-wrap gap-6">
                <button type="button" class="t-small text-ink-900 underline underline-offset-4 hover:text-ink-500">
                  Déplacer vers les favoris
                </button>
                <RouterLink :to="`/produit/${article.id}`" class="t-small text-ink-900 underline underline-offset-4 hover:text-ink-500">
                  Modifier la taille
                </RouterLink>
              </div>

              <!-- Quantité et total, empilés sous le nom en mobile -->
              <div class="mt-6 flex items-center justify-between gap-4 lg:hidden">
                <QuantityStepper
                  :quantite="article.quantity"
                  @modifier="(q) => cartStore.updateQuantity(article.id, q, article.selectedSize, article.selectedColor)"
                />
                <p data-numeric class="t-price">{{ formatPrix(totalLigne(article)) }}</p>
                <button type="button" class="text-ink-500 hover:text-ink-900" :aria-label="`Retirer ${article.name}`" @click="cartStore.removeFromCart(article.id, article.selectedSize, article.selectedColor)">
                  <X class="size-4" />
                </button>
              </div>
            </div>

            <div class="hidden lg:block">
              <QuantityStepper
                :quantite="article.quantity"
                @modifier="(q) => cartStore.updateQuantity(article.id, q, article.selectedSize, article.selectedColor)"
              />
            </div>

            <div class="hidden w-24 text-right lg:block">
              <p data-numeric class="t-price">{{ formatPrix(totalLigne(article)) }}</p>
              <p
                v-if="article.originalPrice && article.originalPrice > article.price"
                data-numeric
                class="t-small text-ink-300 line-through"
              >
                {{ formatPrix(article.originalPrice * article.quantity) }}
              </p>
            </div>

            <button
              type="button"
              class="hidden w-8 justify-self-end text-ink-500 transition-colors hover:text-ink-900 lg:block"
              :aria-label="`Retirer ${article.name}`"
              @click="cartStore.removeFromCart(article.id, article.selectedSize, article.selectedColor)"
            >
              <X class="size-4" />
            </button>
          </article>
        </div>

        <!-- Récapitulatif : 5 colonnes -->
        <aside class="col-span-4 mt-10 lg:col-span-5 lg:mt-0">
          <div class="border border-rule bg-surface p-6 lg:p-8">
            <h2 class="t-label text-ink-500">Récapitulatif</h2>

            <dl class="mt-6 space-y-4">
              <div class="flex items-baseline justify-between gap-4">
                <dt class="t-body text-ink-700">Sous-total ({{ nombreArticles }} article{{ nombreArticles > 1 ? 's' : '' }})</dt>
                <dd data-numeric class="t-price">{{ formatPrix(sousTotal) }}</dd>
              </div>
              <div v-if="remise > 0" class="flex items-baseline justify-between gap-4">
                <dt class="t-body text-ink-700">Remise</dt>
                <dd data-numeric class="t-price text-error">−{{ formatPrix(remise) }}</dd>
              </div>
              <div class="flex items-baseline justify-between gap-4">
                <dt class="t-body text-ink-700">Livraison standard</dt>
                <dd class="t-body" :class="livraisonOfferte ? 'text-success' : 'text-ink-900'">
                  {{ livraisonOfferte ? 'Offerte' : formatPrix(FRAIS_PORT) }}
                </dd>
              </div>
              <div class="flex items-baseline justify-between gap-4">
                <dt class="t-body text-ink-700">TVA incluse (20 %)</dt>
                <dd data-numeric class="t-price text-ink-700">{{ formatPrix(tva) }}</dd>
              </div>
            </dl>

            <div class="mt-8 flex items-baseline justify-between gap-4 border-t border-rule pt-6">
              <p class="t-h3">Total</p>
              <p data-numeric class="font-display text-[34px] leading-none">{{ formatPrix(total) }}</p>
            </div>
            <p class="t-small mt-2 text-right text-ink-500">ou {{ formatFractionne(total) }}</p>

            <!--
              Seule action bleue du site : le système réserve cette couleur à
              l'engagement financier.
            -->
            <RouterLink to="/checkout" class="btn btn-lg btn-action mt-6 w-full">
              Passer commande
            </RouterLink>
            <p class="t-small mt-3 text-center text-ink-500">Paiement sécurisé · 3-D Secure</p>
          </div>

          <!-- Code promotionnel -->
          <div class="mt-4 border border-rule bg-surface p-6 lg:p-8">
            <h2 class="t-label text-ink-500">Code promotionnel</h2>
            <form class="mt-4 flex" @submit.prevent="appliquerCode">
              <label class="sr-only" for="code-promo">Code promotionnel</label>
              <input
                id="code-promo"
                v-model="codePromo"
                class="field flex-1"
                style="border-radius: var(--radius-2) 0 0 var(--radius-2)"
                placeholder="Saisir un code"
              />
              <button
                type="submit"
                class="btn btn-primary shrink-0"
                style="border-radius: 0 var(--radius-2) var(--radius-2) 0"
              >
                Appliquer
              </button>
            </form>
            <p
              v-if="messageCode"
              class="t-small mt-4 flex items-center gap-2 p-3"
              :class="codeValide ? 'bg-success/10 text-success' : 'bg-error/10 text-error'"
              role="status"
            >
              <Check v-if="codeValide" class="size-4 shrink-0" />
              <X v-else class="size-4 shrink-0" />
              {{ messageCode }}
            </p>
          </div>

          <!-- Réassurance, sur fond encre comme la maquette -->
          <ul class="mt-4 space-y-4 bg-ink-900 p-6 text-white lg:p-8">
            <li class="flex items-center gap-3">
              <ShieldCheck class="size-4 shrink-0" />
              <span class="t-small">Paiement chiffré, aucune donnée conservée</span>
            </li>
            <li class="flex items-center gap-3">
              <RotateCcw class="size-4 shrink-0" />
              <span class="t-small">Retour gratuit sous 30 jours</span>
            </li>
          </ul>
        </aside>
      </div>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, RotateCcw, ShieldCheck, Truck, X } from 'lucide-vue-next'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import QuantityStepper from '@/components/cart/QuantityStepper.vue'
import { formatPrix, formatFractionne } from '@/lib/format'
import { useCartStore } from '@/stores/cart'
import type { ProductWithDetails } from '@/types'

const cartStore = useCartStore()

/** Seuil de franco et frais de port, tels qu'annoncés par le bandeau du site. */
const SEUIL_FRANCO = 150
const FRAIS_PORT = 6.9

const codePromo = ref('')
const messageCode = ref<string | null>(null)
const codeValide = ref(false)
const remise = ref(0)

const articles = computed(() => cartStore.cartItems)
const nombreArticles = computed(() => cartStore.totalItems)

const totalLigne = (article: ProductWithDetails) => article.price * (article.quantity || 1)

const sousTotal = computed(() => articles.value.reduce((somme, a) => somme + totalLigne(a), 0))

const livraisonOfferte = computed(() => sousTotal.value >= SEUIL_FRANCO)
const resteAvantFranco = computed(() => Math.max(0, SEUIL_FRANCO - sousTotal.value))
const progressionFranco = computed(() => Math.min(100, (sousTotal.value / SEUIL_FRANCO) * 100))

const total = computed(() =>
  Math.max(0, sousTotal.value - remise.value + (livraisonOfferte.value ? 0 : FRAIS_PORT))
)

/** Les prix sont TTC : la TVA est extraite du total, pas ajoutée. */
const tva = computed(() => total.value - total.value / 1.2)

/** Référence lisible, à défaut d'être fournie par l'API. */
const reference = (article: ProductWithDetails) =>
  `GS-${article.name.slice(0, 3).toUpperCase()}-${(article.selectedSize || 'U').toUpperCase()}`

const ligneId = (article: ProductWithDetails) =>
  `${article.id}:${article.selectedSize}:${article.selectedColor}`

const statut = (article: ProductWithDetails) => {
  const stock = article.stock ?? 0
  if (stock <= 0) return { libelle: 'Épuisé', classe: 'text-error' }
  if (stock <= 3) return { libelle: `Plus que ${stock} pièces`, classe: 'text-warning' }
  return { libelle: 'En stock', classe: 'text-success' }
}

const appliquerCode = () => {
  const code = codePromo.value.trim().toUpperCase()

  if (!code) {
    codeValide.value = false
    messageCode.value = 'Saisissez un code.'
    return
  }

  // Faute d'endpoint dédié, un seul code de démonstration est reconnu.
  if (code === 'ARCHIVES20') {
    remise.value = sousTotal.value * 0.2
    codeValide.value = true
    messageCode.value = 'ARCHIVES20 appliqué — 20 % sur les archives'
    return
  }

  remise.value = 0
  codeValide.value = false
  messageCode.value = 'Ce code n’est pas valide.'
}
</script>

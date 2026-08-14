<template>
  <div v-if="chargement" class="space-y-4">
    <div class="skeleton h-14" />
    <div class="grid gap-4 xl:grid-cols-[1.6fr_1fr]">
      <div class="skeleton h-96" />
      <div class="skeleton h-64" />
    </div>
  </div>

  <div v-else-if="!commande" class="border border-rule bg-surface p-12 text-center">
    <p class="t-h3">Commande introuvable</p>
    <RouterLink to="/admin/commandes" class="btn btn-secondary mt-6">Retour aux commandes</RouterLink>
  </div>

  <div v-else>
    <!-- Barre de commande : identité à gauche, actions à droite. -->
    <div class="flex flex-wrap items-center gap-4 border border-rule bg-surface px-5 py-4">
      <RouterLink to="/admin/commandes" class="t-small text-ink-500 hover:text-ink-900">Commandes</RouterLink>
      <span class="t-small text-ink-300" aria-hidden="true">/</span>
      <p data-numeric class="t-h3">#{{ commande.reference }}</p>

      <span class="t-label px-2 py-1" :class="badgeEtat">{{ etat.libelle }}</span>
      <p class="t-small text-ink-500">{{ formatDateLongue(commande.placedAt) }}</p>

      <div class="ml-auto flex flex-wrap gap-2">
        <button
          v-for="action in actions"
          :key="action.statut"
          type="button"
          class="btn btn-sm"
          :class="action.principale ? 'btn-primary' : 'btn-secondary'"
          :disabled="envoi"
          @click="changerStatut(action.statut, action.libelle)"
        >
          {{ action.libelle }}
        </button>

        <p v-if="!actions.length" class="t-small self-center text-ink-500">
          Aucune action disponible dans cet état.
        </p>
      </div>
    </div>

    <p v-if="erreurAction" class="t-small mt-3 bg-error/10 p-3 text-error" role="alert">{{ erreurAction }}</p>

    <div class="mt-4 grid gap-4 xl:grid-cols-[1.6fr_1fr]">
      <!-- Articles et totaux -->
      <section class="border border-rule bg-surface">
        <div class="flex items-baseline justify-between gap-4 border-b border-rule p-5">
          <h2 class="t-h3">Articles · {{ commande.itemCount }}</h2>
        </div>

        <ul>
          <li
            v-for="(ligne, index) in commande.items"
            :key="ligne.id"
            class="flex items-center gap-4 px-5 py-4"
            :class="index > 0 ? 'border-t border-rule' : ''"
          >
            <span class="size-12 shrink-0 overflow-hidden bg-rule-soft">
              <img v-if="ligne.mediaUrl" :src="ligne.mediaUrl" :alt="ligne.name" class="size-full object-cover" />
            </span>
            <span class="min-w-0 flex-1">
              <span class="block truncate text-[13px] text-ink-900">{{ ligne.name }}</span>
              <span class="block truncate text-[11px] text-ink-500">
                {{ [ligne.reference, ligne.color, ligne.size].filter(Boolean).join(' · ') }}
              </span>
            </span>
            <span data-numeric class="shrink-0 text-[13px] text-ink-500">× {{ ligne.quantity }}</span>
            <span data-numeric class="w-24 shrink-0 text-right text-[13px]">{{ formatPrix(ligne.lineTotal) }}</span>
          </li>
        </ul>

        <dl class="space-y-3 border-t border-rule p-5">
          <div class="flex items-baseline justify-between gap-4">
            <dt class="text-[13px] text-ink-700">Sous-total</dt>
            <dd data-numeric class="text-[13px]">{{ formatPrix(commande.subtotal) }}</dd>
          </div>
          <div v-if="commande.discount > 0" class="flex items-baseline justify-between gap-4">
            <dt class="text-[13px] text-ink-700">
              Remise<span v-if="commande.promoCode"> {{ commande.promoCode }}</span>
            </dt>
            <dd data-numeric class="text-[13px] text-error">−{{ formatPrix(commande.discount) }}</dd>
          </div>
          <div class="flex items-baseline justify-between gap-4">
            <dt class="text-[13px] text-ink-700">Livraison</dt>
            <dd class="text-[13px]" :class="commande.shipping === 0 ? 'text-success' : ''">
              {{ commande.shipping === 0 ? 'Offerte' : formatPrix(commande.shipping) }}
            </dd>
          </div>
        </dl>

        <div class="flex items-baseline justify-between gap-4 border-t border-rule p-5">
          <p class="t-h3">Total</p>
          <p data-numeric class="font-display text-[24px] leading-none">{{ formatPrix(commande.total) }}</p>
        </div>
      </section>

      <!-- Colonne latérale -->
      <div class="space-y-4">
        <section class="border border-rule bg-surface p-5">
          <h2 class="t-label text-ink-500">Client</h2>
          <p class="mt-3 text-[13px] text-ink-900">{{ commande.shippingAddress?.name || '—' }}</p>
          <p class="mt-2 text-[13px] text-ink-700">{{ commande.email }}</p>
          <p v-if="commande.phone" class="text-[13px] text-ink-700">{{ commande.phone }}</p>
          <p class="t-small mt-3 text-ink-500">
            {{ commande.itemCount }} article{{ commande.itemCount > 1 ? 's' : '' }} sur cette commande
          </p>
        </section>

        <section class="border border-rule bg-surface p-5">
          <h2 class="t-label text-ink-500">Livraison</h2>
          <address class="mt-3 not-italic text-[13px] text-ink-900">
            <span class="block">{{ commande.shippingAddress?.address || '—' }}</span>
            <span class="block">
              {{ commande.shippingAddress?.postalCode }} {{ commande.shippingAddress?.city }}
            </span>
            <span class="block">{{ commande.shippingAddress?.country }}</span>
          </address>
          <p class="t-small mt-4 border-t border-rule pt-4 text-ink-700">
            {{ commande.shippingMethod === 'express' ? 'Express' : 'Standard' }}
            <span v-if="commande.shipping === 0" class="text-success"> · offerte</span>
          </p>
        </section>

        <!-- Journal : reconstitué à partir des horodatages enregistrés. -->
        <section class="border border-rule bg-surface p-5">
          <h2 class="t-label text-ink-500">Journal</h2>
          <ol class="mt-4 space-y-4">
            <li v-for="(evenement, index) in journal" :key="evenement.libelle" class="flex gap-3">
              <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="index === 0 ? 'bg-action' : 'bg-ink-900'" />
              <span class="min-w-0">
                <span class="block text-[13px] text-ink-900">{{ evenement.libelle }}</span>
                <span class="block text-[11px] text-ink-500">{{ formatDateLongue(evenement.date) }}</span>
              </span>
            </li>
          </ol>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { ACTIONS_COMMANDE, ETATS_COMMANDE, formatDateLongue } from '@/lib/commandes'
import type { Commande } from '@/lib/commandes'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const toastStore = useToastStore()

const commande = ref<Commande | null>(null)
const chargement = ref(true)
const envoi = ref(false)
const erreurAction = ref<string | null>(null)

const etat = computed(() =>
  ETATS_COMMANDE[commande.value?.status ?? ''] ?? { libelle: '—', classe: 'text-ink-500' }
)

const badgeEtat = computed(() => {
  const statut = commande.value?.status
  if (statut === 'delivered' || statut === 'paid') return 'bg-success/12 text-success'
  if (statut === 'cancelled' || statut === 'refunded') return 'bg-rule-soft text-ink-500'
  if (statut === 'shipped') return 'bg-[#E4EAFF] text-action'
  return 'bg-[#F5E6C8] text-warning'
})

const actions = computed(() => ACTIONS_COMMANDE[commande.value?.status ?? ''] ?? [])

/**
 * Journal des étapes franchies.
 * Il est reconstitué depuis les horodatages de la commande : aucune table
 * d'événements n'existe, et en inventer un fil serait mentir sur l'historique.
 */
const journal = computed(() => {
  if (!commande.value) return []

  const etapes = [
    { libelle: 'Commande passée', date: commande.value.placedAt },
    { libelle: 'Expédiée', date: commande.value.shippedAt },
    { libelle: 'Livrée', date: commande.value.deliveredAt },
    { libelle: 'Annulée', date: commande.value.cancelledAt },
  ]

  return etapes.filter((e) => e.date).reverse()
})

const charger = async () => {
  chargement.value = true
  try {
    const reponse = await api.get(`/api/orders/${route.params.reference}`)
    commande.value = reponse.data.order
  } catch (e) {
    console.error(e)
    commande.value = null
  } finally {
    chargement.value = false
  }
}

const changerStatut = async (statut: string, libelle: string) => {
  if (!commande.value) return

  envoi.value = true
  erreurAction.value = null
  try {
    const reponse = await api.patch(`/api/admin/orders/${commande.value.reference}/status`, { status: statut })
    commande.value = reponse.data.order
    toastStore.success(`${libelle} — fait.`)
  } catch (e: any) {
    // Le serveur contraint les transitions : son refus fait foi.
    erreurAction.value = e?.response?.data?.error ?? 'La mise à jour a échoué.'
  } finally {
    envoi.value = false
  }
}

onMounted(charger)
</script>

<template>
  <div class="space-y-4">
    <!-- ---------------------------------------------- Réglages -->
    <section class="border border-rule bg-surface">
      <div class="border-b border-rule p-5">
        <h2 class="t-h3">Livraison &amp; affichage</h2>
        <p class="t-small mt-1 text-ink-500">
          Ces valeurs s’appliquent au site et au calcul des commandes. Aucun redéploiement n’est nécessaire.
        </p>
      </div>

      <div v-if="chargement" class="space-y-3 p-5">
        <div v-for="i in 4" :key="i" class="skeleton h-11" />
      </div>

      <form v-else class="space-y-6 p-5" @submit.prevent="enregistrer">
        <div class="grid gap-5 md:grid-cols-3">
          <label class="block">
            <span class="t-label text-ink-500">Livraison offerte à partir de (€)</span>
            <input v-model.number="formulaire.freeShippingThreshold" type="number" min="0" step="1" data-numeric class="field mt-3" />
            <span class="t-small mt-2 block text-ink-500">Défaut : {{ formatPrix(defauts.freeShippingThreshold) }}</span>
          </label>

          <label class="block">
            <span class="t-label text-ink-500">Port standard (€)</span>
            <input v-model.number="formulaire.shippingStandard" type="number" min="0" step="0.1" data-numeric class="field mt-3" />
            <span class="t-small mt-2 block text-ink-500">Défaut : {{ formatPrix(defauts.shippingStandard) }}</span>
          </label>

          <label class="block">
            <span class="t-label text-ink-500">Port express (€)</span>
            <input v-model.number="formulaire.shippingExpress" type="number" min="0" step="0.1" data-numeric class="field mt-3" />
            <span class="t-small mt-2 block text-ink-500">Défaut : {{ formatPrix(defauts.shippingExpress) }}</span>
          </label>

          <label class="block">
            <span class="t-label text-ink-500">TVA (%)</span>
            <!-- Saisie en pourcentage, stockée en fraction : 20 → 0,20. -->
            <input v-model.number="tvaPourcent" type="number" min="0" max="100" step="0.1" data-numeric class="field mt-3" />
            <span class="t-small mt-2 block text-ink-500">Enregistrée sous forme de fraction</span>
          </label>

          <label class="block">
            <span class="t-label text-ink-500">Retours acceptés (jours)</span>
            <input v-model.number="formulaire.returnDays" type="number" min="0" max="365" data-numeric class="field mt-3" />
          </label>
        </div>

        <div>
          <span class="t-label text-ink-500">Bandeau d’annonces</span>
          <p class="t-small mt-1 text-ink-500">Trois messages au plus s’affichent sur grand écran, un seul sur mobile.</p>

          <div class="mt-3 space-y-2">
            <div v-for="(_, index) in formulaire.announcements" :key="index" class="flex gap-2">
              <input v-model="formulaire.announcements[index]" class="field flex-1" maxlength="120" />
              <button
                type="button"
                class="btn btn-icon shrink-0"
                :aria-label="`Retirer le message ${index + 1}`"
                @click="formulaire.announcements.splice(index, 1)"
              >
                <X class="size-4" />
              </button>
            </div>
          </div>

          <button
            v-if="formulaire.announcements.length < 5"
            type="button"
            class="btn btn-sm btn-secondary mt-3"
            @click="formulaire.announcements.push('')"
          >
            Ajouter un message
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-rule pt-5">
          <button type="submit" class="btn btn-primary" :disabled="envoi">
            {{ envoi ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
          <p v-if="erreur" class="t-small text-error" role="alert">{{ erreur }}</p>
        </div>
      </form>
    </section>

    <!-- ---------------------------------------------- Codes promotionnels -->
    <section class="border border-rule bg-surface">
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-rule p-5">
        <div>
          <h2 class="t-h3">Codes promotionnels</h2>
          <p class="t-small mt-1 text-ink-500">La remise est calculée par le serveur à la commande.</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary" @click="ouvrirCreation">Nouveau code</button>
      </div>

      <!-- Formulaire de création ou de modification -->
      <form v-if="edition" class="grid gap-4 border-b border-rule bg-rule-soft/40 p-5 md:grid-cols-5" @submit.prevent="enregistrerPromo">
        <label class="block">
          <span class="t-label text-ink-500">Code</span>
          <input v-model="edition.code" class="field mt-2 uppercase" :disabled="!!edition.id" maxlength="40" />
        </label>
        <label class="block">
          <span class="t-label text-ink-500">Type</span>
          <select v-model="edition.type" class="field mt-2">
            <option value="percent">Pourcentage</option>
            <option value="amount">Montant fixe</option>
          </select>
        </label>
        <label class="block">
          <span class="t-label text-ink-500">Valeur</span>
          <input v-model.number="edition.value" type="number" min="0" step="0.01" data-numeric class="field mt-2" />
        </label>
        <label class="block">
          <span class="t-label text-ink-500">Minimum d’achat (€)</span>
          <input v-model.number="edition.min_subtotal" type="number" min="0" step="1" data-numeric class="field mt-2" />
        </label>
        <label class="block">
          <span class="t-label text-ink-500">Expire le</span>
          <input v-model="edition.expires_at" type="date" class="field mt-2" />
        </label>

        <label class="flex items-center gap-3 md:col-span-3">
          <input v-model="edition.active" type="checkbox" class="size-5 accent-ink-900" />
          <span class="t-body">Actif</span>
        </label>

        <div class="flex gap-2 md:col-span-2 md:justify-end">
          <button type="button" class="btn btn-sm btn-secondary" @click="edition = null">Annuler</button>
          <button type="submit" class="btn btn-sm btn-primary" :disabled="envoiPromo">
            {{ edition.id ? 'Mettre à jour' : 'Créer' }}
          </button>
        </div>

        <p v-if="erreurPromo" class="t-small text-error md:col-span-5" role="alert">{{ erreurPromo }}</p>
      </form>

      <!-- Tant que la liste n'est pas revenue, on ne prétend pas qu'elle est vide. -->
      <div v-if="chargement" class="space-y-3 p-5">
        <div v-for="i in 3" :key="i" class="skeleton h-11" />
      </div>

      <p v-else-if="!promos.length" class="t-body p-5 text-ink-500">Aucun code pour l’instant.</p>

      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-[13px]">
          <thead>
            <tr class="border-b border-rule text-left">
              <th class="t-label px-5 py-3 font-normal text-ink-500">Code</th>
              <th class="t-label px-5 py-3 font-normal text-ink-500">Remise</th>
              <th class="t-label px-5 py-3 font-normal text-ink-500">Minimum</th>
              <th class="t-label px-5 py-3 font-normal text-ink-500">Expiration</th>
              <th class="t-label px-5 py-3 font-normal text-ink-500">Utilisations</th>
              <th class="t-label px-5 py-3 font-normal text-ink-500">État</th>
              <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="promo in promos" :key="promo.id" class="border-b border-rule last:border-0">
              <td data-numeric class="px-5 py-3 text-ink-900">{{ promo.code }}</td>
              <td data-numeric class="px-5 py-3 text-ink-700">
                {{ promo.type === 'percent' ? `${promo.value} %` : formatPrix(promo.value) }}
              </td>
              <td data-numeric class="px-5 py-3 text-ink-700">
                {{ promo.minSubtotal > 0 ? formatPrix(promo.minSubtotal) : '—' }}
              </td>
              <td class="px-5 py-3 text-ink-700">{{ formatDateCourte(promo.expiresAt) }}</td>
              <td data-numeric class="px-5 py-3 text-ink-700">
                {{ promo.usedCount }}<span v-if="promo.maxUses"> / {{ promo.maxUses }}</span>
              </td>
              <td class="px-5 py-3">
                <span class="t-small" :class="promo.active ? 'text-success' : 'text-ink-500'">
                  {{ promo.active ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <button type="button" class="btn btn-sm btn-secondary" @click="ouvrirEdition(promo)">Modifier</button>
                <button type="button" class="btn btn-sm btn-danger ml-2" @click="supprimerPromo(promo)">
                  {{ promo.usedCount > 0 ? 'Désactiver' : 'Supprimer' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { X } from 'lucide-vue-next'
import { api, viderCacheApi } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { formatDateCourte } from '@/lib/commandes'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'

interface Promo {
  id: string
  code: string
  label: string | null
  type: 'percent' | 'amount'
  value: number
  minSubtotal: number
  active: boolean
  expiresAt: string | null
  maxUses: number | null
  usedCount: number
}

const toastStore = useToastStore()
const settingsStore = useSettingsStore()

const chargement = ref(true)
const envoi = ref(false)
const erreur = ref<string | null>(null)
const defauts = ref<Record<string, number>>({})

const formulaire = reactive({
  freeShippingThreshold: 0,
  shippingStandard: 0,
  shippingExpress: 0,
  vatRate: 0.2,
  returnDays: 30,
  announcements: [] as string[],
})

/** La TVA se saisit en pourcentage mais se stocke en fraction. */
const tvaPourcent = computed({
  get: () => Math.round(formulaire.vatRate * 1000) / 10,
  set: (valeur: number) => {
    formulaire.vatRate = (Number(valeur) || 0) / 100
  },
})

const promos = ref<Promo[]>([])
const edition = ref<any>(null)
const envoiPromo = ref(false)
const erreurPromo = ref<string | null>(null)

const charger = async () => {
  chargement.value = true
  try {
    const [reglages, codes] = await Promise.all([
      api.get('/api/admin/settings'),
      api.get('/api/admin/promos'),
    ])
    Object.assign(formulaire, reglages.data.settings)
    formulaire.announcements = [...(reglages.data.settings.announcements ?? [])]
    defauts.value = reglages.data.defaults ?? {}
    promos.value = codes.data.promos ?? []
  } catch (e) {
    console.error(e)
    erreur.value = 'Les réglages n’ont pas pu être chargés.'
  } finally {
    chargement.value = false
  }
}

const enregistrer = async () => {
  envoi.value = true
  erreur.value = null
  try {
    await api.put('/api/admin/settings', {
      ...formulaire,
      announcements: formulaire.announcements.filter((m) => m.trim() !== ''),
    })

    /*
     * Le cache client garde l'ancienne charge de /api/settings : sans purge,
     * le bandeau continuerait d'annoncer l'ancien seuil pendant cinq minutes.
     */
    viderCacheApi()
    settingsStore.charge = false
    await settingsStore.charger()

    toastStore.success('Réglages enregistrés.')
  } catch (e: any) {
    erreur.value = e?.response?.data?.message ?? 'L’enregistrement a échoué.'
  } finally {
    envoi.value = false
  }
}

const ouvrirCreation = () => {
  erreurPromo.value = null
  edition.value = {
    id: null,
    code: '',
    type: 'percent',
    value: 10,
    min_subtotal: 0,
    active: true,
    expires_at: '',
  }
}

const ouvrirEdition = (promo: Promo) => {
  erreurPromo.value = null
  edition.value = {
    id: promo.id,
    code: promo.code,
    type: promo.type,
    value: promo.value,
    min_subtotal: promo.minSubtotal,
    active: promo.active,
    expires_at: promo.expiresAt ? promo.expiresAt.slice(0, 10) : '',
  }
}

const enregistrerPromo = async () => {
  if (!edition.value) return

  envoiPromo.value = true
  erreurPromo.value = null

  const charge = { ...edition.value }
  if (!charge.expires_at) charge.expires_at = null
  delete charge.id

  try {
    if (edition.value.id) {
      await api.put(`/api/admin/promos/${edition.value.id}`, charge)
    } else {
      await api.post('/api/admin/promos', charge)
    }
    edition.value = null
    await charger()
    toastStore.success('Code enregistré.')
  } catch (e: any) {
    erreurPromo.value =
      e?.response?.data?.error ??
      Object.values(e?.response?.data?.errors ?? {}).flat()[0] ??
      'L’enregistrement a échoué.'
  } finally {
    envoiPromo.value = false
  }
}

const supprimerPromo = async (promo: Promo) => {
  try {
    const reponse = await api.delete(`/api/admin/promos/${promo.id}`)
    await charger()
    toastStore.success(reponse.data.message ?? 'Code supprimé.')
  } catch {
    toastStore.error('La suppression a échoué.')
  }
}

onMounted(charger)
</script>

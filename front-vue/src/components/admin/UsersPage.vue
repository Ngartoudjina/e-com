<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="onglet in onglets"
          :key="onglet.cle"
          type="button"
          class="chip"
          :aria-pressed="onglet.cle === filtre"
          @click="filtre = onglet.cle"
        >
          {{ onglet.libelle }}
          <span v-if="compteur(onglet.cle)" data-numeric class="text-ink-500">· {{ compteur(onglet.cle) }}</span>
        </button>
      </div>

      <label class="relative w-full sm:w-72">
        <span class="sr-only">Rechercher un client</span>
        <Search class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink-500" />
        <input v-model="recherche" class="field pl-11" placeholder="Nom ou adresse e-mail" />
      </label>
    </div>

    <div v-if="chargement" class="mt-6 space-y-2">
      <div v-for="i in 6" :key="i" class="skeleton h-16" />
    </div>

    <div v-else-if="erreur" class="mt-6 border border-rule bg-surface p-10 text-center">
      <p class="t-body">{{ erreur }}</p>
      <button type="button" class="btn btn-secondary mt-6" @click="charger">Réessayer</button>
    </div>

    <div v-else-if="!clientsFiltres.length" class="mt-6 border border-rule bg-surface p-12 text-center">
      <p class="t-h3">Aucun client</p>
      <p class="t-body mt-2 text-ink-500">
        {{ recherche ? 'Aucun résultat pour cette recherche.' : 'Les comptes créés apparaîtront ici.' }}
      </p>
    </div>

    <div v-else class="mt-6 overflow-x-auto border border-rule bg-surface">
      <table class="w-full min-w-[860px] text-[13px]">
        <thead>
          <tr class="border-b border-rule text-left">
            <th class="t-label px-5 py-3 font-normal text-ink-500">Client</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Inscription</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Compte</th>
            <th class="t-label px-5 py-3 text-right font-normal text-ink-500">Commandes</th>
            <th class="t-label px-5 py-3 text-right font-normal text-ink-500">Dépenses</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Droits</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="client in clientsFiltres"
            :key="client.uid"
            class="border-b border-rule last:border-0 transition-colors hover:bg-rule-soft/50"
          >
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <span
                  aria-hidden="true"
                  class="flex size-8 shrink-0 items-center justify-center border border-rule text-[11px] text-ink-700"
                >
                  {{ initiales(client) }}
                </span>
                <span class="min-w-0">
                  <span class="block truncate text-ink-900">{{ client.name || '—' }}</span>
                  <span class="block truncate text-[11px] text-ink-500">{{ client.email }}</span>
                </span>
              </div>
            </td>

            <td class="px-5 py-3 text-ink-700">{{ formatDateCourte(client.createdAt) }}</td>

            <td class="px-5 py-3">
              <span class="t-small" :class="client.emailVerified ? 'text-success' : 'text-ink-500'">
                {{ client.emailVerified ? 'Vérifié' : 'Non vérifié' }}
              </span>
              <span v-if="client.provider && client.provider !== 'password'" class="t-small block text-ink-500">
                via {{ client.provider }}
              </span>
              <span v-if="client.isAffiliate" class="t-small block text-ink-500">Affilié</span>
            </td>

            <td data-numeric class="px-5 py-3 text-right text-ink-700">{{ client.ordersCount }}</td>
            <td data-numeric class="px-5 py-3 text-right text-ink-900">{{ formatPrix(client.totalSpent) }}</td>

            <td class="px-5 py-3">
              <!--
                Le seul pouvoir réel de cet écran. Les boutons « modifier »,
                « suspendre » et « supprimer » de l'ancienne version n'étaient
                reliés à rien : mieux vaut ne rien promettre.
              -->
              <button
                type="button"
                class="btn btn-sm"
                :class="client.isAdmin ? 'btn-secondary' : 'btn-secondary'"
                :disabled="enCours === client.uid || client.uid === moi"
                :title="client.uid === moi ? 'Vous ne pouvez pas modifier vos propres droits' : undefined"
                @click="basculer(client)"
              >
                <template v-if="enCours === client.uid">…</template>
                <template v-else-if="client.uid === moi">Vous · admin</template>
                <template v-else-if="client.isAdmin">Retirer l’admin</template>
                <template v-else>Nommer admin</template>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Search } from 'lucide-vue-next'
import { api, viderCacheApi } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { formatDateCourte } from '@/lib/commandes'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

interface Client {
  uid: string
  email: string
  name: string | null
  isAdmin: boolean
  isAffiliate: boolean
  emailVerified: boolean
  provider: string | null
  createdAt: string | null
  ordersCount: number
  totalSpent: number
}

const authStore = useAuthStore()
const toastStore = useToastStore()

const clients = ref<Client[]>([])
const chargement = ref(true)
const erreur = ref<string | null>(null)
const recherche = ref('')
const filtre = ref('tous')
const enCours = ref<string | null>(null)

const moi = computed(() => authStore.user?.uid)

const onglets = [
  { cle: 'tous', libelle: 'Tous' },
  { cle: 'clients', libelle: 'Ont commandé' },
  { cle: 'admins', libelle: 'Administrateurs' },
  { cle: 'affilies', libelle: 'Affiliés' },
  { cle: 'non-verifies', libelle: 'Non vérifiés' },
]

const correspond = (client: Client, cle: string) => {
  switch (cle) {
    case 'clients': return client.ordersCount > 0
    case 'admins': return client.isAdmin
    case 'affilies': return client.isAffiliate
    case 'non-verifies': return !client.emailVerified
    default: return true
  }
}

const compteur = (cle: string) => (cle === 'tous' ? 0 : clients.value.filter((c) => correspond(c, cle)).length)

const clientsFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()

  return clients.value.filter((client) => {
    if (!correspond(client, filtre.value)) return false
    if (!terme) return true
    return (client.name ?? '').toLowerCase().includes(terme) || client.email.toLowerCase().includes(terme)
  })
})

const initiales = (client: Client) => {
  const source = client.name?.trim() || client.email
  return source.split(/[\s@.]+/).filter(Boolean).slice(0, 2).map((mot) => mot[0]).join('').toUpperCase()
}

const charger = async () => {
  chargement.value = true
  erreur.value = null
  try {
    const reponse = await api.get('/api/admin/users')
    clients.value = reponse.data.users ?? []
  } catch (e) {
    console.error(e)
    erreur.value = 'La liste des clients n’a pas pu être chargée.'
  } finally {
    chargement.value = false
  }
}

const basculer = async (client: Client) => {
  const promotion = !client.isAdmin
  const question = promotion
    ? `Donner les droits d’administration à ${client.name || client.email} ?`
    : `Retirer les droits d’administration à ${client.name || client.email} ?`

  if (!window.confirm(question)) return

  enCours.value = client.uid
  try {
    await api.patch(`/api/admin/users/${client.uid}/role`, { isAdmin: promotion })
    client.isAdmin = promotion
    viderCacheApi()
    toastStore.success(promotion ? 'Droits accordés.' : 'Droits retirés.')
  } catch (e: any) {
    toastStore.error(e?.response?.data?.error ?? 'La modification a échoué.')
  } finally {
    enCours.value = null
  }
}

onMounted(charger)
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="onglet in onglets"
          :key="onglet.cle"
          type="button"
          class="chip"
          :aria-pressed="onglet.cle === statut"
          @click="statut = onglet.cle"
        >
          {{ onglet.libelle }}
        </button>
      </div>

      <label class="relative w-full sm:w-72">
        <span class="sr-only">Rechercher une demande</span>
        <Search class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink-500" />
        <input v-model="recherche" class="field pl-11" placeholder="Nom, e-mail ou motif" />
      </label>
    </div>

    <div v-if="chargement" class="mt-6 space-y-2">
      <div v-for="i in 4" :key="i" class="skeleton h-32" />
    </div>

    <div v-else-if="!demandesFiltrees.length" class="mt-6 border border-rule bg-surface p-12 text-center">
      <p class="t-h3">{{ vide.titre }}</p>
      <p class="t-body mt-2 text-ink-500">{{ vide.detail }}</p>
    </div>

    <ul v-else class="mt-6 space-y-3">
      <li v-for="demande in demandesFiltrees" :key="demande.id" class="border border-rule bg-surface">
        <div class="flex flex-wrap items-start justify-between gap-4 p-5">
          <div class="min-w-0">
            <p class="t-body text-ink-900">{{ demande.user?.name || 'Compte sans nom' }}</p>
            <p class="t-small mt-1 truncate text-ink-500">{{ demande.user?.email || demande.uid }}</p>
            <p class="t-small mt-1 text-ink-500">Déposée le {{ formatDateLongue(demande.createdAt) }}</p>
          </div>

          <span class="t-small shrink-0" :class="etats[demande.status].classe">
            {{ etats[demande.status].libelle }}
          </span>
        </div>

        <div class="border-t border-rule p-5">
          <p class="t-label text-ink-500">Motif de la demande</p>
          <p class="t-body mt-2 whitespace-pre-line text-ink-700">{{ demande.reason || '—' }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-rule p-5">
          <a
            v-if="demande.identityCardUrl"
            :href="demande.identityCardUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-sm btn-secondary"
          >
            Voir la pièce d’identité
          </a>
          <span v-else class="t-small text-ink-500">Aucune pièce jointe</span>

          <template v-if="demande.status === 'pending'">
            <button
              type="button"
              class="btn btn-sm btn-primary sm:ml-auto"
              :disabled="enCours === demande.id"
              @click="approuver(demande)"
            >
              Approuver
            </button>
            <button
              type="button"
              class="btn btn-sm btn-secondary"
              :disabled="enCours === demande.id"
              @click="rejeter(demande)"
            >
              Rejeter
            </button>
          </template>

          <button
            type="button"
            class="btn btn-sm btn-danger"
            :class="{ 'sm:ml-auto': demande.status !== 'pending' }"
            :disabled="enCours === demande.id"
            @click="supprimer(demande)"
          >
            Supprimer
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Search } from 'lucide-vue-next'
import { api, viderCacheApi } from '@/lib/api'
import { formatDateLongue } from '@/lib/commandes'
import { useToastStore } from '@/stores/toast'

interface Demande {
  id: string
  uid: string
  reason: string
  identityCardUrl: string | null
  status: 'pending' | 'approved' | 'rejected'
  createdAt: string
  user?: { uid: string; email: string; name: string | null }
}

const toastStore = useToastStore()

const demandes = ref<Demande[]>([])
const chargement = ref(true)
const recherche = ref('')
const statut = ref<Demande['status']>('pending')
const enCours = ref<string | null>(null)

const onglets = [
  { cle: 'pending' as const, libelle: 'En attente' },
  { cle: 'approved' as const, libelle: 'Approuvées' },
  { cle: 'rejected' as const, libelle: 'Rejetées' },
]

const etats: Record<Demande['status'], { libelle: string; classe: string }> = {
  pending: { libelle: 'En attente', classe: 'text-warning' },
  approved: { libelle: 'Approuvée', classe: 'text-success' },
  rejected: { libelle: 'Rejetée', classe: 'text-ink-500' },
}

const vide = computed(() => {
  if (recherche.value) return { titre: 'Aucun résultat', detail: 'Aucune demande ne correspond à cette recherche.' }
  return {
    pending: { titre: 'Rien à traiter', detail: 'Les nouvelles demandes d’affiliation apparaîtront ici.' },
    approved: { titre: 'Aucune approbation', detail: 'Les demandes acceptées seront listées ici.' },
    rejected: { titre: 'Aucun rejet', detail: 'Les demandes refusées seront listées ici.' },
  }[statut.value]
})

const demandesFiltrees = computed(() => {
  const terme = recherche.value.trim().toLowerCase()
  if (!terme) return demandes.value

  return demandes.value.filter(
    (d) =>
      (d.user?.name ?? '').toLowerCase().includes(terme) ||
      (d.user?.email ?? '').toLowerCase().includes(terme) ||
      d.reason.toLowerCase().includes(terme)
  )
})

const charger = async () => {
  chargement.value = true
  try {
    const reponse = await api.get(`/api/affiliate/requests/${statut.value}`)
    demandes.value = reponse.data.requests ?? []
  } catch (e) {
    console.error(e)
    toastStore.error('Les demandes n’ont pas pu être chargées.')
  } finally {
    chargement.value = false
  }
}

/*
 * Les trois actions visaient des chemins qui n'existent pas côté serveur
 * (`/api/admin/approve-affiliate`, `/api/affiliate/request/{id}/reject`,
 * `DELETE /api/affiliate/request/{id}`) : l'écran répondait 404 sans jamais
 * rien approuver. Elles suivent maintenant les routes réellement déclarées.
 */
const agir = async (demande: Demande, action: () => Promise<unknown>, message: string) => {
  enCours.value = demande.id
  try {
    await action()
    demandes.value = demandes.value.filter((d) => d.id !== demande.id)
    viderCacheApi()
    toastStore.success(message)
  } catch (e: any) {
    toastStore.error(e?.response?.data?.error ?? 'L’opération a échoué.')
  } finally {
    enCours.value = null
  }
}

const approuver = (demande: Demande) =>
  agir(demande, () => api.post(`/api/affiliate/${demande.id}/approve`), 'Demande approuvée.')

const rejeter = (demande: Demande) =>
  agir(demande, () => api.post(`/api/affiliate/${demande.id}/reject`), 'Demande rejetée.')

const supprimer = (demande: Demande) => {
  if (!window.confirm('Supprimer définitivement cette demande ?')) return
  return agir(demande, () => api.delete(`/api/affiliate/${demande.id}`), 'Demande supprimée.')
}

watch(statut, charger)
onMounted(charger)
</script>

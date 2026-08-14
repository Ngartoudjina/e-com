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

      <p data-numeric class="t-small text-ink-500">
        {{ commandesFiltrees.length }} commande{{ commandesFiltrees.length > 1 ? 's' : '' }}
      </p>
    </div>

    <div v-if="chargement" class="mt-6 space-y-2">
      <div v-for="i in 5" :key="i" class="skeleton h-16" />
    </div>

    <div v-else-if="erreur" class="mt-6 border border-rule bg-surface p-10 text-center">
      <p class="t-body">{{ erreur }}</p>
      <button type="button" class="btn btn-secondary mt-6" @click="charger">Réessayer</button>
    </div>

    <div v-else-if="!commandesFiltrees.length" class="mt-6 border border-rule bg-surface p-12 text-center">
      <p class="t-h3">Aucune commande</p>
      <p class="t-body mt-2 text-ink-500">
        {{ filtre === 'toutes' ? 'Les commandes passées apparaîtront ici.' : 'Aucune commande dans cet état.' }}
      </p>
    </div>

    <!-- Densité 13 px, lignes de 44 px : la table doit se lire d'un coup d'œil. -->
    <div v-else class="mt-6 overflow-x-auto border border-rule bg-surface">
      <table class="w-full min-w-[820px] text-[13px]">
        <thead>
          <tr class="border-b border-rule text-left">
            <th class="t-label px-5 py-3 font-normal text-ink-500">Référence</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Client</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Date</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Articles</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">État</th>
            <th class="t-label px-5 py-3 text-right font-normal text-ink-500">Total</th>
            <th class="px-5 py-3"><span class="sr-only">Action</span></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="commande in commandesFiltrees"
            :key="commande.id"
            class="h-11 border-b border-rule last:border-0 transition-colors hover:bg-rule-soft/50"
          >
            <td data-numeric class="px-5 py-3 text-ink-900">#{{ commande.reference }}</td>
            <td class="px-5 py-3 text-ink-700">
              <span class="block truncate">{{ commande.shippingAddress?.name || '—' }}</span>
              <span class="block truncate text-[11px] text-ink-500">{{ commande.email }}</span>
            </td>
            <td class="px-5 py-3 text-ink-700">{{ formatDate(commande.placedAt) }}</td>
            <td data-numeric class="px-5 py-3 text-ink-700">{{ commande.itemCount }}</td>
            <td class="px-5 py-3">
              <span class="t-small inline-flex items-center gap-2" :class="etat(commande.status).classe">
                <span aria-hidden="true">●</span>{{ etat(commande.status).libelle }}
              </span>
            </td>
            <td data-numeric class="px-5 py-3 text-right text-ink-900">{{ formatPrix(commande.total) }}</td>
            <td class="px-5 py-3 text-right">
              <RouterLink :to="`/admin/commandes/${commande.reference}`" class="btn btn-sm btn-secondary">
                Détail
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { api } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { ETATS_COMMANDE, formatDateCourte } from '@/lib/commandes'
import type { Commande } from '@/lib/commandes'

const commandes = ref<Commande[]>([])
const compteurs = ref<Record<string, number>>({})
const chargement = ref(true)
const erreur = ref<string | null>(null)
const filtre = ref('toutes')

const onglets = [
  { cle: 'toutes', libelle: 'Toutes', statuts: [] as string[] },
  { cle: 'a-traiter', libelle: 'À traiter', statuts: ['pending', 'paid'] },
  { cle: 'preparation', libelle: 'En préparation', statuts: ['preparing'] },
  { cle: 'expediees', libelle: 'Expédiées', statuts: ['shipped'] },
  { cle: 'livrees', libelle: 'Livrées', statuts: ['delivered'] },
  { cle: 'annulees', libelle: 'Annulées', statuts: ['cancelled', 'refunded'] },
]

const etat = (statut: string) => ETATS_COMMANDE[statut] ?? { libelle: statut, classe: 'text-ink-500' }
const formatDate = formatDateCourte

const commandesFiltrees = computed(() => {
  const onglet = onglets.find((o) => o.cle === filtre.value)
  if (!onglet?.statuts.length) return commandes.value
  return commandes.value.filter((c) => onglet.statuts.includes(c.status))
})

/** Compteur par onglet, calculé sur les totaux renvoyés par l'API. */
const compteur = (cle: string) => {
  const onglet = onglets.find((o) => o.cle === cle)
  if (!onglet?.statuts.length) return 0
  return onglet.statuts.reduce((somme, statut) => somme + (compteurs.value[statut] ?? 0), 0)
}

const charger = async () => {
  chargement.value = true
  erreur.value = null
  try {
    const reponse = await api.get('/api/admin/orders')
    commandes.value = reponse.data.orders ?? []
    compteurs.value = reponse.data.byStatus ?? {}
  } catch (e) {
    console.error(e)
    erreur.value = 'Les commandes n’ont pas pu être chargées.'
  } finally {
    chargement.value = false
  }
}

onMounted(charger)
</script>

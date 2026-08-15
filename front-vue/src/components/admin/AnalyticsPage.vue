<template>
  <div class="space-y-4">
    <!-- ---------------------------------------------- Indicateurs -->
    <div v-if="chargement" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="i in 4" :key="i" class="skeleton h-28" />
    </div>

    <div v-else-if="erreur" class="border border-rule bg-surface p-10 text-center">
      <p class="t-body">{{ erreur }}</p>
      <button type="button" class="btn btn-secondary mt-6" @click="charger">Réessayer</button>
    </div>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="carte in cartes" :key="carte.libelle" class="border border-rule bg-surface p-5">
          <p class="t-label text-ink-500">{{ carte.libelle }}</p>
          <p data-numeric class="t-h2 mt-3 text-ink-900">{{ carte.valeur }}</p>
          <p class="t-small mt-2 text-ink-500">{{ carte.detail }}</p>
        </article>
      </div>

      <!-- ---------------------------------------------- Courbe -->
      <section class="border border-rule bg-surface">
        <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-rule p-5">
          <div>
            <h2 class="t-h3">Chiffre d’affaires</h2>
            <p class="t-small mt-1 text-ink-500">
              Douze derniers mois · commandes payées, en préparation, expédiées ou livrées
            </p>
          </div>
          <p data-numeric class="t-small text-ink-500">Point haut : {{ formatPrix(sommet) }}</p>
        </div>

        <div v-if="!sommet" class="p-12 text-center">
          <p class="t-body text-ink-500">Aucune vente encaissée sur la période.</p>
        </div>

        <div v-else class="p-5">
          <!--
            Tracé en SVG plutôt qu'avec une bibliothèque de graphiques :
            une seule courbe ne justifie pas 80 ko de dépendance.
          -->
          <svg
            :viewBox="`0 0 ${LARGEUR} ${HAUTEUR}`"
            class="h-56 w-full"
            preserveAspectRatio="none"
            role="img"
            :aria-label="`Chiffre d'affaires mensuel, point haut ${formatPrix(sommet)}`"
          >
            <line
              v-for="part in 4"
              :key="part"
              :y1="(HAUTEUR / 4) * part"
              :y2="(HAUTEUR / 4) * part"
              x1="0"
              :x2="LARGEUR"
              stroke="var(--color-rule)"
              stroke-width="1"
              vector-effect="non-scaling-stroke"
            />
            <polyline
              :points="trace"
              fill="none"
              stroke="var(--color-ink-900)"
              stroke-width="2"
              vector-effect="non-scaling-stroke"
            />
            <circle
              v-for="(point, index) in points"
              :key="index"
              :cx="point.x"
              :cy="point.y"
              r="3"
              fill="var(--color-surface)"
              stroke="var(--color-ink-900)"
              stroke-width="2"
              vector-effect="non-scaling-stroke"
            />
          </svg>

          <ol class="mt-3 flex justify-between">
            <li
              v-for="mois in serie"
              :key="mois.mois"
              data-numeric
              class="t-small text-ink-500"
              :title="`${libelleMois(mois.mois)} · ${formatPrix(mois.total)} · ${mois.commandes} commande(s)`"
              :aria-label="libelleMois(mois.mois)"
            >
              {{ libelleMoisCourt(mois.mois) }}
            </li>
          </ol>
        </div>
      </section>

      <div class="grid gap-4 lg:grid-cols-2">
        <!-- ---------------------------------------------- Meilleures ventes -->
        <section class="border border-rule bg-surface">
          <div class="border-b border-rule p-5">
            <h2 class="t-h3">Meilleures ventes</h2>
            <p class="t-small mt-1 text-ink-500">Cumul depuis l’ouverture</p>
          </div>

          <p v-if="!meilleuresVentes.length" class="t-body p-5 text-ink-500">
            Aucune pièce vendue pour l’instant.
          </p>

          <ol v-else>
            <li
              v-for="(produit, rang) in meilleuresVentes"
              :key="produit.id"
              class="flex items-baseline gap-4 border-b border-rule px-5 py-3 last:border-0"
            >
              <span data-numeric class="t-small w-4 shrink-0 text-ink-500">{{ rang + 1 }}</span>
              <span class="t-body min-w-0 flex-1 truncate text-ink-900">{{ produit.nom }}</span>
              <span data-numeric class="t-small shrink-0 text-ink-500">{{ produit.vendus }} vendus</span>
              <span data-numeric class="t-small w-24 shrink-0 text-right text-ink-900">
                {{ formatPrix(produit.revenu) }}
              </span>
            </li>
          </ol>
        </section>

        <!-- ---------------------------------------------- Répartitions -->
        <section class="border border-rule bg-surface">
          <div class="border-b border-rule p-5">
            <h2 class="t-h3">Répartition</h2>
            <p class="t-small mt-1 text-ink-500">Commandes par état, catalogue par rayon</p>
          </div>

          <div class="space-y-5 p-5">
            <div>
              <p class="t-label text-ink-500">Commandes</p>
              <ul class="mt-3 space-y-2">
                <li v-for="(nombre, statut) in ventes.parStatut" :key="statut" class="flex items-center gap-3">
                  <span class="t-small w-28 shrink-0" :class="etat(String(statut)).classe">
                    {{ etat(String(statut)).libelle }}
                  </span>
                  <span class="h-1 bg-ink-900" :style="{ width: barre(nombre, totalCommandes) }" />
                  <span data-numeric class="t-small text-ink-500">{{ nombre }}</span>
                </li>
              </ul>
            </div>

            <div v-if="categories.length" class="border-t border-rule pt-5">
              <p class="t-label text-ink-500">Catalogue</p>
              <ul class="mt-3 space-y-2">
                <li v-for="[rayon, nombre] in categories" :key="rayon" class="flex items-center gap-3">
                  <span class="t-small w-28 shrink-0 truncate text-ink-700">{{ rayon }}</span>
                  <span class="h-1 bg-ink-500" :style="{ width: barre(nombre, totalProduits) }" />
                  <span data-numeric class="t-small text-ink-500">{{ nombre }}</span>
                </li>
              </ul>
            </div>
          </div>
        </section>
      </div>

      <!-- ---------------------------------------------- Catalogue & audience -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="mesure in secondaires" :key="mesure.libelle" class="border border-rule bg-surface p-5">
          <p class="t-label text-ink-500">{{ mesure.libelle }}</p>
          <p data-numeric class="t-h3 mt-2 text-ink-900">{{ mesure.valeur }}</p>
        </article>
      </section>
    </template>

    <!-- ---------------------------------------------- Campagne e-mail -->
    <section class="border border-rule bg-surface">
      <div class="border-b border-rule p-5">
        <h2 class="t-h3">Message aux abonnés</h2>
        <p class="t-small mt-1 text-ink-500">
          Adressé aux {{ analytics.subscribers ?? 0 }} inscrits à la lettre, les désabonnés exclus.
        </p>
      </div>

      <form class="space-y-5 p-5" @submit.prevent="envoyer">
        <label class="block">
          <span class="t-label text-ink-500">Sujet</span>
          <input v-model="sujet" class="field mt-3" maxlength="200" :disabled="envoi" required />
        </label>

        <label class="block">
          <span class="t-label text-ink-500">Message</span>
          <textarea v-model="message" class="field mt-3 min-h-32 py-3" maxlength="20000" :disabled="envoi" required />
        </label>

        <div class="border-t border-rule pt-5">
          <button type="submit" class="btn btn-primary" :disabled="envoi || !analytics.subscribers">
            {{ envoi ? 'Envoi en cours…' : 'Envoyer' }}
          </button>
          <p v-if="!analytics.subscribers" class="t-small mt-3 text-ink-500">
            Aucun abonné à qui écrire pour l’instant.
          </p>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { api, viderCacheApi } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { ETATS_COMMANDE } from '@/lib/commandes'
import { useToastStore } from '@/stores/toast'

interface Mois { mois: string; total: number; commandes: number }
interface Vedette { id: string; nom: string; vendus: number; stock: number; revenu: number }

const LARGEUR = 600
const HAUTEUR = 200

const toastStore = useToastStore()

const chargement = ref(true)
const erreur = ref<string | null>(null)
const analytics = ref<Record<string, any>>({})
const ventes = ref<Record<string, any>>({})

const sujet = ref('')
const message = ref('')
const envoi = ref(false)

const etat = (statut: string) => ETATS_COMMANDE[statut] ?? { libelle: statut, classe: 'text-ink-500' }

const serie = computed<Mois[]>(() => ventes.value.parMois ?? [])
const meilleuresVentes = computed<Vedette[]>(() => ventes.value.meilleuresVentes ?? [])
const sommet = computed(() => Math.max(0, ...serie.value.map((m) => m.total)))

/** Coordonnées de la courbe, ramenées au repère du SVG. */
const points = computed(() => {
  const mois = serie.value
  if (mois.length < 2 || !sommet.value) return []

  const pas = LARGEUR / (mois.length - 1)
  return mois.map((m, index) => ({
    x: Math.round(index * pas),
    // L'origine du SVG est en haut : la valeur haute doit donner un y bas.
    y: Math.round(HAUTEUR - (m.total / sommet.value) * (HAUTEUR - 10) - 5),
  }))
})

const trace = computed(() => points.value.map((p) => `${p.x},${p.y}`).join(' '))

const totalCommandes = computed(() =>
  Object.values(ventes.value.parStatut ?? {}).reduce((somme: number, n) => somme + Number(n), 0)
)
const totalProduits = computed(() => analytics.value.totalProducts ?? 0)
const categories = computed<[string, number][]>(() =>
  Object.entries(analytics.value.productsByCategory ?? {}) as [string, number][]
)

/** Largeur proportionnelle, avec un minimum visible pour ne pas effacer une valeur. */
const barre = (valeur: number, total: number) =>
  total > 0 ? `${Math.max(4, Math.round((Number(valeur) / total) * 60))}%` : '4%'

const libelleMois = (cle: string) =>
  new Date(`${cle}-01T00:00:00`).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })

/*
 * Abrégé sur trois lettres plutôt qu'une seule : « J » désignait aussi bien
 * janvier que juin ou juillet, et « M » mars ou mai. Le point final que
 * produit `toLocaleDateString` est retiré, il n'apporte rien ici.
 */
const libelleMoisCourt = (cle: string) =>
  new Date(`${cle}-01T00:00:00`)
    .toLocaleDateString('fr-FR', { month: 'short' })
    .replace('.', '')

const cartes = computed(() => [
  {
    libelle: 'Chiffre d’affaires',
    valeur: formatPrix(ventes.value.revenu ?? 0),
    detail: 'Hors commandes annulées et remboursées',
  },
  {
    libelle: 'Commandes encaissées',
    valeur: String(ventes.value.commandes ?? 0),
    detail: `${ventes.value.enAttente ?? 0} en attente de paiement`,
  },
  {
    libelle: 'Panier moyen',
    valeur: formatPrix(ventes.value.panierMoyen ?? 0),
    detail: `${ventes.value.annulees ?? 0} commande(s) annulée(s)`,
  },
  {
    libelle: 'Pièces vendues',
    valeur: String(analytics.value.totalSold ?? 0),
    detail: `${analytics.value.outOfStock ?? 0} référence(s) en rupture`,
  },
])

const secondaires = computed(() => [
  { libelle: 'Références', valeur: String(analytics.value.totalProducts ?? 0) },
  { libelle: 'Pièces en stock', valeur: String(analytics.value.totalStock ?? 0) },
  { libelle: 'Comptes clients', valeur: String(analytics.value.totalUsers ?? 0) },
  { libelle: 'Abonnés', valeur: String(analytics.value.subscribers ?? 0) },
])

const charger = async () => {
  chargement.value = true
  erreur.value = null
  try {
    const reponse = await api.get('/api/admin/analytics')
    analytics.value = reponse.data.analytics ?? {}
    ventes.value = reponse.data.ventes ?? {}
  } catch (e) {
    console.error(e)
    erreur.value = 'Les statistiques n’ont pas pu être chargées.'
  } finally {
    chargement.value = false
  }
}

const envoyer = async () => {
  envoi.value = true
  try {
    const reponse = await api.post('/api/send-bulk-email', {
      subject: sujet.value,
      message: message.value,
    })
    toastStore.success(reponse.data?.message ?? 'Message mis en file d’envoi.')
    sujet.value = ''
    message.value = ''
    viderCacheApi()
  } catch (e: any) {
    toastStore.error(e?.response?.data?.error ?? 'L’envoi a échoué.')
  } finally {
    envoi.value = false
  }
}

onMounted(charger)
</script>

<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="container-page pb-24">
      <!-- Accueil personnalisé -->
      <header class="flex flex-wrap items-center justify-between gap-8 py-12">
        <div class="flex items-center gap-6">
          <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-rule-soft">
            <img v-if="utilisateur?.photoUrl" :src="utilisateur.photoUrl" alt="" class="size-full object-cover" />
            <span v-else class="font-display text-[22px] text-ink-700">{{ initiale }}</span>
          </span>
          <div>
            <p v-if="membreDepuis" class="t-label text-ink-500">Membre depuis {{ membreDepuis }}</p>
            <p v-else class="t-label text-ink-500">Espace client</p>
            <h1 class="t-screen-title mt-2">Bonjour, {{ prenom }}</h1>
          </div>
        </div>

        <dl class="flex gap-10">
          <div v-for="stat in statistiques" :key="stat.libelle">
            <dt class="t-label text-ink-500">{{ stat.libelle }}</dt>
            <dd data-numeric class="mt-2 font-display text-[28px] leading-none">{{ stat.valeur }}</dd>
          </div>
        </dl>
      </header>

      <div class="grid-page border-t border-rule pt-10">
        <!-- Navigation de l'espace : 3 colonnes -->
        <nav class="col-span-4 lg:col-span-3" aria-label="Espace client">
          <ul class="flex gap-2 overflow-x-auto lg:block lg:space-y-1 lg:overflow-visible">
            <li v-for="section in sections" :key="section.cle" class="shrink-0">
              <button
                type="button"
                class="w-full whitespace-nowrap border-b px-4 py-3 text-left transition-colors duration-[120ms] lg:border-b lg:border-rule"
                :class="section.cle === sectionActive
                  ? 'border-transparent bg-ink-900 text-white lg:border-ink-900'
                  : 'border-rule text-ink-700 hover:text-ink-900'"
                @click="sectionActive = section.cle"
              >
                {{ section.libelle }}
              </button>
            </li>
          </ul>

          <button
            type="button"
            class="mt-8 hidden px-4 py-3 text-left text-ink-500 transition-colors hover:text-ink-900 lg:block"
            @click="seDeconnecter"
          >
            Se déconnecter
          </button>
        </nav>

        <!-- Contenu : 9 colonnes -->
        <div class="col-span-4 mt-10 lg:col-span-9 lg:mt-0">
          <!-- ---------------------------------------------- Commandes -->
          <section v-if="sectionActive === 'commandes'">
            <!-- Suivi de la commande en cours -->
            <div v-if="commandeEnCours" class="flex flex-wrap items-center gap-6 bg-ink-900 p-6 text-white lg:p-8">
              <span class="size-20 shrink-0 overflow-hidden bg-white/10" />
              <div class="min-w-0 flex-1">
                <p class="t-label text-white/60">
                  Commande {{ commandeEnCours.reference }} · {{ commandeEnCours.statut }}
                </p>
                <p class="mt-2 font-display text-[28px] leading-none">{{ commandeEnCours.arrivee }}</p>
                <ol class="mt-6 flex items-center gap-2">
                  <li v-for="(jalon, index) in jalons" :key="jalon" class="flex flex-1 items-center gap-2 last:flex-none">
                    <span class="flex items-center gap-2">
                      <span class="size-2 rounded-full" :class="index <= commandeEnCours.etape ? 'bg-white' : 'bg-white/30'" />
                      <span class="t-small" :class="index <= commandeEnCours.etape ? 'text-white' : 'text-white/40'">{{ jalon }}</span>
                    </span>
                    <span v-if="index < jalons.length - 1" class="h-px flex-1" :class="index < commandeEnCours.etape ? 'bg-white' : 'bg-white/25'" />
                  </li>
                </ol>
              </div>
              <button type="button" class="btn bg-paper text-ink-900 hover:bg-white">Suivre le colis</button>
            </div>

            <div class="mt-10 flex flex-wrap items-center justify-between gap-4">
              <h2 class="t-h2">Commandes</h2>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="filtre in filtresCommandes"
                  :key="filtre"
                  type="button"
                  class="chip"
                  :aria-pressed="filtre === filtreCommande"
                  @click="filtreCommande = filtre"
                >
                  {{ filtre }}
                </button>
              </div>
            </div>

            <!--
              Aucune table de commandes n'existe côté serveur : plutôt que
              d'afficher des commandes inventées, l'état vide dit la vérité.
            -->
            <div class="mt-6 border border-rule bg-surface p-12 text-center">
              <p class="t-h3">Aucune commande pour l’instant</p>
              <p class="t-body mt-2 text-ink-500">
                Vos commandes et leur suivi apparaîtront ici.
              </p>
              <RouterLink to="/catalogue" class="btn btn-secondary mt-6">Découvrir le catalogue</RouterLink>
            </div>
          </section>

          <!-- ---------------------------------------------- Favoris -->
          <section v-else-if="sectionActive === 'favoris'">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <h2 class="t-h2">Favoris</h2>
            </div>
            <div class="mt-6 border border-rule bg-surface p-12 text-center">
              <p class="t-h3">Aucun favori</p>
              <p class="t-body mt-2 text-ink-500">
                Les pièces que vous mettez de côté depuis le catalogue apparaîtront ici.
              </p>
              <RouterLink to="/catalogue" class="btn btn-secondary mt-6">Parcourir le catalogue</RouterLink>
            </div>
          </section>

          <!-- ---------------------------------------------- Retours -->
          <section v-else-if="sectionActive === 'retours'">
            <h2 class="t-h2">Retours &amp; échanges</h2>
            <div class="mt-6 grid gap-4 lg:grid-cols-2">
              <div class="border border-rule bg-surface p-8 text-center">
                <p class="t-h3">Aucun retour en cours</p>
                <p class="t-body mt-2 text-ink-500">Un retour se déclare depuis la commande concernée.</p>
              </div>

              <div class="border border-rule bg-surface p-8">
                <h3 class="t-label text-ink-500">Comment ça marche</h3>
                <ol class="mt-6 space-y-5">
                  <li v-for="(etape, index) in etapesRetour" :key="etape" class="flex gap-4">
                    <span data-numeric class="flex size-7 shrink-0 items-center justify-center rounded-full border border-rule text-[13px]">
                      {{ index + 1 }}
                    </span>
                    <span class="t-body text-ink-700">{{ etape }}</span>
                  </li>
                </ol>
              </div>
            </div>
          </section>

          <!-- ---------------------------------------------- Adresses & paiement -->
          <section v-else-if="sectionActive === 'adresses'">
            <div class="grid gap-10 lg:grid-cols-2">
              <div>
                <h2 class="t-h2">Adresses</h2>
                <div class="mt-6 border border-rule bg-surface p-8 text-center">
                  <p class="t-body text-ink-500">Aucune adresse enregistrée.</p>
                </div>
              </div>
              <div>
                <h2 class="t-h2">Paiement</h2>
                <div class="mt-6 border border-rule bg-surface p-8 text-center">
                  <p class="t-body text-ink-500">Aucun moyen de paiement enregistré.</p>
                </div>
              </div>
            </div>
          </section>

          <!-- ---------------------------------------------- Profil -->
          <section v-else-if="sectionActive === 'profil'">
            <h2 class="t-h2">Profil &amp; tailles</h2>
            <div class="mt-6 border border-rule bg-surface p-8">
              <dl class="grid gap-6 sm:grid-cols-2">
                <div v-for="champ in champsProfil" :key="champ.libelle">
                  <dt class="t-label text-ink-500">{{ champ.libelle }}</dt>
                  <dd class="t-body mt-2 text-ink-900">{{ champ.valeur || '—' }}</dd>
                </div>
              </dl>

              <div class="mt-8 border-t border-rule pt-6">
                <h3 class="t-label text-ink-500">Mes tailles habituelles</h3>
                <p class="t-body mt-3 text-ink-500">
                  Aucune taille enregistrée. Elles seront présélectionnées sur chaque fiche produit.
                </p>
              </div>

              <button type="button" class="btn btn-secondary mt-8" @click="toastStore.info('La modification du profil n’est pas encore branchée.')">
                Modifier mes informations
              </button>
            </div>
          </section>

          <!-- ---------------------------------------------- Notifications -->
          <section v-else>
            <h2 class="t-h2">Notifications</h2>
            <ul class="mt-6 border border-rule bg-surface">
              <li
                v-for="(preference, index) in preferences"
                :key="preference.cle"
                class="flex items-center justify-between gap-6 p-6"
                :class="index > 0 ? 'border-t border-rule' : ''"
              >
                <span class="min-w-0">
                  <span class="t-body block text-ink-900">{{ preference.libelle }}</span>
                  <span class="t-small block text-ink-500">{{ preference.detail }}</span>
                </span>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="preference.actif"
                  :aria-label="preference.libelle"
                  class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-[200ms]"
                  :class="preference.actif ? 'bg-action' : 'bg-rule'"
                  @click="preference.actif = !preference.actif"
                >
                  <span class="absolute top-1 size-4 rounded-full bg-white transition-all duration-[200ms]" :class="preference.actif ? 'left-6' : 'left-1'" />
                </button>
              </li>
            </ul>
            <p class="t-small mt-4 text-ink-500">
              Ces préférences ne sont pas encore enregistrées côté serveur.
            </p>
          </section>

          <button type="button" class="btn btn-secondary mt-10 w-full lg:hidden" @click="seDeconnecter">
            Se déconnecter
          </button>
        </div>
      </div>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const sections = [
  { cle: 'commandes', libelle: 'Commandes' },
  { cle: 'favoris', libelle: 'Favoris' },
  { cle: 'retours', libelle: 'Retours & échanges' },
  { cle: 'adresses', libelle: 'Adresses & paiement' },
  { cle: 'profil', libelle: 'Profil & tailles' },
  { cle: 'notifications', libelle: 'Notifications' },
]

const sectionActive = ref('commandes')
const filtresCommandes = ['Toutes', 'En cours', 'Livrées', 'Retournées']
const filtreCommande = ref('Toutes')
const jalons = ['Confirmée', 'Expédiée', 'Livrée']

const etapesRetour = [
  'Déclarez le retour depuis la commande, sous 30 jours.',
  'Collez l’étiquette prépayée fournie dans le colis.',
  'Déposez-le en point relais, remboursement sous 5 jours après réception.',
]

const preferences = reactive([
  { cle: 'suivi', libelle: 'Suivi de commande', detail: 'E-mail et SMS à chaque étape', actif: true },
  { cle: 'reassort', libelle: 'Alertes de réassort', detail: 'Pour les pièces de vos favoris', actif: true },
  { cle: 'prix', libelle: 'Baisses de prix', detail: 'Un e-mail par semaine maximum', actif: true },
  { cle: 'collections', libelle: 'Nouvelles collections', detail: 'Le premier jeudi du mois', actif: false },
])

const utilisateur = computed(() => authStore.user)

const prenom = computed(
  () => utilisateur.value?.firstName || utilisateur.value?.name?.split(' ')[0] || 'vous'
)

const initiale = computed(() => (prenom.value[0] ?? 'G').toUpperCase())

/**
 * L'API n'expose pas encore `createdAt` sur /auth/me : plutôt qu'une formule
 * vague, on n'affiche rien tant que la date n'est pas connue.
 */
const membreDepuis = computed<string | null>(() => null)

/** Les compteurs viendront du backend : aucun endpoint ne les fournit encore. */
const statistiques = [
  { libelle: 'Commandes', valeur: 0 },
  { libelle: 'Favoris', valeur: 0 },
  { libelle: 'Retouches offertes', valeur: 0 },
]

const commandeEnCours = null as null | { reference: string; statut: string; arrivee: string; etape: number }

const champsProfil = computed(() => [
  { libelle: 'Prénom', valeur: utilisateur.value?.firstName },
  { libelle: 'Nom', valeur: utilisateur.value?.lastName },
  { libelle: 'E-mail', valeur: utilisateur.value?.email },
  { libelle: 'Téléphone', valeur: null },
])

const seDeconnecter = async () => {
  await authStore.deconnexion()
  router.push('/')
}

// L'espace client n'a pas de sens sans session : on renvoie vers la connexion.
onMounted(async () => {
  await authStore.init()
  if (!authStore.estConnecte) {
    router.replace({ name: 'connexion', query: { suite: '/compte' } })
  }
})
</script>

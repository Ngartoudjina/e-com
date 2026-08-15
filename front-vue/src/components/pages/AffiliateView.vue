<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="mx-auto max-w-[900px] px-5 py-12 lg:px-8 lg:py-20">
      <h1 class="t-screen-title text-ink-900">Programme d’affiliation</h1>
      <p class="t-body mt-4 max-w-xl text-ink-700">
        Recommandez nos pièces et percevez une commission sur les commandes passées
        depuis votre lien.
      </p>

      <div v-if="chargement" class="mt-10 space-y-3">
        <div v-for="i in 3" :key="i" class="skeleton h-24" />
      </div>

      <!-- ---------------------------------------------- Non connecté -->
      <section v-else-if="!authStore.estConnecte" class="mt-10 border border-rule bg-surface p-8 text-center">
        <p class="t-h3">Un compte est nécessaire</p>
        <p class="t-body mt-3 text-ink-700">
          Connectez-vous pour déposer une demande d’affiliation.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
          <RouterLink to="/connexion" class="btn btn-primary">Se connecter</RouterLink>
          <RouterLink to="/inscription" class="btn btn-secondary">Créer un compte</RouterLink>
        </div>
      </section>

      <!-- ---------------------------------------------- Affilié actif -->
      <template v-else-if="estAffilie && donnees">
        <section class="mt-10 border border-rule bg-surface">
          <div class="border-b border-rule p-6">
            <p class="t-label text-ink-500">Votre code</p>
            <p data-numeric class="t-h1 mt-2 text-ink-900">{{ donnees.affiliateCode }}</p>
          </div>

          <div class="border-b border-rule p-6">
            <p class="t-label text-ink-500">Lien de parrainage</p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
              <code class="min-w-0 flex-1 truncate border border-rule bg-rule-soft/50 px-4 py-3 text-[13px] text-ink-700">
                {{ donnees.referralLink }}
              </code>
              <button type="button" class="btn btn-secondary shrink-0" @click="copier(donnees.referralLink)">
                {{ copie ? 'Copié' : 'Copier' }}
              </button>
            </div>
          </div>

          <dl class="grid gap-px bg-rule sm:grid-cols-3">
            <div class="bg-surface p-6">
              <dt class="t-label text-ink-500">Commission</dt>
              <dd data-numeric class="t-h2 mt-2 text-ink-900">{{ donnees.commissionRate }} %</dd>
            </div>
            <div class="bg-surface p-6">
              <dt class="t-label text-ink-500">Parrainages</dt>
              <dd data-numeric class="t-h2 mt-2 text-ink-900">{{ donnees.referralCount ?? 0 }}</dd>
            </div>
            <div class="bg-surface p-6">
              <dt class="t-label text-ink-500">Gains cumulés</dt>
              <dd data-numeric class="t-h2 mt-2 text-ink-900">{{ formatPrix(donnees.totalEarnings ?? 0) }}</dd>
            </div>
          </dl>
        </section>

        <p class="t-small mt-6 text-ink-500">
          Les gains sont calculés sur les commandes effectivement encaissées.
        </p>
      </template>

      <!-- ---------------------------------------------- Demande en cours -->
      <section v-else-if="statutDemande === 'pending'" class="mt-10 border border-rule bg-surface p-8">
        <p class="t-h3">Demande en cours d’examen</p>
        <p class="t-body mt-3 text-ink-700">
          Nous revenons vers vous dès qu’elle est traitée. Inutile d’en déposer une seconde.
        </p>
      </section>

      <!-- ---------------------------------------------- Formulaire -->
      <template v-else>
        <section v-if="statutDemande === 'rejected'" class="mt-10 border border-rule bg-surface p-6">
          <p class="t-h3">Demande précédente refusée</p>
          <p v-if="motifRefus" class="t-body mt-3 text-ink-700">{{ motifRefus }}</p>
          <p class="t-body mt-3 text-ink-700">Vous pouvez en déposer une nouvelle ci-dessous.</p>
        </section>

        <form class="mt-10 border border-rule bg-surface" @submit.prevent="envoyer">
          <div class="border-b border-rule p-6">
            <h2 class="t-h3">Déposer une demande</h2>
            <p class="t-small mt-1 text-ink-500">
              Une pièce d’identité est demandée pour verser les commissions.
            </p>
          </div>

          <div class="space-y-6 p-6">
            <label class="block">
              <span class="t-label text-ink-500">Votre motivation</span>
              <textarea
                v-model="motivation"
                class="field mt-3 min-h-32 py-3"
                maxlength="2000"
                placeholder="Comment comptez-vous parler de nos pièces ?"
              />
              <span class="t-small mt-2 block" :class="erreurs.motivation ? 'text-error' : 'text-ink-500'">
                {{ erreurs.motivation ?? `${motivation.trim().length} / 50 caractères minimum` }}
              </span>
            </label>

            <div>
              <span class="t-label text-ink-500">Pièce d’identité</span>
              <p class="t-small mt-1 text-ink-500">Image de 5 Mo au plus.</p>

              <div class="mt-3 flex flex-wrap items-start gap-5">
                <label class="flex h-32 w-full cursor-pointer items-center justify-center border border-dashed border-rule text-center transition-colors hover:bg-rule-soft/50 sm:w-72">
                  <input type="file" accept="image/*" class="sr-only" @change="choisirPiece" />
                  <span class="t-small px-4 text-ink-500">
                    {{ piece ? piece.name : 'Choisir un fichier' }}
                  </span>
                </label>

                <div v-if="apercu" class="relative w-40 shrink-0 border border-rule">
                  <img :src="apercu" alt="Aperçu de la pièce d’identité" class="aspect-[3/4] w-full object-cover" />
                  <button
                    type="button"
                    class="btn btn-icon absolute right-1 top-1 bg-surface"
                    aria-label="Retirer le fichier"
                    @click="retirerPiece"
                  >
                    <X class="size-4" />
                  </button>
                </div>
              </div>

              <p v-if="erreurs.piece" class="t-small mt-2 text-error">{{ erreurs.piece }}</p>
            </div>
          </div>

          <div class="border-t border-rule p-6">
            <button type="submit" class="btn btn-primary" :disabled="envoi">
              {{ envoi ? 'Envoi en cours…' : 'Déposer ma demande' }}
            </button>
          </div>
        </form>
      </template>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { X } from 'lucide-vue-next'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import { api } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

interface DonneesAffilie {
  affiliateCode: string
  referralLink: string
  commissionRate: number
  totalEarnings: number
  referralCount: number
}

const authStore = useAuthStore()
const toastStore = useToastStore()

const chargement = ref(true)
const estAffilie = ref(false)
const donnees = ref<DonneesAffilie | null>(null)
const statutDemande = ref<'pending' | 'approved' | 'rejected' | null>(null)
const motifRefus = ref<string | null>(null)

const motivation = ref('')
const piece = ref<File | null>(null)
const apercu = ref<string | null>(null)
const envoi = ref(false)
const copie = ref(false)
const erreurs = reactive<{ motivation?: string; piece?: string }>({})

let sondage: ReturnType<typeof setInterval> | undefined
let minuteurCopie: ReturnType<typeof setTimeout> | undefined

const lireStatut = async () => {
  try {
    const reponse = await api.get('/api/affiliate/status')
    estAffilie.value = reponse.data.isAffiliate ?? false
    donnees.value = reponse.data.affiliateData ?? null
    statutDemande.value = reponse.data.requestStatus ?? null
    motifRefus.value = reponse.data.rejectionReason ?? null
  } catch (e) {
    console.error(e)
    estAffilie.value = false
    statutDemande.value = null
  } finally {
    chargement.value = false
  }
}

const choisirPiece = (evenement: Event) => {
  const champ = evenement.target as HTMLInputElement
  const choisi = champ.files?.[0]
  if (!choisi) return

  if (choisi.size > 5 * 1024 * 1024) {
    erreurs.piece = 'Le fichier dépasse 5 Mo.'
    champ.value = ''
    return
  }
  if (!choisi.type.startsWith('image/')) {
    erreurs.piece = 'Le fichier doit être une image.'
    champ.value = ''
    return
  }

  erreurs.piece = undefined
  if (apercu.value) URL.revokeObjectURL(apercu.value)
  piece.value = choisi
  apercu.value = URL.createObjectURL(choisi)
}

const retirerPiece = () => {
  if (apercu.value) URL.revokeObjectURL(apercu.value)
  piece.value = null
  apercu.value = null
}

const envoyer = async () => {
  erreurs.motivation = motivation.value.trim().length < 50 ? 'Décrivez votre démarche en 50 caractères au moins.' : undefined
  erreurs.piece = piece.value ? undefined : 'Une pièce d’identité est nécessaire.'
  if (erreurs.motivation || erreurs.piece) return

  /*
   * L'identité du demandeur est déduite du jeton par le serveur.
   * L'ancienne version la lisait en décodant le jeton avec `atob`, hérité de
   * Firebase : un jeton Sanctum n'est pas un JWT, le décodage échouait à tous
   * les coups et le formulaire refusait d'envoyer quoi que ce soit.
   */
  const corps = new FormData()
  corps.append('reason', motivation.value)
  corps.append('identityCard', piece.value as File)

  envoi.value = true
  try {
    await api.post('/api/affiliate/request', corps, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    statutDemande.value = 'pending'
    motivation.value = ''
    retirerPiece()
    toastStore.success('Demande transmise.')
  } catch (e: any) {
    toastStore.error(e?.response?.data?.error ?? 'L’envoi a échoué.')
  } finally {
    envoi.value = false
  }
}

const copier = async (texte: string) => {
  try {
    await navigator.clipboard.writeText(texte)
    copie.value = true
    clearTimeout(minuteurCopie)
    minuteurCopie = setTimeout(() => (copie.value = false), 2000)
  } catch {
    toastStore.error('La copie a échoué.')
  }
}

onMounted(async () => {
  authStore.init()
  await authStore.verification

  if (!authStore.estConnecte) {
    chargement.value = false
    return
  }

  await lireStatut()
  // Une demande peut être traitée pendant la visite.
  sondage = setInterval(lireStatut, 30000)
})

onBeforeUnmount(() => {
  clearInterval(sondage)
  clearTimeout(minuteurCopie)
  if (apercu.value) URL.revokeObjectURL(apercu.value)
})
</script>

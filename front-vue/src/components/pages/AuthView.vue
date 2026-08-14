<template>
  <div class="min-h-screen bg-paper lg:grid lg:min-h-screen lg:grid-cols-2">
    <!-- Visuel de campagne : plein cadre, avec la signature en surimpression. -->
    <aside class="relative hidden bg-rule-soft lg:block">
      <img v-if="visuel" :src="visuel" alt="" class="absolute inset-0 size-full object-cover" />

      <RouterLink to="/" class="absolute left-10 top-10 font-display text-[26px] tracking-[0.18em] text-ink-900">
        GOLDSHOP
      </RouterLink>

      <div class="absolute inset-x-10 bottom-10 bg-paper/90 p-8 backdrop-blur">
        <h2 class="t-h2">Collection Automne</h2>
        <p class="t-body mt-4 max-w-md text-ink-700">
          Les membres accèdent aux nouvelles pièces 48 h avant leur mise en ligne,
          et gardent l’historique de leurs tailles.
        </p>
      </div>
    </aside>

    <!-- Formulaire -->
    <main class="flex items-center justify-center px-5 py-16 lg:px-16">
      <div class="w-full max-w-[420px]">
        <RouterLink to="/" class="mb-10 block font-display text-[22px] tracking-[0.18em] lg:hidden">
          GOLDSHOP
        </RouterLink>

        <!-- ---------------------------------------------- Connexion -->
        <template v-if="mode === 'connexion'">
          <h1 class="t-h2">Se connecter</h1>
          <p class="t-body mt-3 text-ink-700">
            Pas encore de compte ?
            <button type="button" class="text-action hover:underline" @click="basculer('inscription')">
              Créer un compte.
            </button>
          </p>

          <form class="mt-10 space-y-6" novalidate @submit.prevent="seConnecter">
            <div>
              <label for="email" class="t-label text-ink-500">E-mail</label>
              <input id="email" v-model.trim="formulaire.email" type="email" autocomplete="email" class="field mt-3" :aria-invalid="!!erreurs.email" />
              <p v-if="erreurs.email" class="t-small mt-2 text-error">{{ erreurs.email }}</p>
            </div>

            <div>
              <div class="flex items-baseline justify-between gap-4">
                <label for="mdp" class="t-label text-ink-500">Mot de passe</label>
                <button type="button" class="t-small text-action hover:underline" @click="basculer('oubli')">
                  Oublié ?
                </button>
              </div>
              <div class="relative mt-3">
                <input
                  id="mdp"
                  v-model="formulaire.motDePasse"
                  :type="motDePasseVisible ? 'text' : 'password'"
                  autocomplete="current-password"
                  class="field pr-12"
                  :aria-invalid="!!erreurs.motDePasse"
                />
                <button
                  type="button"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-500 hover:text-ink-900"
                  :aria-label="motDePasseVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                  @click="motDePasseVisible = !motDePasseVisible"
                >
                  <EyeOff v-if="motDePasseVisible" class="size-4" />
                  <Eye v-else class="size-4" />
                </button>
              </div>
              <p v-if="erreurs.motDePasse" class="t-small mt-2 text-error">{{ erreurs.motDePasse }}</p>
            </div>

            <p v-if="erreurs.global" class="t-small flex items-start gap-2 bg-error/10 p-3 text-error" role="alert">
              <AlertCircle class="mt-0.5 size-4 shrink-0" />
              {{ erreurs.global }}
            </p>

            <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="envoi">
              {{ envoi ? 'Connexion…' : 'Se connecter' }}
            </button>
          </form>
        </template>

        <!-- ---------------------------------------------- Inscription -->
        <template v-else-if="mode === 'inscription'">
          <h1 class="t-h2">Créer un compte</h1>
          <p class="t-body mt-3 text-ink-700">
            Déjà membre ?
            <button type="button" class="text-action hover:underline" @click="basculer('connexion')">
              Connectez-vous.
            </button>
          </p>

          <form class="mt-10 space-y-6" novalidate @submit.prevent="sInscrire">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="prenom" class="t-label text-ink-500">Prénom</label>
                <input id="prenom" v-model.trim="formulaire.prenom" autocomplete="given-name" class="field mt-3" :aria-invalid="!!erreurs.prenom" />
                <p v-if="erreurs.prenom" class="t-small mt-2 text-error">{{ erreurs.prenom }}</p>
              </div>
              <div>
                <label for="nom" class="t-label text-ink-500">Nom</label>
                <input id="nom" v-model.trim="formulaire.nom" autocomplete="family-name" class="field mt-3" :aria-invalid="!!erreurs.nom" />
                <p v-if="erreurs.nom" class="t-small mt-2 text-error">{{ erreurs.nom }}</p>
              </div>
            </div>

            <div>
              <label for="email-inscription" class="t-label text-ink-500">E-mail</label>
              <input id="email-inscription" v-model.trim="formulaire.email" type="email" autocomplete="email" class="field mt-3" :aria-invalid="!!erreurs.email" />
              <p v-if="erreurs.email" class="t-small mt-2 text-error">{{ erreurs.email }}</p>
            </div>

            <div>
              <label for="mdp-inscription" class="t-label text-ink-500">Mot de passe</label>
              <input
                id="mdp-inscription"
                v-model="formulaire.motDePasse"
                type="password"
                autocomplete="new-password"
                class="field mt-3"
                :aria-invalid="!!erreurs.motDePasse"
              />

              <!--
                La force est indiquée, jamais imposée par une liste de règles :
                c'est la consigne portée par la maquette.
              -->
              <div v-if="formulaire.motDePasse" class="mt-3">
                <div class="flex gap-1.5">
                  <span
                    v-for="segment in 4"
                    :key="segment"
                    class="h-[3px] flex-1 transition-colors duration-[200ms]"
                    :class="segment <= force.niveau ? force.couleur : 'bg-rule'"
                  />
                </div>
                <p class="t-small mt-2" :class="force.texte">
                  {{ force.libelle }} — {{ formulaire.motDePasse.length }} caractères
                </p>
              </div>
              <p v-if="erreurs.motDePasse" class="t-small mt-2 text-error">{{ erreurs.motDePasse }}</p>
            </div>

            <label class="flex items-center gap-3">
              <input v-model="formulaire.newsletter" type="checkbox" class="size-5 accent-ink-900" />
              <span class="t-body">Recevoir les nouveautés et l’accès anticipé aux collections</span>
            </label>

            <p v-if="erreurs.global" class="t-small flex items-start gap-2 bg-error/10 p-3 text-error" role="alert">
              <AlertCircle class="mt-0.5 size-4 shrink-0" />
              {{ erreurs.global }}
            </p>

            <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="envoi">
              {{ envoi ? 'Création…' : 'Créer mon compte' }}
            </button>
          </form>
        </template>

        <!-- ---------------------------------------------- Mot de passe oublié -->
        <template v-else>
          <h1 class="t-h2">Mot de passe oublié</h1>
          <p class="t-body mt-3 text-ink-700">
            Indiquez votre e-mail : vous recevez un lien valable une heure.
          </p>

          <form class="mt-10 space-y-6" novalidate @submit.prevent="demanderLien">
            <div>
              <label for="email-oubli" class="t-label text-ink-500">E-mail</label>
              <input id="email-oubli" v-model.trim="formulaire.email" type="email" autocomplete="email" class="field mt-3" :aria-invalid="!!erreurs.email" />
              <p v-if="erreurs.email" class="t-small mt-2 text-error">{{ erreurs.email }}</p>
            </div>

            <p v-if="succes" class="t-small flex items-start gap-2 bg-success/10 p-3 text-success" role="status">
              <Check class="mt-0.5 size-4 shrink-0" />
              {{ succes }}
            </p>

            <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="envoi">
              {{ envoi ? 'Envoi…' : 'Envoyer le lien' }}
            </button>

            <button type="button" class="t-body block w-full text-center text-action hover:underline" @click="basculer('connexion')">
              Revenir à la connexion
            </button>
          </form>
        </template>

        <!-- Message après inscription -->
        <p v-if="mode === 'inscription' && succes" class="t-small mt-6 flex items-start gap-2 bg-success/10 p-3 text-success" role="status">
          <Check class="mt-0.5 size-4 shrink-0" />
          {{ succes }}
        </p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AlertCircle, Check, Eye, EyeOff } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

type Mode = 'connexion' | 'inscription' | 'oubli'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const visuel = '/head1.png'

const mode = ref<Mode>(route.path === '/inscription' ? 'inscription' : 'connexion')
const envoi = ref(false)
const succes = ref<string | null>(null)
const motDePasseVisible = ref(false)

const formulaire = reactive({
  email: '',
  motDePasse: '',
  prenom: '',
  nom: '',
  newsletter: false,
})

const erreurs = reactive<Record<string, string>>({})

const vider = () => {
  Object.keys(erreurs).forEach((cle) => delete erreurs[cle])
  succes.value = null
}

const basculer = (nouveau: Mode) => {
  vider()
  mode.value = nouveau
}

/**
 * Force du mot de passe : longueur d'abord, puis variété des caractères.
 * Indicative — aucune combinaison n'est exigée.
 */
const force = computed(() => {
  const valeur = formulaire.motDePasse
  let points = 0
  if (valeur.length >= 8) points++
  if (valeur.length >= 12) points++
  if (/[a-z]/.test(valeur) && /[A-Z]/.test(valeur)) points++
  if (/\d/.test(valeur) || /[^\w\s]/.test(valeur)) points++

  const echelle = [
    { libelle: 'Trop court', couleur: 'bg-error', texte: 'text-error' },
    { libelle: 'Faible', couleur: 'bg-error', texte: 'text-error' },
    { libelle: 'Correct', couleur: 'bg-warning', texte: 'text-warning' },
    { libelle: 'Bon', couleur: 'bg-success', texte: 'text-success' },
    { libelle: 'Solide', couleur: 'bg-success', texte: 'text-success' },
  ]

  return { niveau: points, ...echelle[points] }
})

const emailValide = (valeur: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valeur)

const seConnecter = async () => {
  vider()
  if (!emailValide(formulaire.email)) erreurs.email = 'Adresse e-mail invalide.'
  if (!formulaire.motDePasse) erreurs.motDePasse = 'Mot de passe requis.'
  if (Object.keys(erreurs).length) return

  envoi.value = true
  try {
    await authStore.connexion(formulaire.email, formulaire.motDePasse)
    router.push((route.query.suite as string) || '/compte')
  } catch (e: any) {
    const statut = e?.response?.status
    // Le backend renvoie le même message pour un compte inconnu et un mauvais
    // mot de passe : on le relaie tel quel, sans en déduire davantage.
    erreurs.global =
      statut === 403
        ? 'Vérifiez votre e-mail avant de vous connecter.'
        : e?.response?.data?.error ?? 'La connexion a échoué. Réessayez.'
  } finally {
    envoi.value = false
  }
}

const sInscrire = async () => {
  vider()
  if (!formulaire.prenom) erreurs.prenom = 'Prénom requis.'
  if (!formulaire.nom) erreurs.nom = 'Nom requis.'
  if (!emailValide(formulaire.email)) erreurs.email = 'Adresse e-mail invalide.'
  if (formulaire.motDePasse.length < 6) erreurs.motDePasse = 'Au moins 6 caractères.'
  if (Object.keys(erreurs).length) return

  envoi.value = true
  try {
    const reponse = await authStore.inscription({
      email: formulaire.email,
      password: formulaire.motDePasse,
      name: `${formulaire.prenom} ${formulaire.nom}`,
      firstName: formulaire.prenom,
      lastName: formulaire.nom,
      // Le backend exige ces champs ; la maquette ne les demande pas à
      // l'inscription, ils seront complétés à la première commande.
      phone: '—',
      address: '—',
    })
    succes.value = reponse?.message ?? 'Vérifiez votre e-mail pour activer le compte.'
    formulaire.motDePasse = ''
  } catch (e: any) {
    erreurs.global = e?.response?.data?.error ?? 'La création du compte a échoué.'
  } finally {
    envoi.value = false
  }
}

const demanderLien = async () => {
  vider()
  if (!emailValide(formulaire.email)) {
    erreurs.email = 'Adresse e-mail invalide.'
    return
  }

  envoi.value = true
  try {
    const reponse = await authStore.demanderReinitialisation(formulaire.email)
    succes.value = reponse?.message ?? 'Lien envoyé. Vérifiez votre boîte de réception, et les indésirables au besoin.'
  } catch {
    // La réponse du backend est volontairement neutre : on ne révèle pas
    // si l'adresse existe.
    succes.value = 'Lien envoyé. Vérifiez votre boîte de réception, et les indésirables au besoin.'
  } finally {
    envoi.value = false
  }
}
</script>

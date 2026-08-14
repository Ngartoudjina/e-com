<template>
  <div class="min-h-screen bg-paper">
    <!--
      En-tête propre au tunnel : ni navigation ni bandeau d'annonces.
      Rien ne doit détourner d'une commande en cours.
    -->
    <header class="border-b border-rule bg-paper">
      <div class="container-page flex h-[72px] items-center gap-3">
        <RouterLink to="/panier" class="t-body flex flex-1 items-center gap-1 text-ink-700 hover:text-ink-900">
          ‹ <span class="hidden sm:inline">Retour au panier</span>
        </RouterLink>
        <RouterLink to="/" class="shrink-0 font-display text-[20px] tracking-[0.18em] sm:text-[26px]">
          GOLDSHOP
        </RouterLink>
        <p class="t-small flex flex-1 items-center justify-end gap-2 text-ink-700">
          <ShieldCheck class="size-4 text-success" />
          <span class="hidden sm:inline">Paiement sécurisé</span>
        </p>
      </div>
    </header>

    <main class="container-page pb-24">
      <div class="py-10">
        <CheckoutStepper :etapes="etapes" :courante="etapeCourante" />
      </div>

      <!-- Panier vide : il n'y a rien à payer. -->
      <div v-if="!articles.length && etapeCourante < 3" class="border-t border-rule pt-16 text-center">
        <p class="t-h2">Votre panier est vide</p>
        <RouterLink to="/catalogue" class="btn btn-lg btn-primary mt-8">Découvrir le catalogue</RouterLink>
      </div>

      <div v-else class="grid-page">
        <!-- Colonne de saisie : une seule colonne, un seul bouton par étape. -->
        <div class="col-span-4 lg:col-span-7">
          <!-- ÉTAPE 1 — Identité -->
          <section v-if="etapeCourante === 0" class="border border-rule bg-surface p-6 lg:p-10">
            <h1 class="t-h2">1 · Identité</h1>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
              <button type="button" class="btn btn-secondary" @click="externe('Google')">Continuer avec Google</button>
              <button type="button" class="btn btn-secondary" @click="externe('Apple')">Continuer avec Apple</button>
            </div>

            <div class="my-8 flex items-center gap-4">
              <span class="h-px flex-1 bg-rule" />
              <span class="t-small text-ink-500">ou</span>
              <span class="h-px flex-1 bg-rule" />
            </div>

            <form class="space-y-6" novalidate @submit.prevent="validerIdentite">
              <div>
                <label for="email" class="t-label text-ink-500">E-mail</label>
                <input
                  id="email"
                  v-model.trim="identite.email"
                  type="email"
                  autocomplete="email"
                  class="field mt-3"
                  :aria-invalid="!!erreurs.email"
                />
                <p v-if="erreurs.email" class="t-small mt-2 text-error">{{ erreurs.email }}</p>
              </div>

              <div>
                <label for="tel" class="t-label text-ink-500">Téléphone</label>
                <input
                  id="tel"
                  v-model.trim="identite.telephone"
                  type="tel"
                  autocomplete="tel"
                  class="field mt-3"
                  :aria-invalid="!!erreurs.telephone"
                />
                <p v-if="erreurs.telephone" class="t-small mt-2 text-error">{{ erreurs.telephone }}</p>
                <p v-else class="t-small mt-2 text-ink-500">Utilisé uniquement pour la livraison.</p>
              </div>

              <label class="flex items-center gap-3">
                <input v-model="identite.creerCompte" type="checkbox" class="size-5 accent-ink-900" />
                <span class="t-body">Créer un compte pour suivre mes commandes</span>
              </label>

              <button type="submit" class="btn btn-lg btn-primary w-full">Continuer vers la livraison</button>
            </form>
          </section>

          <!-- ÉTAPE 2 — Livraison -->
          <section v-else-if="etapeCourante === 1" class="border border-rule bg-surface p-6 lg:p-10">
            <h1 class="t-h2">2 · Livraison</h1>

            <form class="mt-8 space-y-6" novalidate @submit.prevent="validerLivraison">
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label for="prenom" class="t-label text-ink-500">Prénom</label>
                  <input id="prenom" v-model.trim="livraison.prenom" autocomplete="given-name" class="field mt-3" :aria-invalid="!!erreurs.prenom" />
                  <p v-if="erreurs.prenom" class="t-small mt-2 text-error">{{ erreurs.prenom }}</p>
                </div>
                <div>
                  <label for="nom" class="t-label text-ink-500">Nom</label>
                  <input id="nom" v-model.trim="livraison.nom" autocomplete="family-name" class="field mt-3" :aria-invalid="!!erreurs.nom" />
                  <p v-if="erreurs.nom" class="t-small mt-2 text-error">{{ erreurs.nom }}</p>
                </div>
              </div>

              <div>
                <label for="adresse" class="t-label text-ink-500">Adresse</label>
                <input id="adresse" v-model.trim="livraison.adresse" autocomplete="street-address" class="field mt-3" :aria-invalid="!!erreurs.adresse" />
                <p v-if="erreurs.adresse" class="t-small mt-2 text-error">{{ erreurs.adresse }}</p>
              </div>

              <div class="grid gap-4 sm:grid-cols-[200px_1fr]">
                <div>
                  <label for="cp" class="t-label text-ink-500">Code postal</label>
                  <input id="cp" v-model.trim="livraison.codePostal" inputmode="numeric" autocomplete="postal-code" data-numeric class="field mt-3" :aria-invalid="!!erreurs.codePostal" />
                  <p v-if="erreurs.codePostal" class="t-small mt-2 text-error">{{ erreurs.codePostal }}</p>
                </div>
                <div>
                  <label for="ville" class="t-label text-ink-500">Ville</label>
                  <input id="ville" v-model.trim="livraison.ville" autocomplete="address-level2" class="field mt-3" :aria-invalid="!!erreurs.ville" />
                  <p v-if="erreurs.ville" class="t-small mt-2 text-error">{{ erreurs.ville }}</p>
                </div>
              </div>

              <fieldset>
                <legend class="t-label text-ink-500">Mode de livraison</legend>
                <div class="mt-3 space-y-3">
                  <label
                    v-for="mode in modesLivraison"
                    :key="mode.cle"
                    class="flex cursor-pointer items-center gap-4 border p-4 transition-colors duration-[200ms]"
                    :class="livraison.mode === mode.cle ? 'border-ink-900' : 'border-rule hover:border-ink-300'"
                  >
                    <input v-model="livraison.mode" type="radio" :value="mode.cle" class="size-5 accent-ink-900" />
                    <span class="flex-1">
                      <span class="t-body block text-ink-900">{{ mode.libelle }}</span>
                      <span class="t-small block text-ink-500">{{ mode.detail }}</span>
                    </span>
                    <span class="t-body shrink-0" :class="mode.prix === 0 ? 'text-success' : 'text-ink-900'">
                      {{ mode.prix === 0 ? 'Offerte' : formatPrix(mode.prix) }}
                    </span>
                  </label>
                </div>
              </fieldset>

              <button type="submit" class="btn btn-lg btn-primary w-full">Continuer vers le paiement</button>
            </form>
          </section>

          <!-- ÉTAPE 3 — Paiement -->
          <template v-else-if="etapeCourante === 2">
            <!-- Récapitulatif des étapes franchies -->
            <div class="space-y-3">
              <div v-for="resume in resumes" :key="resume.libelle" class="flex items-start gap-4 border border-rule bg-surface p-5">
                <p class="t-label w-24 shrink-0 pt-1 text-ink-500">{{ resume.libelle }}</p>
                <div class="min-w-0 flex-1">
                  <p v-for="ligne in resume.lignes" :key="ligne" class="t-body text-ink-900">{{ ligne }}</p>
                </div>
                <button type="button" class="t-body shrink-0 text-action hover:underline" @click="etapeCourante = resume.etape">
                  Modifier
                </button>
              </div>
            </div>

            <section class="mt-4 border border-ink-900 bg-surface p-6 lg:p-10">
              <div class="flex flex-wrap items-baseline justify-between gap-4">
                <h1 class="t-h2">Paiement</h1>
                <p class="t-small text-ink-500">Chiffré · 3-D Secure</p>
              </div>

              <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <button type="button" class="btn btn-primary" @click="portefeuille('Apple Pay')">Apple Pay</button>
                <button type="button" class="btn btn-secondary" @click="portefeuille('PayPal')">PayPal</button>
              </div>

              <div class="my-8 flex items-center gap-4">
                <span class="h-px flex-1 bg-rule" />
                <span class="t-small text-ink-500">ou par carte</span>
                <span class="h-px flex-1 bg-rule" />
              </div>

              <form class="space-y-6" novalidate @submit.prevent="payer">
                <div>
                  <label for="carte" class="t-label text-ink-500">Numéro de carte</label>
                  <input
                    id="carte"
                    v-model="carte.numero"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="0000 0000 0000 0000"
                    data-numeric
                    class="field mt-3"
                    :aria-invalid="!!erreurs.numero"
                    @input="formaterNumero"
                  />
                  <p v-if="erreurs.numero" class="t-small mt-2 text-error">{{ erreurs.numero }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label for="exp" class="t-label text-ink-500">Expiration</label>
                    <input
                      id="exp"
                      v-model="carte.expiration"
                      inputmode="numeric"
                      autocomplete="off"
                      placeholder="MM / AA"
                      data-numeric
                      class="field mt-3"
                      :aria-invalid="!!erreurs.expiration"
                      @input="formaterExpiration"
                    />
                    <p v-if="erreurs.expiration" class="t-small mt-2 text-error">{{ erreurs.expiration }}</p>
                  </div>
                  <div>
                    <label for="cvc" class="t-label text-ink-500">Cryptogramme</label>
                    <input
                      id="cvc"
                      v-model="carte.cvc"
                      inputmode="numeric"
                      autocomplete="off"
                      maxlength="4"
                      placeholder="•••"
                      data-numeric
                      class="field mt-3"
                      :aria-invalid="!!erreurs.cvc"
                    />
                    <p v-if="erreurs.cvc" class="t-small mt-2 text-error">{{ erreurs.cvc }}</p>
                    <p v-else class="t-small mt-2 text-ink-500">Les 3 chiffres au dos de la carte.</p>
                  </div>
                </div>

                <div>
                  <label for="titulaire" class="t-label text-ink-500">Titulaire</label>
                  <input id="titulaire" v-model.trim="carte.titulaire" autocomplete="off" class="field mt-3 uppercase" :aria-invalid="!!erreurs.titulaire" />
                  <p v-if="erreurs.titulaire" class="t-small mt-2 text-error">{{ erreurs.titulaire }}</p>
                </div>

                <div class="space-y-3 border-t border-rule pt-6">
                  <label class="flex items-center gap-3">
                    <input v-model="carte.memeAdresse" type="checkbox" class="size-5 accent-ink-900" />
                    <span class="t-body">L’adresse de facturation est identique à l’adresse de livraison</span>
                  </label>
                </div>

                <p class="flex items-start gap-3 bg-rule-soft p-4">
                  <ShieldCheck class="mt-0.5 size-4 shrink-0 text-ink-700" />
                  <span class="t-small text-ink-700">
                    Vos coordonnées bancaires ne transitent jamais par nos serveurs.
                    Le débit intervient à l’expédition, pas à la commande.
                  </span>
                </p>

                <p v-if="erreurs.paiement" class="t-body bg-error/10 p-4 text-error" role="alert">
                  {{ erreurs.paiement }}
                </p>

                <!-- Seconde action bleue du site, et pour la même raison. -->
                <button type="submit" class="btn btn-lg btn-action w-full" :disabled="envoi">
                  {{ envoi ? 'Traitement…' : `Payer ${formatPrix(total)}` }}
                </button>

                <p class="t-small text-center text-ink-500">
                  En validant, vous acceptez nos
                  <RouterLink to="/aide/contact" class="text-action hover:underline">conditions de vente</RouterLink>.
                  Rétractation sous 30 jours.
                </p>
              </form>
            </section>
          </template>

          <!-- ÉTAPE 4 — Confirmation -->
          <section v-else class="border border-rule bg-surface p-10 text-center lg:p-16">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-success/12 text-success">
              <Check class="size-7" />
            </span>
            <h1 class="t-h2 mt-8">Commande confirmée</h1>
            <p class="t-body-l mx-auto mt-4 max-w-md text-ink-700">
              Un e-mail de confirmation part vers {{ identite.email }}.
              Votre colis est expédié sous 24 h.
            </p>
            <p data-numeric class="t-label mt-8 text-ink-500">Commande n° {{ numeroCommande }}</p>
            <RouterLink to="/catalogue" class="btn btn-lg btn-primary mt-10">Continuer mes achats</RouterLink>
          </section>
        </div>

        <!-- Récapitulatif de commande : 5 colonnes -->
        <aside v-if="etapeCourante < 3" class="col-span-4 mt-8 lg:col-span-5 lg:mt-0">
          <div class="border border-rule bg-surface p-6 lg:p-8">
            <div class="flex items-baseline justify-between gap-4">
              <h2 class="t-label text-ink-500">Votre commande</h2>
              <RouterLink to="/panier" class="t-small text-action hover:underline">Modifier</RouterLink>
            </div>

            <ul class="mt-6 space-y-5">
              <li v-for="article in articles" :key="`${article.id}${article.selectedSize}`" class="flex items-center gap-4">
                <span class="relative shrink-0">
                  <span class="block size-16 overflow-hidden bg-rule-soft">
                    <img v-if="article.mediaUrl" :src="article.mediaUrl" :alt="article.name" class="size-full object-cover" />
                  </span>
                  <span
                    data-numeric
                    class="absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-full bg-ink-900 text-[11px] text-white"
                  >
                    {{ article.quantity }}
                  </span>
                </span>
                <span class="min-w-0 flex-1">
                  <span class="t-body block truncate text-ink-900">{{ article.name }}</span>
                  <span class="t-small block text-ink-500">{{ article.selectedColor }} · {{ article.selectedSize }}</span>
                </span>
                <span data-numeric class="t-price shrink-0">{{ formatPrix(article.price * article.quantity) }}</span>
              </li>
            </ul>

            <dl class="mt-8 space-y-3 border-t border-rule pt-6">
              <div class="flex items-baseline justify-between gap-4">
                <dt class="t-body text-ink-700">Sous-total</dt>
                <dd data-numeric class="t-price">{{ formatPrix(sousTotal) }}</dd>
              </div>
              <div class="flex items-baseline justify-between gap-4">
                <dt class="t-body text-ink-700">Livraison {{ modeChoisi.libelle.toLowerCase() }}</dt>
                <dd class="t-body" :class="modeChoisi.prix === 0 ? 'text-success' : 'text-ink-900'">
                  {{ modeChoisi.prix === 0 ? 'Offerte' : formatPrix(modeChoisi.prix) }}
                </dd>
              </div>
            </dl>

            <div class="mt-6 flex items-baseline justify-between gap-4 border-t border-rule pt-6">
              <p class="t-h3">Total à payer</p>
              <p data-numeric class="font-display text-[30px] leading-none">{{ formatPrix(total) }}</p>
            </div>
            <p class="t-small mt-2 text-right text-ink-500">dont {{ formatPrix(tva) }} de TVA</p>
          </div>

          <ul class="space-y-4 bg-ink-900 p-6 text-white lg:p-8">
            <li class="flex items-center gap-3">
              <RotateCcw class="size-4 shrink-0" />
              <span class="t-small">Retour gratuit sous 30 jours</span>
            </li>
            <li class="flex items-center gap-3">
              <Clock class="size-4 shrink-0" />
              <span class="t-small">Un conseiller au 01 84 60 22 10</span>
            </li>
          </ul>
        </aside>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Check, Clock, RotateCcw, ShieldCheck } from 'lucide-vue-next'
import CheckoutStepper from '@/components/checkout/CheckoutStepper.vue'
import { formatPrix } from '@/lib/format'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const cartStore = useCartStore()
const toastStore = useToastStore()

const etapes = [
  { cle: 'identite', libelle: 'Identité' },
  { cle: 'livraison', libelle: 'Livraison' },
  { cle: 'paiement', libelle: 'Paiement' },
  { cle: 'confirmation', libelle: 'Confirmation' },
]

const etapeCourante = ref(0)
const envoi = ref(false)
const numeroCommande = ref('')

const identite = reactive({ email: '', telephone: '', creerCompte: false })
const livraison = reactive({ prenom: '', nom: '', adresse: '', codePostal: '', ville: '', mode: 'standard' })

/**
 * Champs de carte.
 *
 * ATTENTION — aucun prestataire de paiement n'est branché. Ces valeurs ne
 * sont ni transmises, ni journalisées, ni conservées : elles restent dans
 * l'état du composant et disparaissent au démontage. Le jour où un PSP sera
 * intégré, la saisie devra passer par ses champs hébergés (Stripe Elements
 * ou équivalent) et ce formulaire disparaître : voir le rapport associé.
 */
const carte = reactive({ numero: '', expiration: '', cvc: '', titulaire: '', memeAdresse: true })

const erreurs = reactive<Record<string, string>>({})

const modesLivraison = [
  { cle: 'standard', libelle: 'Standard · 2 à 3 jours', detail: 'Réception estimée sous 3 jours ouvrés', prix: 0 },
  { cle: 'express', libelle: 'Express · demain avant 13 h', detail: 'Commande passée avant 15 h', prix: 12 },
]

const articles = computed(() => cartStore.cartItems)
const sousTotal = computed(() => cartStore.sousTotal)
const modeChoisi = computed(() => modesLivraison.find((m) => m.cle === livraison.mode) ?? modesLivraison[0])
const total = computed(() => sousTotal.value + modeChoisi.value.prix)
const tva = computed(() => total.value - total.value / 1.2)

const resumes = computed(() => [
  {
    etape: 0,
    libelle: 'Contact',
    lignes: [identite.email, identite.telephone].filter(Boolean),
  },
  {
    etape: 1,
    libelle: 'Adresse',
    lignes: [
      `${livraison.prenom} ${livraison.nom}`.trim(),
      `${livraison.adresse}, ${livraison.codePostal} ${livraison.ville}`,
    ].filter((l) => l.trim().length > 1),
  },
  {
    etape: 1,
    libelle: 'Livraison',
    lignes: [modeChoisi.value.libelle, modeChoisi.value.detail],
  },
])

const vider = () => Object.keys(erreurs).forEach((cle) => delete erreurs[cle])

const validerIdentite = () => {
  vider()
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identite.email)) erreurs.email = 'Adresse e-mail invalide.'
  if (identite.telephone.replace(/\D/g, '').length < 8) erreurs.telephone = 'Numéro de téléphone incomplet.'
  if (Object.keys(erreurs).length) return
  etapeCourante.value = 1
}

const validerLivraison = () => {
  vider()
  if (!livraison.prenom) erreurs.prenom = 'Prénom requis.'
  if (!livraison.nom) erreurs.nom = 'Nom requis.'
  if (livraison.adresse.length < 5) erreurs.adresse = 'Adresse requise.'
  if (!/^\d{4,5}$/.test(livraison.codePostal)) erreurs.codePostal = 'Code postal invalide.'
  if (!livraison.ville) erreurs.ville = 'Ville requise.'
  if (Object.keys(erreurs).length) return
  etapeCourante.value = 2
}

/** Groupe le numéro par quatre, sans jamais le conserver ailleurs. */
const formaterNumero = () => {
  const chiffres = carte.numero.replace(/\D/g, '').slice(0, 19)
  carte.numero = chiffres.replace(/(.{4})/g, '$1 ').trim()
}

const formaterExpiration = () => {
  const chiffres = carte.expiration.replace(/\D/g, '').slice(0, 4)
  carte.expiration = chiffres.length > 2 ? `${chiffres.slice(0, 2)} / ${chiffres.slice(2)}` : chiffres
}

/** Contrôle de Luhn : rejette une coquille avant tout appel réseau. */
const luhnValide = (numero: string) => {
  const chiffres = numero.replace(/\D/g, '')
  if (chiffres.length < 13) return false
  let somme = 0
  let doubler = false
  for (let i = chiffres.length - 1; i >= 0; i--) {
    let n = Number(chiffres[i])
    if (doubler) {
      n *= 2
      if (n > 9) n -= 9
    }
    somme += n
    doubler = !doubler
  }
  return somme % 10 === 0
}

const expirationValide = (valeur: string) => {
  const [mois, annee] = valeur.split('/').map((p) => p.trim())
  const m = Number(mois)
  const a = Number(annee)
  if (!m || !a || m < 1 || m > 12) return false
  const echeance = new Date(2000 + a, m, 0, 23, 59, 59)
  return echeance >= new Date()
}

const payer = async () => {
  vider()

  if (!luhnValide(carte.numero)) erreurs.numero = 'Numéro de carte invalide.'
  if (!expirationValide(carte.expiration)) erreurs.expiration = 'Date d’expiration invalide ou dépassée.'
  if (!/^\d{3,4}$/.test(carte.cvc)) erreurs.cvc = 'Cryptogramme invalide.'
  if (carte.titulaire.length < 3) erreurs.titulaire = 'Nom du titulaire requis.'

  if (Object.keys(erreurs).length) return

  envoi.value = true
  try {
    /*
     * Aucun prestataire de paiement n'est branché : il n'y a pas d'appel
     * réseau, et surtout aucune donnée de carte n'est transmise. La commande
     * n'est donc pas réellement enregistrée.
     */
    await new Promise((r) => setTimeout(r, 600))

    numeroCommande.value = `GS-${Date.now().toString().slice(-8)}`

    // La carte est effacée de la mémoire dès la validation.
    carte.numero = ''
    carte.expiration = ''
    carte.cvc = ''
    carte.titulaire = ''

    cartStore.clearCart()
    etapeCourante.value = 3
  } catch (e) {
    console.error(e)
    erreurs.paiement = 'Le paiement n’a pas abouti. Réessayez.'
  } finally {
    envoi.value = false
  }
}

const externe = (fournisseur: string) =>
  toastStore.info(`La connexion ${fournisseur} n’est pas encore branchée.`)

const portefeuille = (nom: string) =>
  toastStore.info(`${nom} n’est pas encore branché.`)

// Un panier vidé ailleurs pendant le tunnel ramène au panier.
watch(
  () => articles.value.length,
  (nombre) => {
    if (nombre === 0 && etapeCourante.value < 3) router.push('/panier')
  }
)
</script>

<template>
  <footer class="bg-ink-900 text-white">
    <div class="container-page">
      <div class="grid-page py-24">
        <!-- Signature et inscription -->
        <div class="col-span-4 lg:col-span-4">
          <p class="font-display text-[26px] tracking-[0.18em]">GOLDSHOP</p>
          <p class="t-body mt-6 text-white/70">
            Une garde-robe courte, faite pour durer.<br />
            Nouvelles pièces le premier jeudi du mois.
          </p>

          <form class="mt-8 flex max-w-[420px]" @submit.prevent="inscrire">
            <label class="sr-only" for="footer-email">Votre e-mail</label>
            <input
              id="footer-email"
              v-model="email"
              type="email"
              placeholder="Votre e-mail"
              class="h-12 min-w-0 flex-1 border border-white/25 bg-transparent px-4 text-[15px] text-white outline-none transition-colors duration-[200ms] placeholder:text-white/45 focus:border-white"
              style="border-radius: var(--radius-2) 0 0 var(--radius-2)"
            />
            <button
              type="submit"
              :disabled="envoi"
              class="h-12 shrink-0 bg-paper px-6 text-[15px] font-medium text-ink-900 transition-colors duration-[120ms] hover:bg-white disabled:opacity-60"
              style="border-radius: 0 var(--radius-2) var(--radius-2) 0"
            >
              {{ envoi ? 'Envoi…' : "S'inscrire" }}
            </button>
          </form>

          <p v-if="retour" class="t-small mt-3" :class="erreur ? 'text-error' : 'text-success'" role="status">
            {{ retour }}
          </p>
        </div>

        <!-- Colonnes de liens -->
        <nav
          v-for="colonne in colonnes"
          :key="colonne.titre"
          class="col-span-4 mt-12 lg:col-span-2 lg:col-start-auto lg:mt-0"
          :class="colonne.titre === 'Boutique' ? 'lg:col-start-6' : ''"
        >
          <h2 class="t-label text-white/50">{{ colonne.titre }}</h2>
          <ul class="mt-6 space-y-4">
            <li v-for="lien in colonne.liens" :key="lien.libelle">
              <RouterLink
                :to="lien.to"
                class="t-body text-white/90 transition-colors duration-[120ms] hover:text-white"
              >
                {{ lien.libelle }}
              </RouterLink>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Bas de page -->
      <div class="flex flex-col gap-6 border-t border-white/12 py-8 md:flex-row md:items-center md:justify-between">
        <div class="t-small flex flex-wrap items-center gap-x-8 gap-y-3 text-white/60">
          <span>© {{ new Date().getFullYear() }} GoldShop</span>
          <a href="#" class="transition-colors hover:text-white">Mentions légales</a>
          <a href="#" class="transition-colors hover:text-white">Confidentialité</a>
          <a href="#" class="transition-colors hover:text-white">Cookies</a>

          <!--
            Entrée vers l'administration, réservée aux administrateurs.
            Le drapeau vient de /api/auth/me : rien ne s'affiche tant que la
            session n'est pas résolue, pour éviter un clignotement.
          -->
          <RouterLink
            v-if="estAdmin"
            to="/admin"
            class="inline-flex items-center gap-2 text-white transition-colors hover:text-white/70"
          >
            <Shield class="size-3.5" />
            Administration
          </RouterLink>
        </div>

        <div class="flex items-center gap-3">
          <span class="t-small text-white/60">Paiement sécurisé</span>
          <span
            v-for="moyen in moyensPaiement"
            :key="moyen"
            class="flex h-7 w-11 items-center justify-center border border-white/25 text-[9px] tracking-wider text-white/70"
            style="border-radius: var(--radius-2)"
          >
            {{ moyen }}
          </span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Shield } from 'lucide-vue-next'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

/** Tant que la session n'est pas résolue, on n'affiche rien. */
const estAdmin = computed(() => !authStore.loading && authStore.isAdmin)

const email = ref('')
const retour = ref<string | null>(null)
const erreur = ref(false)
const envoi = ref(false)

const colonnes = [
  {
    titre: 'Boutique',
    liens: [
      { libelle: 'Nouveautés', to: '/catalogue?tri=nouveautes' },
      { libelle: 'Manteaux', to: '/catalogue?categorie=Manteaux' },
      { libelle: 'Mailles', to: '/catalogue?categorie=Mailles' },
      { libelle: 'Accessoires', to: '/catalogue?categorie=Accessoires' },
      { libelle: 'Archives', to: '/catalogue?remise=1' },
    ],
  },
  {
    titre: 'Aide',
    liens: [
      { libelle: 'Livraison', to: '/aide/livraison' },
      { libelle: 'Retours & échanges', to: '/aide/retours' },
      { libelle: 'Guide des tailles', to: '/aide/tailles' },
      { libelle: 'Entretien', to: '/aide/entretien' },
      { libelle: 'Nous écrire', to: '/aide/contact' },
    ],
  },
  {
    titre: 'Maison',
    liens: [
      { libelle: 'Notre histoire', to: '/maison/histoire' },
      { libelle: 'Ateliers', to: '/maison/ateliers' },
      { libelle: 'Matières', to: '/maison/matieres' },
      { libelle: 'Boutiques', to: '/maison/boutiques' },
      { libelle: 'Presse', to: '/maison/presse' },
    ],
  },
]

const moyensPaiement = ['VISA', 'MC', 'AMEX']

const inscrire = async () => {
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    erreur.value = true
    retour.value = 'Adresse e-mail invalide.'
    return
  }

  envoi.value = true
  try {
    const reponse = await api.post('/api/subscribe', { email: email.value })
    erreur.value = false
    retour.value = reponse.data?.message ?? 'Inscription enregistrée.'
    email.value = ''
  } catch (e: any) {
    erreur.value = true
    retour.value = e?.response?.data?.error ?? "L'inscription a échoué. Réessayez."
  } finally {
    envoi.value = false
  }
}
</script>

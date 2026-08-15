<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="mx-auto max-w-[760px] px-5 py-12 lg:px-8 lg:py-20">
      <h1 class="t-screen-title text-ink-900">Questions fréquentes</h1>

      <!--
        Un <details> natif plutôt qu'un accordéon maison : il gère seul le
        clavier, l'état ouvert/fermé et la recherche dans la page.
      -->
      <div class="mt-10 border border-rule bg-surface">
        <details v-for="(item, index) in questions" :key="item.question" class="group" :class="index > 0 ? 'border-t border-rule' : ''">
          <summary
            class="flex cursor-pointer list-none items-center justify-between gap-4 p-6 transition-colors hover:bg-rule-soft/50"
          >
            <span class="t-body text-ink-900">{{ item.question }}</span>
            <span aria-hidden="true" class="t-small shrink-0 text-ink-500 group-open:hidden">+</span>
            <span aria-hidden="true" class="t-small hidden shrink-0 text-ink-500 group-open:inline">−</span>
          </summary>
          <p class="t-body px-6 pb-6 text-ink-700">{{ item.reponse }}</p>
        </details>
      </div>

      <p class="t-body mt-10 text-ink-700">
        Une question qui n’est pas ici ?
        <RouterLink to="/aide/contact" class="underline underline-offset-4 hover:text-ink-900">Écrivez-nous</RouterLink>.
      </p>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'
import { formatPrix } from '@/lib/format'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

/*
 * Les réponses qui citent un seuil, un délai ou un tarif les lisent dans les
 * réglages : figées dans le texte, elles auraient menti dès la première
 * modification faite depuis l'administration.
 */
const questions = computed(() => [
  {
    question: 'Comment passer commande ?',
    reponse:
      'Ajoutez vos pièces au panier, puis suivez le tunnel de commande. Un compte n’est pas obligatoire : une adresse e-mail suffit à recevoir le suivi.',
  },
  {
    question: 'Quels sont les délais et les frais de livraison ?',
    reponse:
      settingsStore.modes.map((mode) => `${mode.label} : ${formatPrix(mode.price)}`).join('. ') +
      `. La livraison est offerte à partir de ${formatPrix(settingsStore.franco)} d’achat.`,
  },
  {
    question: 'Puis-je retourner une pièce ?',
    reponse: `Vous disposez de ${settingsStore.reglages.returnDays ?? 30} jours après réception pour nous la renvoyer, non portée et dans son emballage d’origine.`,
  },
  {
    question: 'Comment suivre ma commande ?',
    reponse:
      'Depuis votre compte, rubrique « Mes commandes ». Si vous avez commandé sans compte, la référence figure dans l’e-mail de confirmation.',
  },
  {
    question: 'Comment vous joindre ?',
    reponse: 'Par e-mail à support@e-com.com. Nous répondons sous 24 heures ouvrées.',
  },
])

onMounted(() => settingsStore.charger())
</script>

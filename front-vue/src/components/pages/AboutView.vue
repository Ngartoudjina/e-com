<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main class="mx-auto max-w-[900px] px-5 py-12 lg:px-8 lg:py-20">
      <h1 class="t-screen-title text-ink-900">À propos</h1>
      <p class="t-body mt-6 max-w-xl text-ink-700">
        GOLDSHOP est une maison de prêt-à-porter qui produit en petites séries.
        Nous vendons directement, sans intermédiaire, pour garder la main sur la
        qualité comme sur le prix.
      </p>

      <!-- ---------------------------------------------- Équipe -->
      <section class="mt-14">
        <h2 class="t-h1 text-ink-900">L’équipe</h2>

        <ul class="mt-8 grid gap-px bg-rule sm:grid-cols-3">
          <li v-for="membre in equipe" :key="membre.nom" class="bg-surface p-6">
            <p class="t-body text-ink-900">{{ membre.nom }}</p>
            <p class="t-small mt-2 text-ink-500">{{ membre.role }}</p>
          </li>
        </ul>
      </section>

      <!-- ---------------------------------------------- Contact -->
      <section class="mt-14">
        <h2 class="t-h1 text-ink-900">Nous écrire</h2>

        <dl class="mt-8 border border-rule bg-surface">
          <div v-for="(ligne, index) in contact" :key="ligne.libelle" class="p-6" :class="index > 0 ? 'border-t border-rule' : ''">
            <dt class="t-label text-ink-500">{{ ligne.libelle }}</dt>
            <dd class="t-body mt-2 text-ink-900">
              <a v-if="ligne.lien" :href="ligne.lien" class="underline underline-offset-4 hover:text-ink-700">
                {{ ligne.valeur }}
              </a>
              <template v-else>{{ ligne.valeur }}</template>
            </dd>
          </div>
        </dl>

        <RouterLink to="/aide/contact" class="btn btn-secondary mt-8">Poser une question</RouterLink>
      </section>
    </main>

    <SiteFooter />
    <BottomTabBar />
  </div>
</template>

<script setup lang="ts">
import AnnouncementBar from '@/components/common/AnnouncementBar.vue'
import SiteHeader from '@/components/common/SiteHeader.vue'
import SiteFooter from '@/components/common/SiteFooter.vue'
import BottomTabBar from '@/components/common/BottomTabBar.vue'

const equipe = [
  { nom: 'Tchinhoun Thibaut', role: 'Direction et fondation' },
  { nom: 'Akoha Harmine', role: 'Direction marketing' },
  { nom: 'Abel Beingar', role: 'Développement' },
]

const contact = [
  { libelle: 'E-mail', valeur: 'support@e-com.com', lien: 'mailto:support@e-com.com' },
  { libelle: 'Téléphone', valeur: '+229 59 33 44 83', lien: 'tel:+22959334483' },
  { libelle: 'Adresse', valeur: '123 rue Commerce, Cotonou, Bénin', lien: null },
]
</script>

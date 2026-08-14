<template>
  <div class="min-h-screen bg-paper">
    <AnnouncementBar />
    <SiteHeader />

    <main>
      <!--
        Aucune maquette n'a été fournie pour l'accueil : ces trois sections
        restent celles d'origine et n'ont pas encore été reprises dans le
        nouveau langage. Le châssis (bandeau, en-tête, pied de page) est en
        revanche celui du reste du site, pour éviter deux navigations
        différentes selon la page.
      -->
      <FashionHero />
      <ImageGallery />
      <PaymentGatewayHero />
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
import FashionHero from '@/components/home/FashionHero.vue'
import PaymentGatewayHero from '@/components/home/PaymentGatewayHero.vue'
import ImageGallery from '@/components/home/ImageGallery.vue'
</script>

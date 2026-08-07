<template>
  <div class="min-h-screen bg-background">
    <Navbar />
    <main>
      <FashionHero />
      <ImageGallery />
      <PaymentGatewayHero />
    </main>
    <Footer />
    <FloatingActionButton />
  </div>
</template>

<script setup lang="ts">
import Navbar from '@/components/common/Navbar.vue'
import Footer from '@/components/common/Footer.vue'
import FloatingActionButton from '@/components/common/FloatingActionButton.vue'
import FashionHero from '@/components/home/FashionHero.vue'
import PaymentGatewayHero from '@/components/home/PaymentGatewayHero.vue'
import ImageGallery from '@/components/home/ImageGallery.vue'
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-indigo-50 py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    <section class="text-center mb-12 sm:mb-16">
      <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-800 mb-4">
        À Propos de Nous
      </h1>
      <p class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto">
        Découvrez l'histoire, la mission et l'équipe derrière notre plateforme e-commerce innovante.
      </p>
    </section>

    <section class="mb-12 sm:mb-16">
      <div class="text-center mb-8">
        <h2 class="text-2xl sm:text-3xl font-semibold text-slate-800">
          Qui Sommes-Nous ?
        </h2>
        <div class="w-16 h-1 bg-indigo-600 mx-auto mt-2"></div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
        <Card class="bg-white shadow-md hover:shadow-lg transition-shadow">
          <CardHeader>
            <CardTitle class="text-lg sm:text-xl text-slate-800">
              Notre Mission
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm sm:text-base text-slate-600">
              Nous visons à offrir une expérience d'achat en ligne fluide et sécurisée, avec des
              produits de qualité à des prix compétitifs.
            </p>
          </CardContent>
        </Card>
        <Card class="bg-white shadow-md hover:shadow-lg transition-shadow">
          <CardHeader>
            <CardTitle class="text-lg sm:text-xl text-slate-800">
              Notre Vision
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm sm:text-base text-slate-600">
              Devenir le leader mondial du commerce électronique en mettant l'accent sur
              l'innovation et la satisfaction client.
            </p>
          </CardContent>
        </Card>
      </div>
    </section>

    <section class="mb-12 sm:mb-16">
      <div class="text-center mb-8">
        <h2 class="text-2xl sm:text-3xl font-semibold text-slate-800">
          Notre Équipe
        </h2>
        <div class="w-16 h-1 bg-indigo-600 mx-auto mt-2"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        <Card v-for="member in team" :key="member.name" class="bg-white shadow-md hover:shadow-lg transition-shadow">
          <CardContent class="p-6 text-center">
            <div class="w-24 h-24 bg-indigo-200 rounded-full mx-auto mb-4 flex items-center justify-center">
              <Users class="h-12 w-12 text-indigo-600" />
            </div>
            <h3 class="text-lg font-semibold text-slate-800">{{ member.name }}</h3>
            <p class="text-sm text-slate-600">{{ member.role }}</p>
          </CardContent>
        </Card>
      </div>
    </section>

    <section class="mb-12 sm:mb-16">
      <div class="text-center mb-8">
        <h2 class="text-2xl sm:text-3xl font-semibold text-slate-800">
          Contactez-Nous
        </h2>
        <div class="w-16 h-1 bg-indigo-600 mx-auto mt-2"></div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
        <Card class="bg-white shadow-md hover:shadow-lg transition-shadow">
          <CardContent class="p-6 text-center">
            <Mail class="h-8 w-8 text-indigo-600 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-slate-800">Email</h3>
            <p class="text-sm text-slate-600">support@e-com.com</p>
          </CardContent>
        </Card>
        <Card class="bg-white shadow-md hover:shadow-lg transition-shadow">
          <CardContent class="p-6 text-center">
            <Phone class="h-8 w-8 text-indigo-600 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-slate-800">Téléphone</h3>
            <p class="text-sm text-slate-600">+229 59 33 44 83</p>
          </CardContent>
        </Card>
        <Card class="bg-white shadow-md hover:shadow-lg transition-shadow">
          <CardContent class="p-6 text-center">
            <MapPin class="h-8 w-8 text-indigo-600 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-slate-800">Adresse</h3>
            <p class="text-sm text-slate-600">123 Rue Commerce, Cotonou, Bénin</p>
          </CardContent>
        </Card>
      </div>
      <div class="text-center mt-8">
        <Button class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white hover:from-indigo-600 hover:to-indigo-700">
          <RouterLink to="/">Nous Contacter</RouterLink>
        </Button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Users, Mail, Phone, MapPin } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, Button } from '@/components/ui/index'

const team = [
  { name: 'Tchinhoun Thibaut', role: 'CEO & Fondateur' },
  { name: 'Akoha Harmine', role: 'Directrice Marketing' },
  { name: 'Abel Beingar', role: 'Développeur Principal' },
]
</script>

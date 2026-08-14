<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-indigo-50 py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    <section class="text-center mb-12 sm:mb-16">
      <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-800 mb-4">
        Bienvenue sur Notre Blog
      </h1>
      <p class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto">
        Restez informé avec nos derniers articles sur le e-commerce, la technologie et le marketing.
      </p>
      <Button class="mt-6 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white hover:from-indigo-600 hover:to-indigo-700">
        <RouterLink to="/catalogue">Explorer les articles</RouterLink>
      </Button>
    </section>

    <section class="mb-12 sm:mb-16">
      <div class="flex flex-wrap justify-center gap-2 mb-6">
        <Button
          v-for="category in categories"
          :key="category"
          :variant="selectedCategory === category ? 'default' : 'outline'"
          @click="selectedCategory = category"
          class="text-sm sm:text-base"
        >
          <Filter class="w-4 h-4 mr-2" />
          {{ category }}
        </Button>
      </div>
    </section>

    <section id="articles" class="mb-12 sm:mb-16">
      <div class="text-center mb-8">
        <h2 class="text-2xl sm:text-3xl font-semibold text-slate-800">
          Derniers Articles
        </h2>
        <div class="w-16 h-1 bg-indigo-600 mx-auto mt-2"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        <Card v-for="post in filteredPosts" :key="post.id" class="bg-white shadow-md hover:shadow-lg transition-shadow overflow-hidden">
          <img :src="post.image" :alt="post.title" class="w-full h-48 object-cover" />
          <CardHeader>
            <CardTitle class="text-lg sm:text-xl text-slate-800 line-clamp-2">
              {{ post.title }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-slate-600 mb-2 line-clamp-3">
              {{ post.excerpt }}
            </p>
            <p class="text-xs text-slate-500">{{ post.date }}</p>
            <Button class="mt-4 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white hover:from-indigo-600 hover:to-indigo-700 w-full">
              <RouterLink :to="`/blog/${post.id}`">Lire plus</RouterLink>
            </Button>
          </CardContent>
        </Card>
      </div>
    </section>

    <section class="text-center mb-12 sm:mb-16 bg-indigo-100 p-6 sm:p-8 rounded-xl">
      <h2 class="text-2xl sm:text-3xl font-semibold text-slate-800 mb-4">
        Rejoignez Notre Communauté
      </h2>
      <p class="text-sm sm:text-base text-slate-600 mb-6 max-w-xl mx-auto">
        Abonnez-vous pour recevoir nos derniers articles ou contribuez en écrivant pour nous !
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <Button class="bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700">
          <Mail class="mr-2 h-4 w-4" />
          S'abonner
        </Button>
        <Button variant="outline" class="border-indigo-600 text-indigo-600 hover:bg-indigo-50">
          <PenSquare class="mr-2 h-4 w-4" />
          Écrire un article
        </Button>
      </div>
    </section>

    <footer class="text-center text-sm text-slate-600">
      <p>
        © {{ new Date().getFullYear() }} E-com. Tous droits réservés. |
        <RouterLink to="/propos" class="text-indigo-600 hover:underline ml-2">
          À propos
        </RouterLink>
      </p>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, Button } from '@/components/ui/index'
import { Filter, PenSquare, Mail } from 'lucide-vue-next'

const blogPosts = [
  {
    id: 1,
    title: 'Les Tendances du E-commerce en 2025',
    excerpt: 'Découvrez les innovations qui redéfinissent le commerce en ligne cette année.',
    date: '2025-07-10',
    image: '/blog1.jpeg',
    category: 'E-commerce',
  },
  {
    id: 2,
    title: 'Comment Optimiser Votre Site Web',
    excerpt: 'Des astuces pour améliorer les performances et l\'expérience utilisateur.',
    date: '2025-07-05',
    image: '/blog2.jpg',
    category: 'Technologie',
  },
  {
    id: 3,
    title: 'Les Meilleures Stratégies Marketing',
    excerpt: 'Explorez les techniques qui boostent vos campagnes marketing.',
    date: '2025-06-28',
    image: '/blog3.jpg',
    category: 'Marketing',
  },
]

const categories = ['Tous', 'E-commerce', 'Technologie', 'Marketing']
const selectedCategory = ref('Tous')

const filteredPosts = computed(() =>
  selectedCategory.value === 'Tous'
    ? blogPosts
    : blogPosts.filter((post) => post.category === selectedCategory.value)
)
</script>

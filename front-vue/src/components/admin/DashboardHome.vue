<template>
  <div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="absolute -left-16 top-0 h-52 w-52 rounded-full bg-indigo-400/10 blur-3xl" />
      <div class="absolute -right-10 top-20 h-64 w-64 rounded-full bg-fuchsia-400/10 blur-3xl" />
      <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl" />
    </div>

    <div class="relative z-10 space-y-6">
      <header class="rounded-[28px] border border-slate-200/80 bg-white/75 p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)] backdrop-blur-xl sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-indigo-700">
              <Activity class="h-3.5 w-3.5" />
              Overview
            </div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
              Tableau de bord
            </h2>
            <p class="mt-1 text-sm text-slate-600">Aperçu de votre activité en temps réel</p>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
          >
            <Eye class="h-4 w-4" />
            Voir tout
            <ArrowUpRight class="h-4 w-4" />
          </button>
        </div>
      </header>

      <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="(stat, index) in stats"
          :key="stat.title"
          class="group relative overflow-hidden rounded-[24px] border border-slate-200/80 bg-white/80 p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(15,23,42,0.08)]"
        >
          <div class="absolute right-4 top-4 h-12 w-12 rounded-full opacity-20 blur-xl" :class="stat.glow" />
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-medium text-slate-500">{{ stat.title }}</p>
              <p class="mt-4 text-3xl font-black tracking-tight text-slate-900" :class="{ 'animate-pulse': animatingStats }">
                {{ stat.value }}
              </p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/50 shadow-sm" :class="stat.bgColor">
              <component :is="stat.icon" class="h-5 w-5" :class="stat.textColor" />
            </div>
          </div>

          <div class="mt-5 flex items-center gap-2 rounded-xl bg-slate-50 px-2.5 py-2">
            <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
            <p class="text-xs font-medium text-slate-600">{{ stat.change }}</p>
          </div>
        </article>
      </section>

      <section class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/80 shadow-[0_18px_45px_rgba(15,23,42,0.06)] backdrop-blur-xl">
        <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 via-white to-indigo-50/60 px-5 py-4 sm:px-6">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                  <Activity class="h-4 w-4" />
                </span>
                Activité récente
              </h3>
              <p class="mt-1 text-sm text-slate-500">Dernières actions et événements du magasin</p>
            </div>
          </div>
        </div>

        <div class="divide-y divide-slate-200/80">
          <div
            v-for="activity in recentActivities"
            :key="activity.id"
            class="group px-5 py-4 transition-colors hover:bg-slate-50/80 sm:px-6"
          >
            <div class="flex items-center gap-4">
              <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 group-hover:bg-indigo-50">
                <div class="absolute -left-1 top-1/2 h-2 w-2 -translate-y-1/2 rounded-full" :class="activity.color" />
                <component :is="activity.icon" class="h-4 w-4 text-slate-600 group-hover:text-indigo-600" />
              </div>

              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-800 group-hover:text-indigo-600">{{ activity.title }}</p>
                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                  <Clock class="h-3 w-3" />
                  {{ activity.time }}
                </div>
              </div>

              <Badge :class="activity.badgeClass">{{ activity.value }}</Badge>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, markRaw, onBeforeUnmount, onMounted, ref, type Component } from 'vue'
import { Package, ShoppingCart, Users, BarChart3, TrendingUp, DollarSign, Eye, ArrowUpRight, Clock, Activity } from 'lucide-vue-next'
import { Badge } from '@/components/ui/index'

interface FloatingCircle {
  id: number
  x: number
  y: number
  size: number
  opacity: number
  color: string
}

const floatingCircles = ref<FloatingCircle[]>([])
const animatingStats = ref(false)
let interval: ReturnType<typeof setInterval> | undefined

interface Stat {
  title: string
  value: string
  change: string
  icon: Component
  bgColor: string
  textColor: string
  glow: string
}

const stats: Stat[] = [
  { title: 'Revenus Total', value: '€45,231.89', change: '+20.1% par rapport au mois dernier', icon: markRaw(DollarSign), bgColor: 'bg-emerald-50 border-emerald-100', textColor: 'text-emerald-700', glow: 'bg-emerald-400/60' },
  { title: 'Commandes', value: '+2,350', change: '+180.1% par rapport au mois dernier', icon: markRaw(ShoppingCart), bgColor: 'bg-blue-50 border-blue-100', textColor: 'text-blue-700', glow: 'bg-blue-400/60' },
  { title: 'Produits Vendus', value: '+12,234', change: '+19% par rapport au mois dernier', icon: markRaw(Package), bgColor: 'bg-violet-50 border-violet-100', textColor: 'text-violet-700', glow: 'bg-violet-400/60' },
  { title: 'Utilisateurs Actifs', value: '+573', change: '+201 depuis la semaine dernière', icon: markRaw(Users), bgColor: 'bg-pink-50 border-pink-100', textColor: 'text-pink-700', glow: 'bg-pink-400/60' },
]

interface Activity {
  id: number
  title: string
  time: string
  value: string
  color: string
  badgeClass: string
  icon: Component
}

const recentActivities: Activity[] = [
  { id: 1, title: 'Nouvelle commande reçue', time: 'Il y a 2 minutes', value: '€125.00', color: 'bg-indigo-500', badgeClass: 'bg-indigo-100 text-indigo-700 border-indigo-200', icon: markRaw(ShoppingCart) },
  { id: 2, title: 'Nouvel utilisateur inscrit', time: 'Il y a 15 minutes', value: 'Nouveau', color: 'bg-blue-500', badgeClass: 'bg-blue-100 text-blue-700 border-blue-200', icon: markRaw(Users) },
  { id: 3, title: 'Produit mis à jour', time: 'Il y a 1 heure', value: 'Modifié', color: 'bg-amber-500', badgeClass: 'bg-amber-100 text-amber-700 border-amber-200', icon: markRaw(Package) },
  { id: 4, title: 'Rapport généré', time: 'Il y a 2 heures', value: 'PDF', color: 'bg-emerald-500', badgeClass: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: markRaw(BarChart3) },
]

const colors = ['bg-indigo-300', 'bg-blue-300', 'bg-purple-300', 'bg-pink-300']

onMounted(() => {
  for (let i = 0; i < 15; i++) {
    floatingCircles.value.push({
      id: i,
      x: Math.random() * 100,
      y: Math.random() * 100,
      size: Math.random() * 4 + 2,
      opacity: Math.random() * 0.3 + 0.1,
      color: colors[Math.floor(Math.random() * 4)],
    })
  }
  interval = setInterval(() => {
    animatingStats.value = true
    setTimeout(() => (animatingStats.value = false), 2000)
  }, 8000)
})

onBeforeUnmount(() => {
  clearInterval(interval)
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">
    <div class="sticky top-0 z-10 bg-white/80 backdrop-blur-sm border-b border-slate-200/50 p-3 sm:p-6">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-slate-800 to-indigo-600 bg-clip-text text-transparent">
            Commandes
          </h2>
          <p class="text-sm sm:text-base text-slate-600 mt-1">Gérez toutes vos commandes en temps réel</p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-3 w-full sm:w-auto">
          <Button variant="outline" size="sm" class="w-full sm:w-auto border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-sm py-2">
            <Search class="h-4 w-4 mr-2" />
            Rechercher
          </Button>
          <Button variant="outline" size="sm" class="w-full sm:w-auto border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-sm py-2">
            <Filter class="h-4 w-4 mr-2" />
            Filtrer
          </Button>
          <Button class="w-full sm:w-auto bg-gradient-to-r from-indigo-500 to-indigo-600 text-white hover:from-indigo-600 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all duration-300 text-sm py-2">
            <ShoppingCart class="mr-2 h-4 w-4" />
            Nouvelle commande
          </Button>
        </div>
      </div>
    </div>

    <div class="p-3 sm:p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div
          v-for="stat in orderStats"
          :key="stat.title"
          class="bg-white rounded-lg p-3 sm:p-4 shadow-sm border border-slate-200/50 hover:shadow-md transition-all duration-200"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs sm:text-sm font-medium text-slate-600">{{ stat.title }}</p>
              <p class="text-lg sm:text-2xl font-bold text-slate-800 mt-1">{{ stat.value }}</p>
            </div>
            <div class="p-2 sm:p-3 rounded-full" :class="stat.bg">
              <component :is="stat.icon" class="h-4 w-4 sm:h-6 sm:w-6" :class="stat.color" />
            </div>
          </div>
        </div>
      </div>

      <Card class="bg-white/70 backdrop-blur-sm shadow-xl border-slate-200/50">
        <CardHeader class="bg-gradient-to-r from-white to-indigo-50/50 border-b border-slate-200/50 p-3 sm:p-4">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-lg sm:text-xl font-semibold text-slate-800">Commandes Récentes</CardTitle>
              <CardDescription class="text-sm text-slate-600 mt-1">{{ orders.length }} commandes trouvées</CardDescription>
            </div>
            <Button variant="ghost" size="sm" class="text-slate-600 hover:text-slate-800 p-1 sm:p-2">
              <MoreVertical class="h-4 w-4" />
            </Button>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <div class="divide-y divide-slate-200/50">
            <div
              v-for="order in orders"
              :key="order.id"
              class="p-3 sm:p-6 cursor-pointer group hover:bg-slate-50/50 transition-colors"
              @click="toggleOrder(order.id)"
            >
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-3 w-full">
                  <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-indigo-100 to-indigo-200 rounded-full flex items-center justify-center">
                    <span class="text-xs sm:text-sm font-semibold text-indigo-700">#{{ order.id }}</span>
                  </div>
                  <div class="flex-1">
                    <p class="font-semibold text-sm sm:text-base text-slate-800 group-hover:text-indigo-600 transition-colors">
                      Commande #{{ order.number }}
                    </p>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Client: {{ order.client }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ order.date }}</p>
                  </div>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full sm:w-auto">
                  <Badge :class="getStatusConfig(order.status).className" class="text-xs sm:text-sm py-1 px-2">
                    <component :is="getStatusConfig(order.status).icon" class="h-3 w-3 mr-1" />
                    {{ getStatusConfig(order.status).label }}
                  </Badge>
                  <div class="text-right w-full sm:w-auto">
                    <p class="font-semibold text-sm sm:text-base text-slate-800">€{{ order.amount.toFixed(2) }}</p>
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 mt-1 sm:mt-0">
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-1 text-slate-400 hover:text-indigo-600">
                        <ArrowUpRight class="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="selectedOrder === order.id"
                class="mt-3 sm:mt-4 pt-2 sm:pt-4 border-t border-slate-200/50"
              >
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-4 text-xs sm:text-sm">
                  <div class="bg-slate-50 rounded-lg p-2 sm:p-3">
                    <p class="font-medium text-slate-700">Statut</p>
                    <p class="text-slate-600 mt-1">{{ getStatusConfig(order.status).label }}</p>
                  </div>
                  <div class="bg-slate-50 rounded-lg p-2 sm:p-3">
                    <p class="font-medium text-slate-700">Montant</p>
                    <p class="text-slate-600 mt-1">€{{ order.amount.toFixed(2) }}</p>
                  </div>
                  <div class="bg-slate-50 rounded-lg p-2 sm:p-3">
                    <p class="font-medium text-slate-700">Date</p>
                    <p class="text-slate-600 mt-1">{{ order.date }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { markRaw, ref, type Component } from 'vue'
import { Package, ShoppingCart, DollarSign, Search, Filter, MoreVertical, ArrowUpRight, Clock, CheckCircle } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Badge, Button } from '@/components/ui/index'

interface Order {
  id: number
  number: string
  client: string
  status: 'delivered' | 'processing' | 'pending'
  amount: number
  date: string
}

interface StatusConfig {
  label: string
  className: string
  icon: Component
}

const orders: Order[] = [
  { id: 1, number: '10001', client: 'Sophie Martin', status: 'delivered', amount: 245.5, date: '2024-07-07' },
  { id: 2, number: '10002', client: 'Thomas Dubois', status: 'pending', amount: 189.99, date: '2024-07-06' },
  { id: 3, number: '10003', client: 'Marie Lefebvre', status: 'delivered', amount: 320.75, date: '2024-07-05' },
  { id: 4, number: '10004', client: 'Pierre Moreau', status: 'processing', amount: 156.3, date: '2024-07-04' },
  { id: 5, number: '10005', client: 'Julie Rousseau', status: 'delivered', amount: 298.45, date: '2024-07-03' },
]

const selectedOrder = ref<number | null>(null)

const getStatusConfig = (status: Order['status']): StatusConfig => {
  switch (status) {
    case 'delivered':
      return { label: 'Livré', className: 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100', icon: markRaw(CheckCircle) }
    case 'processing':
      return { label: 'En traitement', className: 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100', icon: markRaw(Clock) }
    default:
      return { label: 'En attente', className: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100', icon: markRaw(Package) }
  }
}

const orderStats: { title: string; value: string; icon: Component; color: string; bg: string }[] = [
  { title: 'Total commandes', value: '1,234', icon: markRaw(ShoppingCart), color: 'text-indigo-600', bg: 'bg-indigo-50' },
  { title: 'Commandes livrées', value: '987', icon: markRaw(CheckCircle), color: 'text-emerald-600', bg: 'bg-emerald-50' },
  { title: 'En traitement', value: '156', icon: markRaw(Clock), color: 'text-amber-600', bg: 'bg-amber-50' },
  { title: 'Revenus', value: '€45,678', icon: markRaw(DollarSign), color: 'text-purple-600', bg: 'bg-purple-50' },
]

const toggleOrder = (id: number) => {
  selectedOrder.value = selectedOrder.value === id ? null : id
}
</script>

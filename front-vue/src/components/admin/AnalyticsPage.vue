<template>
  <div class="space-y-6 p-4 bg-gradient-to-br from-slate-50 via-white to-blue-50 min-h-screen">
    <div class="flex flex-col items-start gap-3">
      <div class="space-y-2">
        <div class="flex items-center gap-2">
          <div class="p-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl">
            <BarChart3 class="h-5 w-5 text-white" />
          </div>
          <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">
              Tableau Analytique
            </h1>
            <p class="text-slate-600 text-sm">Performances en temps réel</p>
          </div>
        </div>
      </div>

      <button
        type="button"
        class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 text-sm py-2 px-3 rounded-md inline-flex items-center"
      >
        <Download class="mr-1 h-3 w-3" />
        Exporter
      </button>
    </div>

    <div class="grid grid-cols-1 gap-4">
      <div
        v-for="stat in statsData"
        :key="stat.title"
        class="bg-white/80 backdrop-blur-sm rounded-lg border-0 shadow-md hover:shadow-xl transition-all duration-300"
      >
        <div class="p-4">
          <div class="flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs text-slate-600 font-medium">{{ stat.title }}</p>
              <p class="text-xl font-bold text-slate-900">{{ stat.value }}</p>
              <Badge variant="secondary" class="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs">
                {{ stat.change }}
              </Badge>
            </div>
            <div class="p-2 rounded-xl bg-slate-50">
              <component :is="stat.icon" class="h-5 w-5" :class="stat.color" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
      <Card class="bg-white/80 backdrop-blur-sm border-0 shadow-lg hover:shadow-xl transition-all duration-300">
        <CardHeader class="pb-3">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                <TrendingUp class="h-4 w-4 text-blue-600" />
                Ventes Mensuelles
              </CardTitle>
              <CardDescription class="text-sm text-slate-600 mt-1">Évolution des ventes</CardDescription>
            </div>
            <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 text-xs">+12.5%</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl border border-blue-100/50">
            <div class="text-center space-y-3">
              <TrendingUp class="h-14 w-14 text-blue-400 mx-auto hidden md:block" />
              <div>
                <p class="text-base font-medium text-slate-700">Graphique Ventes</p>
                <p class="text-xs text-slate-500">Données temps réel</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-white/80 backdrop-blur-sm border-0 shadow-lg hover:shadow-xl transition-all duration-300">
        <CardHeader class="pb-3">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                <Users class="h-4 w-4 text-purple-600" />
                Trafic Utilisateurs
              </CardTitle>
              <CardDescription class="text-sm text-slate-600 mt-1">Analyse du trafic</CardDescription>
            </div>
            <Badge variant="outline" class="bg-purple-50 text-purple-700 border-purple-200 text-xs">+15.3%</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border border-purple-100/50">
            <div class="text-center space-y-3">
              <Users class="h-14 w-14 text-purple-400 mx-auto hidden md:block" />
              <div>
                <p class="text-base font-medium text-slate-700">Stats Trafic</p>
                <p class="text-xs text-slate-500">Analytics avancées</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card class="bg-white/80 backdrop-blur-sm border-0 shadow-lg hover:shadow-xl transition-all duration-300">
      <CardHeader class="pb-4">
        <div class="flex items-center gap-2">
          <div class="p-2 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl">
            <Mail class="h-4 w-4 text-white" />
          </div>
          <div>
            <CardTitle class="text-lg font-semibold text-slate-800 flex items-center gap-2">
              Campagne Email
              <Sparkles class="h-3 w-3 text-yellow-500" />
            </CardTitle>
            <CardDescription class="text-sm text-slate-600 mt-1">Notification abonnés</CardDescription>
          </div>
        </div>
      </CardHeader>

      <Separator class="mb-4" />

      <CardContent>
        <form @submit.prevent="handleSendEmail" class="space-y-4">
          <div class="space-y-1">
            <label for="subject" class="block text-xs font-semibold text-slate-700">Sujet</label>
            <Input
              id="subject"
              v-model="subject"
              class="border-slate-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-sm"
              placeholder="Sujet accrocheur..."
              :disabled="isSending"
            />
          </div>

          <div class="space-y-1">
            <label for="message" class="block text-xs font-semibold text-slate-700">Message</label>
            <Textarea
              id="message"
              v-model="message"
              class="border-slate-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 min-h-[100px] text-sm"
              placeholder="Message personnalisé..."
              :disabled="isSending"
            />
          </div>

          <button
            type="submit"
            class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 h-10 text-sm rounded-md inline-flex items-center justify-center gap-2 disabled:opacity-50"
            :disabled="isSending"
          >
            <template v-if="isSending">
              <div class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" />
              Envoi...
            </template>
            <template v-else>
              <Send class="h-3 w-3" />
              Envoyer
            </template>
          </button>
        </form>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { markRaw, ref, type Component } from 'vue'
import { Users, BarChart3, TrendingUp, Mail, Download, Send, Activity, Eye, ShoppingCart, DollarSign, Sparkles } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Badge, Separator, Input, Textarea } from '@/components/ui/index'
import { api } from '@/lib/api'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()
const isSending = ref(false)
const subject = ref('')
const message = ref('')

const statsData: { title: string; value: string; change: string; icon: Component; color: string }[] = [
  { title: 'Revenus', value: '€24,580', change: '+12.5%', icon: markRaw(DollarSign), color: 'text-emerald-600' },
  { title: 'Commandes', value: '1,234', change: '+8.2%', icon: markRaw(ShoppingCart), color: 'text-blue-600' },
  { title: 'Visiteurs', value: '8,549', change: '+15.3%', icon: markRaw(Eye), color: 'text-purple-600' },
  { title: 'Conversion', value: '3.2%', change: '+2.1%', icon: markRaw(Activity), color: 'text-orange-600' },
]

const handleSendEmail = async () => {
  if (!subject.value || !message.value) {
    toastStore.error('Veuillez remplir sujet et message.')
    return
  }

  isSending.value = true
  try {
    const response = await api.post('/api/send-bulk-email', {
      subject: subject.value,
      message: message.value,
    })
    toastStore.success(response.data?.message || 'Email envoyé !')
    subject.value = ''
    message.value = ''
  } catch (error: any) {
    toastStore.error(error.response?.data?.error || 'Erreur survenue.')
  } finally {
    isSending.value = false
  }
}
</script>

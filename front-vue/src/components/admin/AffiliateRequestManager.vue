<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
      <div
        v-for="particle in floatingParticles"
        :key="particle.id"
        class="absolute rounded-full blur-sm hidden md:block"
        :class="particle.color"
        :style="{
          width: particle.size + 'px',
          height: particle.size + 'px',
          left: particle.x + '%',
          top: particle.y + '%',
          opacity: particle.opacity,
        }"
      />
    </div>

    <div class="absolute inset-0 opacity-[0.02] pointer-events-none">
      <div
        class="h-full w-full"
        :style="{
          backgroundImage: 'radial-gradient(circle at 1px 1px, rgba(99, 102, 241, 0.8) 1px, transparent 0)',
          backgroundSize: '24px 24px',
        }"
      />
    </div>

    <div class="relative z-10 p-4">
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-3 mb-4">
          <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg">
            <Users class="h-8 w-8 text-white" />
          </div>
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
              Gestion des Affiliations
            </h1>
            <p class="text-slate-600 text-sm">Administrez les demandes d'adhésion avec style</p>
          </div>
        </div>
      </div>

      <div class="flex overflow-x-auto gap-3 mb-8 snap-x snap-mandatory scrollbar-hide">
        <button
          v-for="tab in tabs"
          :key="tab"
          type="button"
          class="relative overflow-hidden group shrink-0 snap-center flex-shrink-0 transition-all duration-500 w-36 py-3 text-sm rounded-2xl font-medium border"
          :class="activeTab === tab
            ? `bg-gradient-to-r ${getTabConfig(tab).gradient} text-white shadow-xl border-0`
            : `bg-white/80 backdrop-blur-sm ${getTabConfig(tab).borderColor} ${getTabConfig(tab).textColor} hover:bg-gradient-to-r hover:${getTabConfig(tab).bgGradient} hover:shadow-lg`"
          @click="activeTab = tab"
        >
          <div class="relative flex items-center gap-2">
            <component :is="getTabConfig(tab).icon" class="h-4 w-4" />
            <div class="text-left">
              <div class="font-semibold text-xs">{{ getTabConfig(tab).label }}</div>
              <div class="text-[10px] opacity-80">{{ filteredRequests.length }} demande{{ filteredRequests.length > 1 ? 's' : '' }}</div>
            </div>
            <span
              v-if="filteredRequests.length > 0"
              class="bg-white/30 text-[10px] px-1.5 py-0.5 rounded-full font-bold"
            >
              {{ filteredRequests.length }}
            </span>
          </div>
        </button>
      </div>

      <Card class="bg-white/90 backdrop-blur-xl shadow-2xl border-0 overflow-hidden rounded-3xl">
        <CardHeader class="bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border-b border-slate-200/50 p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg">
                <User class="h-5 w-5 text-white" />
              </div>
              <div>
                <CardTitle class="text-xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                  {{ getTabConfig(activeTab).label }}
                </CardTitle>
                <CardDescription class="text-sm text-slate-600 mt-1">
                  {{ getTabConfig(activeTab).description }}
                </CardDescription>
              </div>
            </div>
            <div class="flex items-center gap-1 bg-white/60 rounded-full px-3 py-1">
              <Star class="h-3 w-3 text-amber-500" />
              <span class="text-xs font-medium text-slate-700">{{ filteredRequests.length }}</span>
            </div>
          </div>
        </CardHeader>

        <CardContent class="p-4">
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="search" class="text-slate-700 font-semibold text-sm flex items-center gap-2">
                <Search class="h-4 w-4 text-indigo-600" />
                Recherche
              </Label>
              <div class="relative">
                <Input
                  id="search"
                  v-model="searchTerm"
                  placeholder="UID, raison, mots-clés..."
                  class="pl-10 pr-10 py-3 border-2 border-slate-200 focus:border-indigo-500 rounded-2xl text-sm bg-white/80 backdrop-blur-sm transition-all duration-300 focus:shadow-lg"
                />
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                <button
                  v-if="searchTerm"
                  type="button"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                  @click="searchTerm = ''"
                >
                  <XCircle class="h-4 w-4" />
                </button>
              </div>
            </div>

            <div v-if="loading" class="flex flex-col items-center justify-center py-12">
              <Loader2 class="h-10 w-10 text-indigo-600 animate-spin mb-4" />
              <p class="text-slate-600 text-base">Chargement...</p>
            </div>

            <div v-else-if="filteredRequests.length > 0" class="grid grid-cols-1 gap-4">
              <div
                v-for="(request, index) in filteredRequests"
                :key="request.id"
                class="bg-gradient-to-br from-white to-slate-50 hover:shadow-2xl transition-all duration-500 border-2 border-slate-100 hover:border-indigo-200 rounded-2xl overflow-hidden group cursor-pointer"
              >
                <div class="p-4">
                  <div class="flex flex-col gap-3">
                    <div class="relative self-start">
                      <div class="w-16 h-16 bg-gradient-to-br from-slate-100 to-slate-200 rounded-2xl overflow-hidden shadow-lg border-2 border-white">
                        <img
                          v-if="request.identityCardUrl"
                          :src="request.identityCardUrl"
                          alt="Carte d'identité"
                          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                          <User class="h-6 w-6 text-slate-400" />
                        </div>
                      </div>
                      <div class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <Sparkles class="h-2 w-2 text-white" />
                      </div>
                    </div>

                    <div class="flex-1">
                      <h4 class="text-base font-bold text-slate-800 mb-1 flex items-center gap-2">
                        <span class="text-indigo-600">#</span>
                        {{ request.uid }}
                      </h4>
                      <p class="text-sm text-slate-600 mb-2 leading-relaxed">
                        {{ expandedCard === request.id ? request.reason : request.reason.substring(0, 60) + (request.reason.length > 60 ? '...' : '') }}
                        <button
                          v-if="request.reason.length > 60"
                          type="button"
                          class="ml-2 text-indigo-600 hover:text-indigo-800 font-medium text-xs"
                          @click="expandedCard = expandedCard === request.id ? null : request.id"
                        >
                          {{ expandedCard === request.id ? 'Moins' : 'Plus' }}
                        </button>
                      </p>
                      <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
                        <Calendar class="h-3 w-3" />
                        <span>{{ formatDate(request.createdAt) }}</span>
                      </div>

                      <div class="flex flex-wrap gap-2">
                        <template v-if="activeTab === 'pending'">
                          <button
                            type="button"
                            class="bg-gradient-to-r from-emerald-500 to-green-600 text-white hover:from-emerald-600 hover:to-green-700 shadow-lg hover:shadow-xl transition-all duration-300 rounded-xl px-3 py-1 text-xs inline-flex items-center"
                            @click="handleApprove(request.id, request.uid, request.identityCardUrl)"
                          >
                            <CheckCircle class="mr-1 h-3 w-3" />
                            Approuver
                          </button>
                          <button
                            type="button"
                            class="bg-gradient-to-r from-rose-500 to-red-600 text-white hover:from-rose-600 hover:to-red-700 shadow-lg hover:shadow-xl transition-all duration-300 rounded-xl px-3 py-1 text-xs inline-flex items-center"
                            @click="handleReject(request.id)"
                          >
                            <XCircle class="mr-1 h-3 w-3" />
                            Rejeter
                          </button>
                        </template>
                        <template v-if="activeTab === 'approved' || activeTab === 'rejected'">
                          <button
                            type="button"
                            class="bg-gradient-to-r from-slate-500 to-slate-600 text-white hover:from-slate-600 hover:to-slate-700 shadow-lg hover:shadow-xl transition-all duration-300 rounded-xl px-3 py-1 text-xs inline-flex items-center"
                            @click="handleDelete(request.id)"
                          >
                            <Trash2 class="mr-1 h-3 w-3" />
                            Supprimer
                          </button>
                        </template>
                        <button
                          v-if="request.identityCardUrl"
                          type="button"
                          class="border-2 border-indigo-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300 shadow-lg hover:shadow-xl transition-all duration-300 rounded-xl px-3 py-1 text-xs inline-flex items-center"
                          @click="openDocument(request.identityCardUrl)"
                        >
                          <Eye class="mr-1 h-3 w-3" />
                          Document
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <div class="w-20 h-20 mx-auto bg-gradient-to-br from-slate-100 to-slate-200 rounded-full flex items-center justify-center shadow-lg mb-4">
                <Search class="h-10 w-10 text-slate-400" />
              </div>
              <h3 class="text-xl font-bold text-slate-600 mb-2">Aucune demande</h3>
              <p class="text-slate-500 text-base max-w-sm mx-auto leading-relaxed">
                {{ searchTerm ? `Aucun résultat pour "${searchTerm}"` : `Aucune demande ${getTabConfig(activeTab).label.toLowerCase()}` }}
              </p>
              <button
                v-if="searchTerm"
                type="button"
                class="mt-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-4 py-2 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 text-sm"
                @click="searchTerm = ''"
              >
                Effacer
              </button>
            </div>
          </div>
        </CardContent>
      </Card>

      <div class="fixed bottom-8 right-8 hidden lg:block">
        <div class="bg-white/90 backdrop-blur-xl shadow-2xl border-0 rounded-2xl overflow-hidden">
          <div class="p-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                <Sparkles class="h-6 w-6 text-white" />
              </div>
              <div>
                <div class="text-sm font-semibold text-slate-700">Total des demandes</div>
                <div class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                  {{ requests.length }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Clock, CheckCircle, XCircle, Trash2, Loader2, User, Search, Sparkles, Star, Users, Eye, Calendar } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Label, Input } from '@/components/ui/index'
import { api } from '@/lib/api'
import { useToastStore } from '@/stores/toast'

interface AffiliateRequest {
  id: string
  uid: string
  reason: string
  identityCardUrl: string
  identityCardPublicId: string
  status: 'pending' | 'approved' | 'rejected'
  createdAt: string
}

type Tab = 'pending' | 'approved' | 'rejected'

const toastStore = useToastStore()
const activeTab = ref<Tab>('pending')
const requests = ref<AffiliateRequest[]>([])
const loading = ref(true)
const searchTerm = ref('')
const expandedCard = ref<string | null>(null)
const floatingParticles = ref<{ id: number; x: number; y: number; size: number; opacity: number; color: string }[]>([])

const tabs: Tab[] = ['pending', 'approved', 'rejected']

const particleColors = [
  'bg-gradient-to-br from-indigo-400 to-purple-600',
  'bg-gradient-to-br from-pink-400 to-rose-600',
  'bg-gradient-to-br from-blue-400 to-cyan-600',
  'bg-gradient-to-br from-violet-400 to-purple-600',
]

onMounted(() => {
  for (let i = 0; i < 12; i++) {
    floatingParticles.value.push({
      id: i,
      x: Math.random() * 100,
      y: Math.random() * 100,
      size: Math.random() * 4 + 2,
      opacity: Math.random() * 0.6 + 0.2,
      color: particleColors[Math.floor(Math.random() * 4)],
    })
  }
})

const getTabConfig = (tab: Tab) => {
  switch (tab) {
    case 'pending':
      return { icon: Clock, label: 'En attente', gradient: 'from-amber-500 via-orange-500 to-yellow-500', bgGradient: 'from-amber-50 to-orange-50', borderColor: 'border-amber-200', textColor: 'text-amber-700', count: requests.value.filter((r) => r.status === 'pending').length, description: 'Demandes nécessitant une révision' }
    case 'approved':
      return { icon: CheckCircle, label: 'Approuvées', gradient: 'from-emerald-500 via-green-500 to-teal-500', bgGradient: 'from-emerald-50 to-green-50', borderColor: 'border-emerald-200', textColor: 'text-emerald-700', count: requests.value.filter((r) => r.status === 'approved').length, description: 'Demandes validées avec succès' }
    case 'rejected':
      return { icon: XCircle, label: 'Rejetées', gradient: 'from-rose-500 via-red-500 to-pink-500', bgGradient: 'from-rose-50 to-red-50', borderColor: 'border-rose-200', textColor: 'text-rose-700', count: requests.value.filter((r) => r.status === 'rejected').length, description: 'Demandes non conformes' }
  }
}

const fetchRequests = async () => {
  loading.value = true
  try {
    const response = await api.get<{ success: boolean; requests: AffiliateRequest[] }>(`/api/affiliate/requests/${activeTab.value}`)
    requests.value = response.data.requests
  } catch (err) {
    console.error(`Erreur lors de la récupération des demandes (${activeTab.value}):`, err)
    toastStore.error(`Échec de la récupération des demandes ${activeTab.value}.`)
  } finally {
    loading.value = false
  }
}

watch(activeTab, fetchRequests)

onMounted(fetchRequests)

const filteredRequests = computed(() =>
  requests.value.filter(
    (request) =>
      request.uid.toUpperCase().includes(searchTerm.value.toUpperCase()) ||
      request.reason.toUpperCase().includes(searchTerm.value.toUpperCase())
  )
)

const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' })

const handleApprove = async (requestId: string, uid: string, identityCardUrl: string) => {
  try {
    await api.post('/api/admin/approve-affiliate', { uid, identityCardUrl })
    requests.value = requests.value.filter((req) => req.id !== requestId)
    toastStore.success('Demande approuvée')
  } catch (err) {
    console.error('Erreur lors de l\'approbation:', err)
    toastStore.error('Échec de l\'approbation de la demande.')
  }
}

const handleReject = async (requestId: string) => {
  try {
    await api.put(`/api/affiliate/request/${requestId}/reject`, {})
    requests.value = requests.value.filter((req) => req.id !== requestId)
    toastStore.success('Demande rejetée')
  } catch (err) {
    console.error('Erreur lors du rejet:', err)
    toastStore.error('Échec du rejet de la demande.')
  }
}

const handleDelete = async (requestId: string) => {
  if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')) return
  try {
    await api.delete(`/api/affiliate/request/${requestId}`)
    requests.value = requests.value.filter((req) => req.id !== requestId)
    toastStore.success('Demande supprimée')
  } catch (err) {
    console.error('Erreur lors de la suppression:', err)
    toastStore.error('Échec de la suppression de la demande.')
  }
}

const openDocument = (url: string) => {
  window.open(url, '_blank')
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

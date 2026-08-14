<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(129,140,248,0.12),_transparent_25%),radial-gradient(circle_at_right,_rgba(236,72,153,0.10),_transparent_22%),linear-gradient(180deg,#f8fafc_0%,#eef2ff_100%)] text-slate-900">
    <div class="flex h-screen">
      <aside
        class="fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-200/80 bg-white/80 shadow-[0_18px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl transition-all duration-300 ease-out lg:static lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
      >
        <div class="flex h-full flex-col">
          <div class="flex items-center justify-between border-b border-slate-200/80 px-5 py-5">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-500 text-sm font-bold text-white shadow-lg shadow-indigo-500/30">
                G
              </div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500">Boutique</p>
                <h2 class="text-lg font-bold text-slate-900">GoldShop</h2>
              </div>
            </div>
            <button
              type="button"
              class="rounded-xl p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 lg:hidden"
              @click="sidebarOpen = false"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <nav class="flex-1 px-4 py-6">
            <div class="mb-5 flex items-center justify-between px-2">
              <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">Navigation</p>
            </div>
            <ul class="space-y-2">
              <li v-for="item in sidebarItems" :key="item.path">
                <button
                  type="button"
                  :class="cn(
                    'group flex w-full items-center gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200',
                    isActivePath(item.path)
                      ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25'
                      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                  )"
                  @click="handleNavigation(item.path)"
                >
                  <span
                    :class="isActivePath(item.path)
                      ? 'bg-white/15 text-white'
                      : 'bg-slate-100 text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600'"
                    class="flex h-8 w-8 items-center justify-center rounded-xl transition-colors"
                  >
                    <component :is="item.icon" class="h-4 w-4 shrink-0" />
                  </span>
                  <span class="flex-1 text-left">{{ item.label }}</span>
                  <Badge
                    v-if="item.badge"
                    variant="secondary"
                    :class="isActivePath(item.path)
                      ? 'border-white/20 bg-white/10 text-white'
                      : 'border-indigo-100 bg-indigo-50 text-indigo-700'"
                  >
                    {{ item.badge }}
                  </Badge>
                </button>
              </li>
            </ul>
          </nav>

          <div class="border-t border-slate-200/80 p-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
              <div class="flex items-center gap-3">
                <Avatar class="h-11 w-11 ring-2 ring-white">
                  <AvatarImage :src="authStore.user?.photoURL || ''" />
                  <AvatarFallback class="bg-gradient-to-br from-indigo-500 to-violet-500 text-sm font-bold text-white">
                    {{ initials }}
                  </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-semibold text-slate-800">
                    {{ authStore.user?.displayName || 'Utilisateur' }}
                  </p>
                  <p class="truncate text-xs text-slate-500">
                    {{ authStore.user?.email }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm lg:hidden"
        @click="sidebarOpen = false"
      />

      <div class="flex flex-1 flex-col overflow-hidden">
        <header class="border-b border-slate-200/80 bg-white/70 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
          <div class="flex h-20 items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm transition-colors hover:bg-slate-50 lg:hidden"
                @click="sidebarOpen = true"
              >
                <Menu class="h-5 w-5" />
              </button>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Administration</p>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                  {{ currentTitle }}
                </h1>
              </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
              <button type="button" class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 transition-colors hover:bg-slate-50">
                <Search class="h-4 w-4" />
              </button>
              <button type="button" class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 transition-colors hover:bg-slate-50">
                <Bell class="h-4 w-4" />
                <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 text-[10px] font-bold text-white shadow-sm">
                  3
                </span>
              </button>
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 transition-colors hover:bg-rose-50 hover:text-rose-600"
                @click="handleLogout"
              >
                <LogOut class="h-4 w-4" />
              </button>
            </div>
          </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
          <RouterView />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, markRaw, onMounted, ref, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { signOut } from 'firebase/auth'
import { Home, Package, ShoppingCart, Users, BarChart3, Handshake, Menu, X, Bell, Search, LogOut } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Badge, Avatar, AvatarImage, AvatarFallback } from '@/components/ui/index'
import { useAuthStore } from '@/stores/auth'
import { auth } from '@/lib/firebaseConfig'
import { useToastStore } from '@/stores/toast'

interface SidebarItem {
  id: string
  label: string
  icon: Component
  path: string
  badge?: string
}

const sidebarItems: SidebarItem[] = [
  { id: 'dashboard', label: 'Dashboard', icon: markRaw(Home), path: '/admin' },
  { id: 'products', label: 'Produits', icon: markRaw(Package), path: '/admin/products', badge: 'Nouveau' },
  { id: 'orders', label: 'Commandes', icon: markRaw(ShoppingCart), path: '/admin/orders', badge: '12' },
  { id: 'affiliate', label: 'Affiliation', icon: markRaw(Handshake), path: '/admin/affiliate', badge: '12' },
  { id: 'users', label: 'Utilisateurs', icon: markRaw(Users), path: '/admin/users' },
  { id: 'analytics', label: 'Analytiques', icon: markRaw(BarChart3), path: '/admin/analytics' },
]

const authStore = useAuthStore()
const toastStore = useToastStore()
const router = useRouter()
const route = useRoute()
const sidebarOpen = ref(false)

onMounted(() => {
  authStore.init()
})

const initials = computed(() => {
  const name = authStore.user?.displayName
  if (name) return name.charAt(0)
  const email = authStore.user?.email
  return email ? email.charAt(0).toUpperCase() : 'U'
})

const currentTitle = computed(() => {
  const active = sidebarItems.find((item) => isActivePath(item.path))
  return active?.label || 'Dashboard'
})

const isActivePath = (path: string) => {
  if (path === '/admin') {
    return route.path === '/admin'
  }
  return route.path.startsWith(path)
}

const handleNavigation = (path: string) => {
  router.push(path)
  sidebarOpen.value = false
}

const handleLogout = async () => {
  await signOut(auth)
  localStorage.removeItem('token')
  toastStore.success('Déconnexion réussie')
  router.push('/connexion')
}
</script>

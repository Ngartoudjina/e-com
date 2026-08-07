<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Utilisateurs</h2>
        <p class="text-gray-600">Gérez et surveillez tous les utilisateurs de votre plateforme</p>
      </div>
      <Button @click="isAddDialogOpen = true">
        <Plus class="mr-2 h-4 w-4" />
        Ajouter un utilisateur
      </Button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card v-for="stat in statsCards" :key="stat.label">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">{{ stat.label }}</p>
              <p class="text-2xl font-bold" :class="stat.color">{{ stat.value }}</p>
            </div>
            <component :is="stat.icon" class="h-8 w-8" :class="stat.iconColor" />
          </div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Filtres</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <Label for="search" class="sr-only">Rechercher</Label>
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
              <Input
                id="search"
                v-model="searchTerm"
                placeholder="Rechercher par nom ou email..."
                class="pl-10"
              />
            </div>
          </div>
          <Select v-model="selectedRole" class="w-full sm:w-48">
            <option value="all">Tous les rôles</option>
            <option value="admin">Administrateur</option>
            <option value="moderator">Modérateur</option>
            <option value="user">Utilisateur</option>
            <option value="guest">Invité</option>
          </Select>
          <Select v-model="selectedStatus" class="w-full sm:w-48">
            <option value="all">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
            <option value="suspended">Suspendu</option>
          </Select>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Liste des Utilisateurs ({{ filteredUsers.length }})</CardTitle>
        <CardDescription>
          Gérez les utilisateurs, leurs rôles et leurs statuts
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Utilisateur</TableHead>
              <TableHead>Rôle</TableHead>
              <TableHead>Statut</TableHead>
              <TableHead>Commandes</TableHead>
              <TableHead>Dépenses</TableHead>
              <TableHead>Dernière activité</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="user in filteredUsers" :key="user.id">
              <TableCell>
                <div class="flex items-center gap-3">
                  <Avatar>
                    <AvatarImage :src="user.avatar" />
                    <AvatarFallback>
                      {{ initials(user.name) }}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p class="font-medium">{{ user.name }}</p>
                    <p class="text-sm text-gray-500">{{ user.email }}</p>
                  </div>
                </div>
              </TableCell>
              <TableCell>
                <Badge variant="outline" :class="roleConfig[user.role].color">
                  <component :is="roleConfig[user.role].icon" class="mr-1 h-3 w-3" />
                  {{ roleConfig[user.role].label }}
                </Badge>
              </TableCell>
              <TableCell>
                <Badge variant="outline" :class="statusConfig[user.status].color">
                  {{ statusConfig[user.status].label }}
                </Badge>
              </TableCell>
              <TableCell>{{ user.ordersCount }}</TableCell>
              <TableCell>€{{ user.totalSpent.toFixed(2) }}</TableCell>
              <TableCell>
                <time class="text-sm text-gray-500">
                  {{ new Date(user.lastActive).toLocaleDateString('fr-FR') }}
                </time>
              </TableCell>
              <TableCell class="text-right">
                <DropdownMenu>
                  <template #trigger>
                    <Button variant="ghost" size="sm">
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </template>
                  <template #content>
                    <div class="p-1">
                      <p class="px-2 py-1.5 text-sm font-semibold text-muted-foreground">Actions</p>
                      <button
                        type="button"
                        class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                        @click="openUserDetails(user)"
                      >
                        <Eye class="mr-2 h-4 w-4" />
                        Voir détails
                      </button>
                      <button
                        type="button"
                        class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                      >
                        <Edit class="mr-2 h-4 w-4" />
                        Modifier
                      </button>
                      <button
                        type="button"
                        class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                      >
                        <Mail class="mr-2 h-4 w-4" />
                        Envoyer un email
                      </button>
                      <div class="my-1 h-px bg-border" />
                      <template v-if="user.status === 'active'">
                        <button
                          type="button"
                          class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                          @click="handleUserAction(user.id, 'suspend')"
                        >
                          <Ban class="mr-2 h-4 w-4" />
                          Suspendre
                        </button>
                      </template>
                      <template v-if="user.status === 'suspended'">
                        <button
                          type="button"
                          class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                          @click="handleUserAction(user.id, 'activate')"
                        >
                          <UserCheck class="mr-2 h-4 w-4" />
                          Réactiver
                        </button>
                      </template>
                      <div class="my-1 h-px bg-border" />
                      <button
                        type="button"
                        class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-red-600 hover:bg-accent"
                        @click="handleDeleteUser(user.id)"
                      >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Supprimer
                      </button>
                    </div>
                  </template>
                </DropdownMenu>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

    <Dialog v-model="isDetailsDialogOpen">
      <div class="space-y-4">
        <div>
          <h3 class="text-lg font-semibold">Détails de l'utilisateur</h3>
          <p class="text-sm text-muted-foreground">Informations complètes sur l'utilisateur sélectionné</p>
        </div>
        <div v-if="selectedUser" class="space-y-6">
          <div class="flex items-center gap-4">
            <Avatar class="h-16 w-16">
              <AvatarImage :src="selectedUser.avatar" />
              <AvatarFallback class="text-lg">
                {{ initials(selectedUser.name) }}
              </AvatarFallback>
            </Avatar>
            <div class="flex-1">
              <h3 class="text-xl font-semibold">{{ selectedUser.name }}</h3>
              <p class="text-gray-600">{{ selectedUser.email }}</p>
              <div class="flex items-center gap-2 mt-2">
                <Badge variant="outline" :class="roleConfig[selectedUser.role].color">
                  {{ roleConfig[selectedUser.role].label }}
                </Badge>
                <Badge variant="outline" :class="statusConfig[selectedUser.status].color">
                  {{ statusConfig[selectedUser.status].label }}
                </Badge>
              </div>
            </div>
          </div>

          <Separator />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h4 class="font-medium">Informations de contact</h4>
              <div v-if="selectedUser.phone" class="flex items-center gap-2 text-sm">
                <Phone class="h-4 w-4 text-gray-500" />
                {{ selectedUser.phone }}
              </div>
              <div v-if="selectedUser.location" class="flex items-center gap-2 text-sm">
                <MapPin class="h-4 w-4 text-gray-500" />
                {{ selectedUser.location }}
              </div>
              <div class="flex items-center gap-2 text-sm">
                <Calendar class="h-4 w-4 text-gray-500" />
                Inscrit le {{ new Date(selectedUser.joinDate).toLocaleDateString('fr-FR') }}
              </div>
            </div>

            <div class="space-y-4">
              <h4 class="font-medium">Statistiques</h4>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Commandes:</span>
                  <span class="font-medium">{{ selectedUser.ordersCount }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Total dépensé:</span>
                  <span class="font-medium">€{{ selectedUser.totalSpent.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Dernière activité:</span>
                  <span class="font-medium">{{ new Date(selectedUser.lastActive).toLocaleDateString('fr-FR') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <Dialog v-model="isAddDialogOpen">
      <div class="space-y-4">
        <div>
          <h3 class="text-lg font-semibold">Ajouter un nouvel utilisateur</h3>
          <p class="text-sm text-muted-foreground">Créez un nouveau compte utilisateur</p>
        </div>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label for="firstName">Prénom</Label>
              <Input id="firstName" placeholder="John" />
            </div>
            <div>
              <Label for="lastName">Nom</Label>
              <Input id="lastName" placeholder="Doe" />
            </div>
          </div>
          <div>
            <Label for="email">Email</Label>
            <Input id="email" type="email" placeholder="john.doe@example.com" />
          </div>
          <div>
            <Label for="role">Rôle</Label>
            <Select id="role">
              <option value="user">Utilisateur</option>
              <option value="moderator">Modérateur</option>
              <option value="admin">Administrateur</option>
            </Select>
          </div>
          <div class="flex items-center gap-2">
            <Switch id="active" v-model="activeAccount" />
            <Label for="active">Compte actif</Label>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="isAddDialogOpen = false">Annuler</Button>
            <Button @click="isAddDialogOpen = false">Créer l'utilisateur</Button>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, markRaw, ref, type Component } from 'vue'
import { Users, Plus, Search, MoreHorizontal, Edit, Trash2, Mail, Phone, MapPin, Calendar, Shield, ShieldCheck, Crown, UserCheck, Eye, Ban } from 'lucide-vue-next'
import {
  Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Badge,
  Avatar, AvatarImage, AvatarFallback, Input, Separator, DropdownMenu, Dialog, Select, Switch, Label,
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/index'

interface User {
  id: string
  name: string
  email: string
  role: 'admin' | 'moderator' | 'user' | 'guest'
  status: 'active' | 'inactive' | 'suspended'
  avatar?: string
  phone?: string
  location?: string
  joinDate: string
  lastActive: string
  ordersCount: number
  totalSpent: number
}

const mockUsers: User[] = [
  { id: '1', name: 'Jean Dupont', email: 'jean.dupont@example.com', role: 'admin', status: 'active', phone: '+33 1 23 45 67 89', location: 'Paris, France', joinDate: '2023-01-15', lastActive: '2024-01-15', ordersCount: 45, totalSpent: 2450.5 },
  { id: '2', name: 'Marie Martin', email: 'marie.martin@example.com', role: 'moderator', status: 'active', phone: '+33 1 98 76 54 32', location: 'Lyon, France', joinDate: '2023-03-20', lastActive: '2024-01-14', ordersCount: 32, totalSpent: 1680.25 },
  { id: '3', name: 'Pierre Durand', email: 'pierre.durand@example.com', role: 'user', status: 'active', phone: '+33 1 11 22 33 44', location: 'Marseille, France', joinDate: '2023-06-10', lastActive: '2024-01-13', ordersCount: 18, totalSpent: 945.75 },
  { id: '4', name: 'Sophie Moreau', email: 'sophie.moreau@example.com', role: 'user', status: 'inactive', phone: '+33 1 55 66 77 88', location: 'Toulouse, France', joinDate: '2023-09-05', lastActive: '2023-12-20', ordersCount: 8, totalSpent: 320 },
  { id: '5', name: 'Antoine Rousseau', email: 'antoine.rousseau@example.com', role: 'user', status: 'suspended', phone: '+33 1 99 88 77 66', location: 'Nice, France', joinDate: '2023-11-12', lastActive: '2023-12-15', ordersCount: 2, totalSpent: 89.99 },
  { id: '6', name: 'Julie Leroy', email: 'julie.leroy@example.com', role: 'guest', status: 'active', location: 'Bordeaux, France', joinDate: '2024-01-08', lastActive: '2024-01-14', ordersCount: 0, totalSpent: 0 },
]

const roleConfig: Record<User['role'], { label: string; icon: Component; color: string }> = {
  admin: { label: 'Administrateur', icon: markRaw(Crown), color: 'bg-purple-100 text-purple-800' },
  moderator: { label: 'Modérateur', icon: markRaw(ShieldCheck), color: 'bg-blue-100 text-blue-800' },
  user: { label: 'Utilisateur', icon: markRaw(UserCheck), color: 'bg-green-100 text-green-800' },
  guest: { label: 'Invité', icon: markRaw(Shield), color: 'bg-gray-100 text-gray-800' },
}

const statusConfig: Record<User['status'], { label: string; color: string }> = {
  active: { label: 'Actif', color: 'bg-green-100 text-green-800' },
  inactive: { label: 'Inactif', color: 'bg-yellow-100 text-yellow-800' },
  suspended: { label: 'Suspendu', color: 'bg-red-100 text-red-800' },
}

const users = ref<User[]>(mockUsers)
const searchTerm = ref('')
const selectedRole = ref<string>('all')
const selectedStatus = ref<string>('all')
const selectedUser = ref<User | null>(null)
const isAddDialogOpen = ref(false)
const isDetailsDialogOpen = ref(false)
const activeAccount = ref(true)

const filteredUsers = computed(() =>
  users.value.filter((user) => {
    const matchesSearch = user.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      user.email.toLowerCase().includes(searchTerm.value.toLowerCase())
    const matchesRole = selectedRole.value === 'all' || user.role === selectedRole.value
    const matchesStatus = selectedStatus.value === 'all' || user.status === selectedStatus.value
    return matchesSearch && matchesRole && matchesStatus
  })
)

const statsCards = computed(() => [
  { label: 'Total', value: users.value.length, color: 'text-gray-900', icon: markRaw(Users), iconColor: 'text-blue-500' },
  { label: 'Actifs', value: users.value.filter((u) => u.status === 'active').length, color: 'text-green-600', icon: markRaw(UserCheck), iconColor: 'text-green-500' },
  { label: 'Inactifs', value: users.value.filter((u) => u.status === 'inactive').length, color: 'text-yellow-600', icon: markRaw(Shield), iconColor: 'text-yellow-500' },
  { label: 'Suspendus', value: users.value.filter((u) => u.status === 'suspended').length, color: 'text-red-600', icon: markRaw(Ban), iconColor: 'text-red-500' },
])

const initials = (name: string) => name.split(' ').map((n) => n[0]).join('')

const handleUserAction = (userId: string, action: string) => {
  users.value = users.value.map((user) => {
    if (user.id === userId) {
      switch (action) {
        case 'activate':
          return { ...user, status: 'active' as const }
        case 'suspend':
          return { ...user, status: 'suspended' as const }
        case 'deactivate':
          return { ...user, status: 'inactive' as const }
        default:
          return user
      }
    }
    return user
  })
}

const handleDeleteUser = (userId: string) => {
  users.value = users.value.filter((user) => user.id !== userId)
}

const openUserDetails = (user: User) => {
  selectedUser.value = user
  isDetailsDialogOpen.value = true
}
</script>

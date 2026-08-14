<template>
  <div class="flex items-center justify-center h-screen bg-background p-4">
    <Card class="max-w-md w-full">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <ShieldAlert class="h-5 w-5 text-destructive" />
          Accès Refusé
        </CardTitle>
        <CardDescription>
          Vous n'avez pas les autorisations nécessaires pour accéder à cette page.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-2">
          <p class="text-sm text-muted-foreground">
            {{ loading ? 'Vérification des permissions...' : 'Connectez-vous avec un compte administrateur.' }}
          </p>
          <div class="flex gap-2">
            <Button :to="'/'">Retour à l'accueil</Button>
            <Button variant="outline" :to="'/connexion'">Se connecter</Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ShieldAlert } from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button } from '@/components/ui/index'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
authStore.init()
const loading = computed(() => authStore.loading)
</script>

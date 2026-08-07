<template>
  <div class="flex items-center justify-center h-screen bg-background p-4">
    <Card class="max-w-md w-full">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <FileQuestion class="h-5 w-5 text-muted-foreground" />
          Page Non Trouvée
        </CardTitle>
        <CardDescription>
          La page que vous recherchez n'existe pas.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Button @click="$router.back()">Retour</Button>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { FileQuestion } from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button } from '@/components/ui/index'
</script>

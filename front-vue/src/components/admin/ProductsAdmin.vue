<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-4">
      <label class="relative w-full sm:w-80">
        <span class="sr-only">Rechercher une pièce</span>
        <Search class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink-500" />
        <input v-model="recherche" class="field pl-11" placeholder="Nom ou rayon" />
      </label>

      <div class="flex items-center gap-3">
        <p data-numeric class="t-small text-ink-500">
          {{ produitsFiltres.length }} référence{{ produitsFiltres.length > 1 ? 's' : '' }}
        </p>
        <button type="button" class="btn btn-primary" @click="ouvrirCreation">Nouvelle pièce</button>
      </div>
    </div>

    <!-- ---------------------------------------------- Panneau d'édition -->
    <section v-if="edition" class="mt-6 border border-rule bg-surface">
      <div class="flex items-center justify-between border-b border-rule p-5">
        <div>
          <h2 class="t-h3">{{ edition.id ? 'Modifier la pièce' : 'Nouvelle pièce' }}</h2>
          <p v-if="edition.id" class="t-small mt-1 text-ink-500">{{ edition.name }}</p>
        </div>
        <button type="button" class="btn btn-icon" aria-label="Fermer" @click="fermer">
          <X class="size-4" />
        </button>
      </div>

      <form class="space-y-6 p-5" @submit.prevent="enregistrer">
        <div class="grid gap-5 md:grid-cols-2">
          <label class="block">
            <span class="t-label text-ink-500">Nom</span>
            <input v-model="edition.name" class="field mt-3" maxlength="255" required />
          </label>

          <label class="block">
            <span class="t-label text-ink-500">Rayon</span>
            <input
              v-model="edition.category"
              class="field mt-3"
              maxlength="120"
              list="rayons-existants"
              placeholder="Mailles, Vestes…"
            />
            <datalist id="rayons-existants">
              <option v-for="rayon in rayons" :key="rayon" :value="rayon" />
            </datalist>
          </label>

          <label class="block">
            <span class="t-label text-ink-500">Prix (€)</span>
            <input v-model.number="edition.price" type="number" min="0" step="0.01" data-numeric class="field mt-3" required />
          </label>

          <label class="block">
            <span class="t-label text-ink-500">Stock</span>
            <input v-model.number="edition.stock" type="number" min="0" step="1" data-numeric class="field mt-3" />
          </label>
        </div>

        <label class="block">
          <span class="t-label text-ink-500">Description</span>
          <textarea v-model="edition.description" class="field mt-3 min-h-28 py-3" maxlength="5000" />
        </label>

        <div>
          <span class="t-label text-ink-500">Visuel</span>
          <p class="t-small mt-1 text-ink-500">Image ou vidéo, 10 Mo au plus. Une pièce sans visuel ne peut pas être créée.</p>

          <div class="mt-3 flex flex-wrap items-start gap-5">
            <label
              class="flex h-32 w-full cursor-pointer items-center justify-center border border-dashed border-rule text-center transition-colors hover:bg-rule-soft/50 sm:w-72"
            >
              <input type="file" accept="image/*,video/*" class="sr-only" @change="choisirMedia" />
              <span class="t-small px-4 text-ink-500">
                {{ fichier ? fichier.name : 'Choisir un fichier' }}
              </span>
            </label>

            <div v-if="apercu" class="w-40 shrink-0 border border-rule">
              <video v-if="estVideo(apercu)" :src="apercu" controls class="aspect-[3/4] w-full object-cover" />
              <img v-else :src="apercu" alt="" class="aspect-[3/4] w-full object-cover" />
            </div>
          </div>

          <div v-if="envoiMedia" class="mt-3 h-1 w-full bg-rule-soft">
            <div class="h-1 bg-ink-900 transition-all" :style="{ width: progression + '%' }" />
          </div>
        </div>

        <p v-if="erreurForm" class="t-small text-error" role="alert">{{ erreurForm }}</p>

        <div class="flex flex-wrap items-center gap-3 border-t border-rule pt-5">
          <button type="submit" class="btn btn-primary" :disabled="envoi">
            {{ envoi ? 'Enregistrement…' : edition.id ? 'Mettre à jour' : 'Créer la pièce' }}
          </button>
          <button type="button" class="btn btn-secondary" @click="fermer">Annuler</button>

          <button
            v-if="edition.id"
            type="button"
            class="btn btn-danger sm:ml-auto"
            @click="supprimer"
          >
            Supprimer
          </button>
        </div>
      </form>
    </section>

    <!-- ---------------------------------------------- Liste -->
    <div v-if="chargement" class="mt-6 space-y-2">
      <div v-for="i in 6" :key="i" class="skeleton h-16" />
    </div>

    <div v-else-if="erreur" class="mt-6 border border-rule bg-surface p-10 text-center">
      <p class="t-body">{{ erreur }}</p>
      <button type="button" class="btn btn-secondary mt-6" @click="charger">Réessayer</button>
    </div>

    <div v-else-if="!produitsFiltres.length" class="mt-6 border border-rule bg-surface p-12 text-center">
      <p class="t-h3">Aucune pièce</p>
      <p class="t-body mt-2 text-ink-500">
        {{ recherche ? 'Aucun résultat pour cette recherche.' : 'Le catalogue est vide.' }}
      </p>
    </div>

    <div v-else class="mt-6 overflow-x-auto border border-rule bg-surface">
      <table class="w-full min-w-[820px] text-[13px]">
        <thead>
          <tr class="border-b border-rule text-left">
            <th class="t-label px-5 py-3 font-normal text-ink-500">Pièce</th>
            <th class="t-label px-5 py-3 font-normal text-ink-500">Rayon</th>
            <th class="t-label px-5 py-3 text-right font-normal text-ink-500">Prix</th>
            <th class="t-label px-5 py-3 text-right font-normal text-ink-500">Stock</th>
            <th class="t-label px-5 py-3 text-right font-normal text-ink-500">Vendues</th>
            <th class="px-5 py-3"><span class="sr-only">Action</span></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="produit in produitsFiltres"
            :key="produit.id"
            class="border-b border-rule last:border-0 transition-colors hover:bg-rule-soft/50"
          >
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <img
                  v-if="produit.mediaUrl && !estVideo(produit.mediaUrl)"
                  :src="produit.mediaUrl"
                  alt=""
                  loading="lazy"
                  class="size-10 shrink-0 border border-rule object-cover"
                />
                <span v-else class="size-10 shrink-0 border border-rule bg-rule-soft" aria-hidden="true" />
                <span class="block min-w-0 truncate text-ink-900">{{ produit.name }}</span>
              </div>
            </td>
            <td class="px-5 py-3 text-ink-700">{{ produit.category || '—' }}</td>
            <td data-numeric class="px-5 py-3 text-right text-ink-900">{{ formatPrix(produit.price) }}</td>
            <td data-numeric class="px-5 py-3 text-right">
              <span :class="(produit.stock ?? 0) === 0 ? 'text-error' : 'text-ink-700'">
                {{ produit.stock ?? 0 }}
              </span>
            </td>
            <td data-numeric class="px-5 py-3 text-right text-ink-700">{{ produit.soldCount ?? 0 }}</td>
            <td class="px-5 py-3 text-right">
              <button type="button" class="btn btn-sm btn-secondary" @click="ouvrirEdition(produit)">Modifier</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Search, X } from 'lucide-vue-next'
import { api, viderCacheApi } from '@/lib/api'
import { formatPrix } from '@/lib/format'
import { useToastStore } from '@/stores/toast'
import type { Product } from '@/types'

interface Edition {
  id: string | null
  name: string
  category: string
  price: number
  stock: number
  description: string
  mediaUrl: string
}

const toastStore = useToastStore()

const produits = ref<Product[]>([])
const chargement = ref(true)
const erreur = ref<string | null>(null)
const recherche = ref('')

const edition = ref<Edition | null>(null)
const fichier = ref<File | null>(null)
const apercu = ref<string | null>(null)
const envoi = ref(false)
const envoiMedia = ref(false)
const progression = ref(0)
const erreurForm = ref<string | null>(null)

const estVideo = (url: string) => /\.(mp4|mov|webm|mpeg)(\?|$)/i.test(url) || url.startsWith('blob:') && fichier.value?.type.startsWith('video/')

const rayons = computed(() =>
  [...new Set(produits.value.map((p) => p.category).filter(Boolean) as string[])].sort()
)

const produitsFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()
  if (!terme) return produits.value
  return produits.value.filter(
    (p) => p.name.toLowerCase().includes(terme) || (p.category ?? '').toLowerCase().includes(terme)
  )
})

const charger = async () => {
  chargement.value = true
  erreur.value = null
  try {
    const reponse = await api.get<{ products: Product[] }>('/api/admin/products')
    produits.value = reponse.data.products ?? []
  } catch (e) {
    console.error(e)
    erreur.value = 'Le catalogue n’a pas pu être chargé.'
  } finally {
    chargement.value = false
  }
}

const libererApercu = () => {
  if (apercu.value?.startsWith('blob:')) URL.revokeObjectURL(apercu.value)
}

const ouvrirCreation = () => {
  libererApercu()
  erreurForm.value = null
  fichier.value = null
  apercu.value = null
  edition.value = { id: null, name: '', category: '', price: 0, stock: 0, description: '', mediaUrl: '' }
}

const ouvrirEdition = (produit: Product) => {
  libererApercu()
  erreurForm.value = null
  fichier.value = null
  apercu.value = produit.mediaUrl || null
  edition.value = {
    id: produit.id,
    name: produit.name,
    category: produit.category ?? '',
    price: Number(produit.price),
    stock: Number(produit.stock ?? 0),
    description: produit.description ?? '',
    mediaUrl: produit.mediaUrl || '',
  }
}

const fermer = () => {
  libererApercu()
  edition.value = null
  fichier.value = null
  apercu.value = null
}

const choisirMedia = (evenement: Event) => {
  const champ = evenement.target as HTMLInputElement
  const choisi = champ.files?.[0]
  if (!choisi) return

  if (choisi.size > 10 * 1024 * 1024) {
    erreurForm.value = 'Le fichier dépasse 10 Mo.'
    champ.value = ''
    return
  }

  erreurForm.value = null
  libererApercu()
  fichier.value = choisi
  apercu.value = URL.createObjectURL(choisi)
}

/** Envoi du média séparé : l'API rend une URL que l'on joint ensuite à la pièce. */
const envoyerMedia = async (choisi: File): Promise<string> => {
  const corps = new FormData()
  corps.append('file', choisi)

  envoiMedia.value = true
  progression.value = 0
  try {
    const reponse = await api.post<{ secure_url: string }>('/api/upload', corps, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (evenement) => {
        progression.value = Math.round((evenement.loaded * 100) / (evenement.total || 1))
      },
    })
    if (!reponse.data.secure_url) throw new Error('URL du média non reçue.')
    return reponse.data.secure_url
  } finally {
    envoiMedia.value = false
  }
}

const enregistrer = async () => {
  if (!edition.value) return

  erreurForm.value = null
  envoi.value = true

  try {
    let url = edition.value.mediaUrl
    if (fichier.value) url = await envoyerMedia(fichier.value)

    if (!url) {
      erreurForm.value = 'Un visuel est nécessaire.'
      return
    }

    const charge = {
      name: edition.value.name,
      price: Number(edition.value.price),
      description: edition.value.description,
      category: edition.value.category || 'Autre',
      stock: Number(edition.value.stock) || 0,
      mediaUrl: url,
    }

    if (edition.value.id) {
      await api.put(`/api/admin/products/${edition.value.id}`, charge)
      toastStore.success('Pièce mise à jour.')
    } else {
      await api.post('/api/admin/products', charge)
      toastStore.success('Pièce créée.')
    }

    // Le catalogue public est mis en cache : sans purge, la boutique
    // continuerait d'afficher l'ancienne fiche.
    viderCacheApi()
    fermer()
    await charger()
  } catch (e: any) {
    erreurForm.value =
      e?.response?.data?.error ??
      Object.values(e?.response?.data?.errors ?? {}).flat()[0] ??
      e?.message ??
      'L’enregistrement a échoué.'
  } finally {
    envoi.value = false
  }
}

const supprimer = async () => {
  if (!edition.value?.id) return
  if (!window.confirm(`Supprimer « ${edition.value.name} » ? Cette action est définitive.`)) return

  try {
    await api.delete(`/api/admin/products/${edition.value.id}`)
    viderCacheApi()
    fermer()
    await charger()
    toastStore.success('Pièce supprimée.')
  } catch (e: any) {
    toastStore.error(e?.response?.data?.error ?? 'La suppression a échoué.')
  }
}

onMounted(charger)
</script>

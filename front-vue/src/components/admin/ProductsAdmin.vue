<template>
  <div class="relative overflow-hidden">
    <div class="space-y-6 relative z-10">
      <div class="flex flex-col md:flex-row justify-center gap-4">
        <button
          type="button"
          class="bg-gradient-to-r from-indigo-50 to-purple-50 border-indigo-200 text-indigo-700 hover:from-indigo-100 hover:to-purple-100 hover:border-indigo-300 shadow-lg hover:shadow-xl transition-all duration-300 w-full md:w-auto px-4 py-3 text-sm md:text-base rounded-md border font-medium flex items-center justify-center gap-2"
          @click="toggleAddMode"
        >
          <Plus class="h-4 w-4 md:h-5 md:w-5" :class="{ 'rotate-45': isFormVisible && !isManageMode }" />
          {{ isFormVisible && !isManageMode ? 'Fermer' : 'Ajouter un produit' }}
          <Sparkles class="h-3 w-3 md:h-4 md:w-4" />
        </button>
        <button
          type="button"
          class="bg-gradient-to-r from-blue-50 to-green-50 border-blue-200 text-blue-700 hover:from-blue-100 hover:to-green-100 hover:border-blue-300 shadow-lg hover:shadow-xl transition-all duration-300 w-full md:w-auto px-4 py-3 text-sm md:text-base rounded-md border font-medium flex items-center justify-center gap-2"
          @click="toggleManageMode"
        >
          <Edit class="h-4 w-4 md:h-5 md:w-5" />
          Gérer les produits
        </button>
      </div>

      <div class="space-y-6 relative z-10">
        <div class="bg-white/80 backdrop-blur-sm shadow-2xl border-slate-200/50 rounded-xl border overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-slate-200/50 p-4 md:p-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 md:gap-3">
                <div class="p-1 md:p-2 bg-indigo-100 rounded-full">
                  <ShoppingBag class="h-4 w-4 md:h-5 md:w-5 text-indigo-600" />
                </div>
                <div>
                  <h3 class="text-lg md:text-xl font-semibold text-slate-800">
                    {{ isManageMode ? 'Gérer un produit' : 'Ajouter un nouveau produit' }}
                  </h3>
                  <p class="text-sm md:text-base text-slate-600">
                    {{ isManageMode ? 'Recherchez et modifiez ou supprimez un produit' : 'Remplissez les informations ci-dessous pour créer votre produit' }}
                  </p>
                </div>
              </div>
              <button
                type="button"
                class="p-1 md:p-2 hover:bg-slate-100 rounded-full transition-colors"
                @click="closeForm"
              >
                <X class="h-3 w-3 md:h-4 md:w-4 text-slate-400" />
              </button>
            </div>
          </div>

          <div class="p-4 md:p-6">
            <div v-if="isManageMode" class="space-y-4">
              <div class="space-y-2">
                <label class="text-slate-700 font-medium text-sm md:text-base block">
                  <Search class="inline h-4 w-4 mr-1" />
                  Rechercher un produit
                </label>
                <Input
                  v-model="searchTerm"
                  placeholder="Entrez le nom du produit..."
                  class="border-slate-200 focus:border-blue-500 text-sm md:text-base"
                  @update:model-value="handleSearch"
                />
              </div>

              <div v-if="searchTerm" class="grid grid-cols-1 gap-3 md:gap-4">
                <div
                  v-for="product in filteredProducts"
                  :key="product.id"
                  class="cursor-pointer"
                  @click="selectProduct(product)"
                >
                  <div class="h-full hover:shadow-lg transition-shadow duration-200 rounded-lg border border-gray-100 bg-white overflow-hidden">
                    <div class="p-3 md:p-4">
                      <img
                        v-if="product.mediaUrl"
                        :src="product.mediaUrl"
                        :alt="product.name"
                        class="w-full h-24 md:h-32 object-cover rounded-t-lg"
                      />
                      <h4 class="text-sm md:text-lg font-medium mt-1 md:mt-2">{{ product.name }}</h4>
                      <p class="text-xs md:text-sm text-gray-600 mt-1">
                        {{ product.description ? product.description.substring(0, 50) + '...' : 'Aucune description' }}
                      </p>
                      <div class="flex items-center gap-1 md:gap-2 mt-1">
                        <DollarSign class="h-3 w-3 md:h-4 md:w-4 text-green-600" />
                        <span class="font-semibold text-xs md:text-base">{{ product.price }} fcfa</span>
                      </div>
                      <div class="flex items-center gap-1 mt-1">
                        <Star class="h-3 w-3 md:h-4 md:w-4 text-yellow-500" />
                        <span class="text-xs md:text-sm">{{ product.rating ?? 0 }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="name" class="text-slate-700 font-medium text-sm md:text-base">Nom du produit</Label>
                  <Input
                    id="name"
                    v-model="formData.name"
                    placeholder="Entrez le nom du produit"
                    :class="fieldErrors.name ? 'border-red-500' : 'border-slate-200'"
                  />
                  <p v-if="fieldErrors.name" class="text-red-500 text-xs">{{ fieldErrors.name }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="price" class="text-slate-700 font-medium text-sm md:text-base">Prix (FCFA)</Label>
                  <div class="relative">
                    <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <Input
                      id="price"
                      v-model="formData.price"
                      type="number"
                      placeholder="Entrez le prix"
                      class="pl-10"
                      :class="fieldErrors.price ? 'border-red-500' : 'border-slate-200'"
                    />
                  </div>
                  <p v-if="fieldErrors.price" class="text-red-500 text-xs">{{ fieldErrors.price }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="stars" class="text-slate-700 font-medium text-sm md:text-base">Évaluation (0-5)</Label>
                  <div class="relative">
                    <Star class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-yellow-500" />
                    <Input
                      id="stars"
                      v-model="formData.stars"
                      type="number"
                      step="0.1"
                      min="0"
                      max="5"
                      placeholder="Entrez l'évaluation"
                      class="pl-10"
                      :class="fieldErrors.stars ? 'border-red-500' : 'border-slate-200'"
                    />
                  </div>
                  <p v-if="fieldErrors.stars" class="text-red-500 text-xs">{{ fieldErrors.stars }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="purchases" class="text-slate-700 font-medium text-sm md:text-base">Stock</Label>
                  <div class="relative">
                    <Package class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <Input
                      id="purchases"
                      v-model="formData.purchases"
                      type="number"
                      placeholder="Entrez le stock"
                      class="pl-10"
                      :class="fieldErrors.purchases ? 'border-red-500' : 'border-slate-200'"
                    />
                  </div>
                  <p v-if="fieldErrors.purchases" class="text-red-500 text-xs">{{ fieldErrors.purchases }}</p>
                </div>
              </div>

              <div class="space-y-2">
                <Label for="category" class="text-slate-700 font-medium text-sm md:text-base">Catégorie</Label>
                <Input
                  id="category"
                  v-model="formData.category"
                  placeholder="Entrez la catégorie (ex: ÉLECTRONIQUE)"
                  @update:model-value="normalizeCategory"
                  :class="fieldErrors.category ? 'border-red-500' : 'border-slate-200'"
                />
                <p v-if="fieldErrors.category" class="text-red-500 text-xs">{{ fieldErrors.category }}</p>
              </div>

              <div class="space-y-2">
                <Label for="description" class="text-slate-700 font-medium text-sm md:text-base">Description</Label>
                <Textarea
                  id="description"
                  v-model="formData.description"
                  placeholder="Décrivez le produit..."
                  class="min-h-24"
                  :class="fieldErrors.description ? 'border-red-500' : 'border-slate-200'"
                />
                <p v-if="fieldErrors.description" class="text-red-500 text-xs">{{ fieldErrors.description }}</p>
              </div>

              <div class="space-y-2">
                <Label for="media" class="text-slate-700 font-medium text-sm md:text-base">Image ou Vidéo</Label>
                <label
                  for="media"
                  class="flex items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors"
                >
                  <div class="flex flex-col items-center gap-2">
                    <Camera class="h-6 w-6 text-slate-400" />
                    <span class="text-sm text-slate-600">
                      {{ mediaFile ? mediaFile.name : 'Sélectionnez une image ou une vidéo' }}
                    </span>
                  </div>
                </label>
                <input
                  id="media"
                  type="file"
                  accept="image/*,video/*"
                  class="hidden"
                  @change="handleMediaChange"
                />
                <div v-if="previewMedia" class="mt-4">
                  <img
                    v-if="!isVideoUrl(previewMedia)"
                    :src="previewMedia"
                    alt="Prévisualisation"
                    class="w-full max-h-64 object-contain rounded-lg"
                  />
                  <video v-else :src="previewMedia" controls class="w-full max-h-64 object-contain rounded-lg" />
                </div>
                <div v-if="isUploading" class="w-full bg-slate-200 rounded-full h-2.5">
                  <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-200" :style="{ width: uploadProgress + '%' }" />
                </div>
              </div>

              <div v-if="error" class="flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-lg">
                <AlertCircle class="h-5 w-5" />
                <span class="text-sm">{{ error }}</span>
              </div>

              <div class="flex flex-col md:flex-row gap-4">
                <template v-if="!isManageMode">
                  <button
                    type="submit"
                    :disabled="isUploading"
                    class="w-full md:w-auto bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 rounded-md px-4 py-2 text-sm font-medium inline-flex items-center justify-center gap-2"
                  >
                    <Loader2 v-if="isUploading" class="h-4 w-4 animate-spin" />
                    <template v-else>
                      <Plus class="h-4 w-4" />
                      Ajouter le produit
                    </template>
                  </button>
                </template>
                <template v-else>
                  <button
                    type="button"
                    :disabled="isUploading || !selectedProduct"
                    class="w-full md:w-auto bg-gradient-to-r from-blue-600 to-green-600 text-white hover:from-blue-700 hover:to-green-700 disabled:opacity-50 rounded-md px-4 py-2 text-sm font-medium inline-flex items-center justify-center gap-2"
                    @click="handleUpdate"
                  >
                    <Loader2 v-if="isUploading" class="h-4 w-4 animate-spin" />
                    <template v-else>
                      <Edit class="h-4 w-4" />
                      Mettre à jour
                    </template>
                  </button>
                  <button
                    type="button"
                    :disabled="!selectedProduct"
                    class="w-full md:w-auto bg-gradient-to-r from-red-600 to-pink-600 text-white hover:from-red-700 hover:to-pink-700 disabled:opacity-50 rounded-md px-4 py-2 text-sm font-medium inline-flex items-center justify-center gap-2"
                    @click="handleDelete"
                  >
                    <Trash2 class="h-4 w-4" />
                    Supprimer
                  </button>
                </template>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus, Star, DollarSign, Package, Camera, AlertCircle, Loader2, Sparkles, ShoppingBag, X, Edit, Trash2, Search } from 'lucide-vue-next'
import { api } from '@/lib/api'
import { Input, Label, Textarea } from '@/components/ui/index'
import { useToastStore } from '@/stores/toast'
import type { Product } from '@/types'

interface FormData {
  name: string
  price: string
  stars: string
  purchases: string
  description: string
  mediaUrl: string
  category: string
}

const emptyForm: FormData = { name: '', price: '', stars: '', purchases: '', description: '', mediaUrl: '', category: '' }

const toastStore = useToastStore()
const isFormVisible = ref(true)
const isManageMode = ref(false)
const formData = ref<FormData>({ ...emptyForm })
const mediaFile = ref<File | null>(null)
const previewMedia = ref<string | null>(null)
const uploadProgress = ref(0)
const isUploading = ref(false)
const error = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})
const searchTerm = ref('')
const selectedProduct = ref<Product | null>(null)
const products = ref<Product[]>([])

const filteredProducts = computed(() =>
  products.value.filter((product) => product.name.toUpperCase().includes(searchTerm.value.toUpperCase()))
)

const isVideoUrl = (url: string) => /\.(mp4|mov|mpeg)$/.test(url)

onMounted(async () => {
  try {
    const response = await api.get<{ success: boolean; products: Product[] }>('/api/admin/products')
    products.value = response.data.products
  } catch (err) {
    console.error('Erreur lors de la récupération des produits:', err)
    error.value = 'Échec de la récupération des produits.'
  }
})

const toggleAddMode = () => {
  isManageMode.value = false
  isFormVisible.value = !isFormVisible.value
  resetForm()
}

const toggleManageMode = () => {
  isManageMode.value = true
  isFormVisible.value = !isFormVisible.value
  resetForm()
}

const closeForm = () => {
  isFormVisible.value = false
  resetForm()
}

const resetForm = () => {
  formData.value = { ...emptyForm }
  mediaFile.value = null
  previewMedia.value = null
  selectedProduct.value = null
}

const normalizeCategory = () => {
  formData.value.category = formData.value.category.toUpperCase()
}

const handleMediaChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  const maxSize = 10 * 1024 * 1024

  if (file.size > maxSize) {
    error.value = 'Le fichier dépasse la limite de 10 Mo.'
    return
  }

  mediaFile.value = file
  previewMedia.value = URL.createObjectURL(file)

  if (file.type.startsWith('video/')) {
    const video = document.createElement('video')
    video.src = previewMedia.value
    video.onloadedmetadata = () => {
      if (video.duration > 60) {
        error.value = 'La durée de la vidéo dépasse 60 secondes.'
        clearMedia()
      }
      video.remove()
    }
    video.onerror = () => {
      error.value = 'Erreur lors de la lecture de la vidéo.'
      clearMedia()
      video.remove()
    }
  }
}

const clearMedia = () => {
  if (previewMedia.value) URL.revokeObjectURL(previewMedia.value)
  mediaFile.value = null
  previewMedia.value = null
}

const validateForm = (): boolean => {
  const errors: Record<string, string> = {}
  if (!formData.value.name.trim()) errors.name = 'Le nom du produit est requis'
  if (!formData.value.price || parseFloat(formData.value.price) <= 0) errors.price = 'Le prix doit être positif'
  if (!formData.value.stars || parseFloat(formData.value.stars) < 0 || parseFloat(formData.value.stars) > 5) {
    errors.stars = 'Les étoiles doivent être entre 0 et 5'
  }
  if (!formData.value.purchases || parseInt(formData.value.purchases) < 0) {
    errors.purchases = 'Le nombre d\'achats doit être positif'
  }
  if (!formData.value.description.trim()) errors.description = 'La description est requise'
  if (!formData.value.category.trim()) errors.category = 'La catégorie est requise'
  fieldErrors.value = errors
  return Object.keys(errors).length === 0
}

const uploadMedia = async (file: File): Promise<string> => {
  const form = new FormData()
  form.append('file', file)
  try {
    const response = await api.post<{ secure_url: string; public_id: string }>('/api/upload', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        const total = progressEvent.total || 1
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / total)
      },
    })
    if (!response.data.secure_url) {
      throw new Error('URL du média non reçue du serveur')
    }
    return response.data.secure_url
  } catch (err: any) {
    throw new Error(err.response?.data?.error || 'Échec du téléchargement du média')
  }
}

const buildProductData = (mediaUrl: string | undefined) => ({
  name: formData.value.name,
  price: parseFloat(formData.value.price),
  description: formData.value.description,
  rating: parseFloat(formData.value.stars) || 0,
  stock: parseInt(formData.value.purchases) || 0,
  category: formData.value.category.toUpperCase(),
  ...(mediaUrl !== undefined ? { mediaUrl } : {}),
})

const handleSubmit = async () => {
  if (!validateForm()) return

  let mediaUrl = formData.value.mediaUrl
  if (mediaFile.value) {
    error.value = null
    isUploading.value = true
    uploadProgress.value = 0
    try {
      mediaUrl = await uploadMedia(mediaFile.value)
      formData.value.mediaUrl = mediaUrl
    } catch (err: any) {
      error.value = err.message
      isUploading.value = false
      uploadProgress.value = 0
      return
    } finally {
      isUploading.value = false
    }
  }

  if (!mediaFile.value && !mediaUrl) {
    error.value = 'Un média (image ou vidéo) est requis pour ajouter un produit.'
    return
  }

  try {
    const response = await api.post<{ message: string; productId: string; product: Product }>(
      '/api/admin/products',
      buildProductData(mediaUrl)
    )
    if (!response.data.productId) {
      throw new Error('ID du produit non reçu du serveur')
    }
    const newProduct: Product = {
      ...response.data.product,
      rating: parseFloat(formData.value.stars) || 0,
      stock: parseInt(formData.value.purchases) || 0,
      category: formData.value.category.toUpperCase(),
    }
    products.value = [...products.value, newProduct]
    resetForm()
    toastStore.success('Produit ajouté avec succès!')
  } catch (err: any) {
    console.error('Error adding product:', err)
    error.value = err.response?.data?.error || 'Échec de l\'ajout du produit. Veuillez réessayer.'
  }
}

const handleSearch = () => {
  selectedProduct.value = null
  const matches = filteredProducts.value
  if (matches.length === 1) {
    fillForm(matches[0])
  }
}

const fillForm = (product: Product) => {
  formData.value = {
    name: product.name,
    price: product.price.toString(),
    stars: (product.rating ?? 0).toString(),
    purchases: (product.stock ?? 0).toString(),
    description: product.description ?? '',
    mediaUrl: product.mediaUrl || '',
    category: product.category ?? '',
  }
  previewMedia.value = product.mediaUrl || null
}

const selectProduct = (product: Product) => {
  selectedProduct.value = product
  fillForm(product)
  mediaFile.value = null
}

const handleUpdate = async () => {
  if (!selectedProduct.value) return
  if (!validateForm()) return

  let mediaUrl = formData.value.mediaUrl
  if (mediaFile.value) {
    isUploading.value = true
    uploadProgress.value = 0
    try {
      mediaUrl = await uploadMedia(mediaFile.value)
      formData.value.mediaUrl = mediaUrl
    } catch (err: any) {
      error.value = err.message
      isUploading.value = false
      uploadProgress.value = 0
      return
    } finally {
      isUploading.value = false
    }
  }

  const updatedData = {
    ...buildProductData(mediaUrl !== selectedProduct.value.mediaUrl ? mediaUrl : undefined),
  }

  try {
    const response = await api.put<{ message: string; product: Product }>(
      `/api/admin/products/${selectedProduct.value.id}`,
      updatedData
    )
    const updatedProduct: Product = {
      ...response.data.product,
      rating: parseFloat(formData.value.stars) || 0,
      stock: parseInt(formData.value.purchases) || 0,
      category: formData.value.category.toUpperCase(),
    }
    products.value = products.value.map((product) =>
      product.id === selectedProduct.value?.id ? updatedProduct : product
    )
    toastStore.success('Produit mis à jour avec succès!')
  } catch (err: any) {
    console.error('Error updating product:', err)
    error.value = err.response?.data?.error || 'Échec de la mise à jour du produit.'
  }
}

const handleDelete = async () => {
  if (!selectedProduct.value) return
  if (!window.confirm(`Êtes-vous sûr de vouloir supprimer "${selectedProduct.value.name}" ?`)) return
  try {
    await api.delete(`/api/admin/products/${selectedProduct.value.id}`)
    products.value = products.value.filter((product) => product.id !== selectedProduct.value?.id)
    resetForm()
    toastStore.success('Produit supprimé avec succès!')
  } catch (err: any) {
    console.error('Error deleting product:', err)
    error.value = err.response?.data?.error || 'Échec de la suppression du produit.'
  }
}
</script>

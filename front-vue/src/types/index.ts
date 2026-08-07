export interface Product {
  id: string
  name: string
  price: number
  mediaUrl: string
  category?: string
  description?: string
  rating?: number
  stock?: number
  selectedColor?: string
  selectedSize?: string
  originalPrice?: number
  isNew?: boolean
}

export interface ProductWithDetails extends Product {
  quantity: number
  selectedColor: string
  selectedSize: string
}

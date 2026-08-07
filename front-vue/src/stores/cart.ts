import { defineStore } from 'pinia'
import type { ProductWithDetails } from '@/types'

export const useCartStore = defineStore('cart', {
  state: () => ({
    cartItems: [] as ProductWithDetails[],
  }),
  getters: {
    totalItems: (state) => state.cartItems.reduce((sum, item) => sum + (item.quantity || 0), 0),
    totalCount: (state) => state.cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0),
  },
  actions: {
    addToCart(product: ProductWithDetails) {
      const existingItem = this.cartItems.find((item) => item.id === product.id)
      if (existingItem) {
        this.cartItems = this.cartItems.map((item) =>
          item.id === product.id
            ? { ...item, quantity: (item.quantity || 1) + (product.quantity || 1) }
            : item
        )
        return
      }
      this.cartItems = [
        ...this.cartItems,
        {
          ...product,
          originalPrice: product.originalPrice ?? product.price,
          isNew: product.isNew ?? false,
          selectedColor: product.selectedColor ?? 'default',
          selectedSize: product.selectedSize ?? 'M',
        },
      ]
    },
    removeFromCart(id: string) {
      this.cartItems = this.cartItems.filter((item) => item.id !== id)
    },
    updateQuantity(id: string, quantity: number) {
      this.cartItems = this.cartItems.map((item) =>
        item.id === id ? { ...item, quantity: Math.max(1, quantity) } : item
      )
    },
    clearCart() {
      this.cartItems = []
    },
  },
})

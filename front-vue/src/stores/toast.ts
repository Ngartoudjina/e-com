import { defineStore } from 'pinia'

export interface Toast {
  id: number
  type: 'success' | 'error' | 'info'
  message: string
}

let nextId = 1

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [] as Toast[],
  }),
  actions: {
    add(message: string, type: Toast['type'] = 'info') {
      const id = nextId++
      this.toasts.push({ id, type, message })
      setTimeout(() => this.remove(id), 4000)
    },
    success(message: string) {
      this.add(message, 'success')
    },
    error(message: string) {
      this.add(message, 'error')
    },
    info(message: string) {
      this.add(message, 'info')
    },
    remove(id: number) {
      this.toasts = this.toasts.filter((t) => t.id !== id)
    },
  },
})

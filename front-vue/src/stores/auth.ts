import { defineStore } from 'pinia'
import { auth, db } from '@/lib/firebaseConfig'
import { onAuthStateChanged, type User } from 'firebase/auth'
import { doc, getDoc } from 'firebase/firestore'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    isAdmin: false,
    loading: true,
    initialized: false,
  }),
  actions: {
    init() {
      if (this.initialized) return
      this.initialized = true
      onAuthStateChanged(auth, async (user) => {
        this.user = user
        if (user) {
          try {
            const userDocRef = doc(db, 'users', user.uid)
            const userDoc = await getDoc(userDocRef)
            if (userDoc.exists()) {
              const userData = userDoc.data()
              this.isAdmin = userData.isAdmin === true
            } else {
              this.isAdmin = false
            }
          } catch (error) {
            console.error('Error fetching user data:', error)
            this.isAdmin = false
          }
        } else {
          this.isAdmin = false
        }
        this.loading = false
      })
    },
  },
})

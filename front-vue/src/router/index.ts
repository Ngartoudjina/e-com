import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { watch } from 'vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/components/pages/HomeView.vue'),
    },
    {
      // Catalogue refait d'après la maquette « 02 Catalogue ».
      path: '/catalogue',
      name: 'catalogue',
      component: () => import('@/components/pages/CatalogView.vue'),
    },
    {
      // Fiche produit refaite d'après « 03 Fiche produit ».
      path: '/produit/:id',
      name: 'produit',
      component: () => import('@/components/pages/ProductView.vue'),
    },
    {
      path: '/signin',
      name: 'signin',
      component: () => import('@/components/pages/AuthView.vue'),
    },
    {
      path: '/verify-email',
      name: 'verify-email',
      component: () => import('@/components/pages/EmailVerificationView.vue'),
    },
    {
      path: '/panier',
      name: 'panier',
      component: () => import('@/components/pages/CartView.vue'),
    },
    {
      path: '/propos',
      name: 'propos',
      component: () => import('@/components/pages/AboutView.vue'),
    },
    {
      path: '/blog',
      name: 'blog',
      component: () => import('@/components/pages/BlogView.vue'),
    },
    {
      path: '/affiliation',
      name: 'affiliation',
      component: () => import('@/components/pages/AffiliateView.vue'),
    },
    {
      path: '/product',
      name: 'product',
      component: () => import('@/components/pages/ProductsView.vue'),
    },
    {
      path: '/faq',
      name: 'faq',
      component: () => import('@/components/pages/FaqView.vue'),
    },
    {
      path: '/access-denied',
      name: 'access-denied',
      component: () => import('@/components/pages/AccessDeniedView.vue'),
    },
    {
      path: '/admin',
      component: () => import('@/components/admin/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-home',
          component: () => import('@/components/admin/DashboardHome.vue'),
        },
        {
          path: 'products',
          name: 'admin-products',
          component: () => import('@/components/admin/ProductsAdmin.vue'),
        },
        {
          path: 'orders',
          name: 'admin-orders',
          component: () => import('@/components/admin/OrdersPage.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/components/admin/UsersPage.vue'),
        },
        {
          path: 'analytics',
          name: 'admin-analytics',
          component: () => import('@/components/admin/AnalyticsPage.vue'),
        },
        {
          path: 'affiliate',
          name: 'admin-affiliate',
          component: () => import('@/components/admin/AffiliateRequestManager.vue'),
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/components/pages/NotFoundView.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  authStore.init()

  if (to.meta.requiresAdmin) {
    if (authStore.loading) {
      await new Promise<void>((resolve) => {
        const stop = watch(
          () => authStore.loading,
          (value) => {
            if (!value) {
              stop()
              resolve()
            }
          }
        )
      })
    }
    if (!authStore.isAdmin) {
      return { name: 'access-denied' }
    }
  }
  return true
})

export default router

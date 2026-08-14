import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Rubriques d'aide et de marque annoncées par le pied de page.
 * Aucune maquette ne les décrit : elles partagent une page éditoriale
 * générique, dont le titre vient de `meta`.
 */
const pagesEditoriales: { chemin: string; titre: string; rubrique: string }[] = [
  { chemin: '/aide/livraison', titre: 'Livraison', rubrique: 'Aide' },
  { chemin: '/aide/retours', titre: 'Retours & échanges', rubrique: 'Aide' },
  { chemin: '/aide/tailles', titre: 'Guide des tailles', rubrique: 'Aide' },
  { chemin: '/aide/entretien', titre: 'Entretien', rubrique: 'Aide' },
  { chemin: '/aide/contact', titre: 'Nous écrire', rubrique: 'Aide' },
  { chemin: '/maison/ateliers', titre: 'Nos ateliers', rubrique: 'Maison' },
  { chemin: '/maison/matieres', titre: 'Nos matières', rubrique: 'Maison' },
  { chemin: '/maison/boutiques', titre: 'Nos boutiques', rubrique: 'Maison' },
  { chemin: '/maison/presse', titre: 'Presse', rubrique: 'Maison' },
  { chemin: '/favoris', titre: 'Mes favoris', rubrique: 'Mon compte' },
]

const routesEditoriales: RouteRecordRaw[] = pagesEditoriales.map(({ chemin, titre, rubrique }) => ({
  path: chemin,
  component: () => import('@/components/pages/InfoView.vue'),
  meta: { titre, rubrique },
}))

const router = createRouter({
  history: createWebHistory(),

  // Une navigation ramène en haut de page, sauf retour arrière.
  scrollBehavior: (_to, _from, position) => position ?? { top: 0 },

  routes: [
    {
      path: '/',
      name: 'accueil',
      component: () => import('@/components/pages/HomeView.vue'),
    },

    // ---- Boutique -------------------------------------------------
    {
      // Catalogue refait d'après « 02 Catalogue ».
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
      path: '/panier',
      name: 'panier',
      component: () => import('@/components/pages/CartView.vue'),
    },

    // ---- Compte ---------------------------------------------------
    {
      path: '/connexion',
      name: 'connexion',
      component: () => import('@/components/pages/AuthView.vue'),
    },
    {
      // L'espace client de « 08 Espace client » n'est pas encore construit :
      // le lien mène à l'authentification en attendant.
      path: '/compte',
      name: 'compte',
      component: () => import('@/components/pages/AuthView.vue'),
    },
    {
      path: '/verification-email',
      name: 'verification-email',
      component: () => import('@/components/pages/EmailVerificationView.vue'),
    },

    // ---- Contenus -------------------------------------------------
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
      path: '/blog/:id',
      name: 'blog-article',
      component: () => import('@/components/pages/BlogView.vue'),
    },
    {
      path: '/faq',
      name: 'faq',
      component: () => import('@/components/pages/FaqView.vue'),
    },
    {
      path: '/affiliation',
      name: 'affiliation',
      component: () => import('@/components/pages/AffiliateView.vue'),
    },
    ...routesEditoriales,

    // ---- Administration -------------------------------------------
    {
      path: '/admin',
      component: () => import('@/components/admin/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        { path: '', name: 'admin-accueil', component: () => import('@/components/admin/DashboardHome.vue') },
        { path: 'produits', name: 'admin-produits', component: () => import('@/components/admin/ProductsAdmin.vue') },
        { path: 'commandes', name: 'admin-commandes', component: () => import('@/components/admin/OrdersPage.vue') },
        { path: 'clients', name: 'admin-clients', component: () => import('@/components/admin/UsersPage.vue') },
        { path: 'statistiques', name: 'admin-statistiques', component: () => import('@/components/admin/AnalyticsPage.vue') },
        { path: 'affiliation', name: 'admin-affiliation', component: () => import('@/components/admin/AffiliateRequestManager.vue') },

        // Anciens chemins anglais de l'administration.
        { path: 'products', redirect: { name: 'admin-produits' } },
        { path: 'orders', redirect: { name: 'admin-commandes' } },
        { path: 'users', redirect: { name: 'admin-clients' } },
        { path: 'analytics', redirect: { name: 'admin-statistiques' } },
        { path: 'affiliate', redirect: { name: 'admin-affiliation' } },
      ],
    },

    {
      path: '/acces-refuse',
      name: 'acces-refuse',
      component: () => import('@/components/pages/AccessDeniedView.vue'),
    },

    /*
     * Redirections des anciens chemins.
     * Les liens déjà diffusés et les favoris des visiteurs doivent continuer
     * de fonctionner après le renommage.
     */
    { path: '/product', redirect: { name: 'catalogue' } },
    { path: '/products', redirect: { name: 'catalogue' } },
    { path: '/collections', redirect: { name: 'catalogue' } },
    { path: '/single/:id', redirect: (to) => ({ name: 'produit', params: { id: to.params.id } }) },
    { path: '/signin', redirect: { name: 'connexion' } },
    { path: '/login', redirect: { name: 'connexion' } },
    { path: '/verify-email', redirect: { name: 'verification-email' } },
    { path: '/access-denied', redirect: { name: 'acces-refuse' } },
    { path: '/maison/histoire', redirect: { name: 'propos' } },

    {
      path: '/:pathMatch(.*)*',
      name: 'introuvable',
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
      return { name: 'acces-refuse' }
    }
  }
  return true
})

/** Le titre de l'onglet suit la page consultée. */
router.afterEach((to) => {
  const titre = to.meta.titre as string | undefined
  document.title = titre ? `${titre} — GOLDSHOP` : 'GOLDSHOP — Boutique en ligne'
})

export default router

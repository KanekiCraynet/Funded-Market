import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Public routes
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomePage.vue'),
      meta: { title: 'Home' }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginPage.vue'),
      meta: { guest: true, title: 'Login' }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterPage.vue'),
      meta: { guest: true, title: 'Register' }
    },
    
    // Protected routes
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/DashboardPage.vue'),
      meta: { requiresAuth: true, title: 'Dashboard' }
    },
    {
      path: '/market',
      name: 'market',
      component: () => import('@/views/MarketPage.vue'),
      meta: { requiresAuth: true, title: 'Market Explorer' }
    },
    {
      path: '/instrument/:symbol',
      name: 'instrument-detail',
      component: () => import('@/views/InstrumentDetailPage.vue'),
      meta: { requiresAuth: true, title: 'Instrument Detail' },
      props: true
    },
    {
      path: '/analysis',
      name: 'analysis',
      component: () => import('@/views/AnalysisPage.vue'),
      meta: { requiresAuth: true, title: 'Generate Analysis' }
    },
    {
      path: '/analysis/:id',
      name: 'analysis-detail',
      component: () => import('@/views/AnalysisDetailPage.vue'),
      meta: { requiresAuth: true, title: 'Analysis Detail' },
      props: true
    },
    {
      path: '/indicators',
      name: 'indicators',
      component: () => import('@/views/IndicatorsPage.vue'),
      meta: { requiresAuth: true, title: 'Technical Indicators' }
    },
    {
      path: '/indicators/:symbol',
      name: 'indicators-detail',
      component: () => import('@/views/IndicatorsPage.vue'),
      meta: { requiresAuth: true, title: 'Indicator Analysis' },
      props: true
    },
    {
      path: '/compare',
      name: 'compare',
      component: () => import('@/views/ComparePage.vue'),
      meta: { requiresAuth: true, title: 'Compare Instruments' }
    },
    {
      path: '/history',
      name: 'history',
      component: () => import('@/views/HistoryPage.vue'),
      meta: { requiresAuth: true, title: 'Analysis History' }
    },
    {
      path: '/portfolio',
      name: 'portfolio',
      component: () => import('@/views/PortfolioPage.vue'),
      meta: { requiresAuth: true, title: 'Portfolio' }
    },
    {
      path: '/watchlist',
      name: 'watchlist',
      component: () => import('@/views/WatchlistPage.vue'),
      meta: { requiresAuth: true, title: 'Watchlist' }
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/SettingsPage.vue'),
      meta: { requiresAuth: true, title: 'Settings' }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/ProfilePage.vue'),
      meta: { requiresAuth: true, title: 'Profile' }
    },
    
    // 404 Not Found
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundPage.vue'),
      meta: { title: '404 Not Found' }
    }
  ]
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Set page title
  if (to.meta.title) {
    document.title = `${to.meta.title} | Market Analysis Platform`
  }
  
  // Check if user is authenticated (if not already checked)
  if (!authStore.checkPerformed) {
    try {
      await authStore.checkAuth()
    } catch (error) {
      console.error('Auth check failed:', error)
    }
  }
  
  // Handle protected routes
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ 
      name: 'login',
      query: { redirect: to.fullPath }
    })
  } 
  // Handle guest routes (redirect to dashboard if authenticated)
  else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  }
  // Allow navigation
  else {
    next()
  }
})

// After each navigation
router.afterEach((to, from) => {
  // Scroll to top
  window.scrollTo(0, 0)
})

export default router

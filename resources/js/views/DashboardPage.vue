<template>
  <div class="min-h-screen">
    <!-- Header -->
    <header class="border-b border-white/10 backdrop-blur-lg bg-white/5">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <h1 class="text-2xl font-bold">Market Analysis</h1>
          </div>
          
          <nav class="hidden md:flex items-center gap-6">
            <router-link to="/dashboard" class="text-white hover:text-purple-400 transition">Dashboard</router-link>
            <router-link to="/analysis" class="text-white hover:text-purple-400 transition">Analysis</router-link>
            <router-link to="/history" class="text-white hover:text-purple-400 transition">History</router-link>
          </nav>

          <div class="flex items-center gap-4">
            <div class="text-white text-sm">{{ authStore.user?.name }}</div>
            <button @click="handleLogout" class="text-white hover:text-purple-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>

    <div class="container mx-auto px-4 py-8">
      <!-- Welcome Section -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold mb-2">Welcome back, {{ authStore.user?.name }}! 👋</h2>
        <p class="text-gray-400">Here's what's happening in the markets today</p>
      </div>

      <!-- Quick Stats -->
      <QuickStatsGrid class="mb-8" />

      <!-- Quick Actions -->
      <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="card bg-gradient-to-br from-purple-500/20 to-pink-500/20 border-white/10">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-500/30 rounded-lg">
              <svg class="w-8 h-8 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-semibold mb-1">Generate AI Analysis</h3>
              <p class="text-gray-400 text-sm">Get AI-powered insights on any instrument</p>
            </div>
            <router-link to="/analysis" class="btn-primary">Start Analysis</router-link>
          </div>
        </div>

        <div class="card bg-gradient-to-br from-blue-500/20 to-cyan-500/20 border-white/10">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-500/30 rounded-lg">
              <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-semibold mb-1">View History</h3>
              <p class="text-gray-400 text-sm">Browse your past analysis results</p>
            </div>
            <router-link to="/history" class="btn-secondary">View History</router-link>
          </div>
        </div>
      </div>

      <!-- Market Overview -->
      <MarketOverviewWidget class="mb-8" />

      <!-- Content Grid -->
      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Recent Analyses -->
        <RecentAnalysesWidget />

        <!-- Watchlist -->
        <WatchlistWidget />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import QuickStatsGrid from '@/components/market/QuickStatsGrid.vue'
import MarketOverviewWidget from '@/components/market/MarketOverviewWidget.vue'
import RecentAnalysesWidget from '@/components/analysis/RecentAnalysesWidget.vue'
import WatchlistWidget from '@/components/market/WatchlistWidget.vue'

const router = useRouter()
const authStore = useAuthStore()

onMounted(async () => {
  await authStore.checkAuth()
})

async function handleLogout() {
  await authStore.logout()
  router.push('/')
}
</script>

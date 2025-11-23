<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Total Analyses -->
    <Card padding="md" variant="glass" hoverable>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-400 mb-1">Total Analyses</p>
          <p class="text-3xl font-bold text-white">{{ totalAnalyses }}</p>
          <p class="text-xs text-green-400 mt-1">All time</p>
        </div>
        <div class="w-12 h-12 rounded-full bg-purple-500/20 flex items-center justify-center">
          <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
      </div>
    </Card>

    <!-- Avg Confidence -->
    <Card padding="md" variant="glass" hoverable>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-400 mb-1">Avg Confidence</p>
          <p class="text-3xl font-bold text-white">{{ avgConfidence }}%</p>
          <p class="text-xs text-blue-400 mt-1">Accuracy score</p>
        </div>
        <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center">
          <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </Card>

    <!-- Instruments Tracked -->
    <Card padding="md" variant="glass" hoverable>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-400 mb-1">Instruments</p>
          <p class="text-3xl font-bold text-white">{{ totalInstruments }}</p>
          <p class="text-xs text-yellow-400 mt-1">Available</p>
        </div>
        <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center">
          <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
        </div>
      </div>
    </Card>

    <!-- Watchlist -->
    <Card padding="md" variant="glass" hoverable>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-400 mb-1">Watchlist</p>
          <p class="text-3xl font-bold text-white">{{ watchlistCount }}</p>
          <p class="text-xs text-pink-400 mt-1">Favorites</p>
        </div>
        <div class="w-12 h-12 rounded-full bg-pink-500/20 flex items-center justify-center">
          <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useHistoryStore } from '@/stores/history'
import { useMarketStore } from '@/stores/market'
import { useWatchlistStore } from '@/stores/watchlist'
import Card from '@/components/ui/Card.vue'

const historyStore = useHistoryStore()
const marketStore = useMarketStore()
const watchlistStore = useWatchlistStore()

const totalAnalyses = computed(() => {
  return historyStore.stats?.total_analyses || 0
})

const avgConfidence = computed(() => {
  const conf = historyStore.stats?.avg_confidence || historyStore.stats?.average_confidence || 0
  return Math.round(conf * 100)
})

const totalInstruments = computed(() => {
  return marketStore.pagination?.total || 0
})

const watchlistCount = computed(() => {
  return watchlistStore.watchlistCount || 0
})

onMounted(async () => {
  // Fetch stats if not already loaded
  if (!historyStore.stats) {
    await historyStore.fetchStats()
  }
  
  if (!marketStore.hasInstruments) {
    await marketStore.fetchInstruments({ per_page: 1 }) // Just to get count
  }
})
</script>

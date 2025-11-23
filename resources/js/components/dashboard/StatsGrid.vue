<template>
  <div class="stats-grid">
    <!-- Total Instruments -->
    <div class="stat-card">
      <div class="stat-icon-wrapper primary">
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Total Instruments</div>
        <div class="stat-value">{{ formatNumber(totalInstruments) }}</div>
        <div v-if="marketSummary?.total_market_cap" class="stat-meta">
          Market Cap: ${{ formatLargeNumber(marketSummary.total_market_cap) }}
        </div>
      </div>
    </div>

    <!-- Top Gainers Count -->
    <div class="stat-card">
      <div class="stat-icon-wrapper success">
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Top Gainers</div>
        <div class="stat-value stat-value-success">{{ formatNumber(gainersCount) }}</div>
        <div v-if="marketSummary?.gainers_count" class="stat-meta">
          {{ ((marketSummary.gainers_count / totalInstruments) * 100).toFixed(1) }}% of market
        </div>
      </div>
    </div>

    <!-- Top Losers Count -->
    <div class="stat-card">
      <div class="stat-icon-wrapper danger">
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Top Losers</div>
        <div class="stat-value stat-value-danger">{{ formatNumber(losersCount) }}</div>
        <div v-if="marketSummary?.losers_count" class="stat-meta">
          {{ ((marketSummary.losers_count / totalInstruments) * 100).toFixed(1) }}% of market
        </div>
      </div>
    </div>

    <!-- 24h Volume -->
    <div class="stat-card">
      <div class="stat-icon-wrapper warning">
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">24h Volume</div>
        <div class="stat-value">${{ formatLargeNumber(totalVolume) }}</div>
        <div class="stat-meta">
          Trending: {{ trendingCount }} assets
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useMarketStore } from '@/stores/market'

const marketStore = useMarketStore()

const props = defineProps({
  marketSummary: {
    type: Object,
    default: null,
  },
})

// Computed
const totalInstruments = computed(() => {
  return props.marketSummary?.total_instruments || marketStore.instruments?.length || 0
})

const gainersCount = computed(() => {
  return props.marketSummary?.gainers_count || 0
})

const losersCount = computed(() => {
  return props.marketSummary?.losers_count || 0
})

const trendingCount = computed(() => {
  return marketStore.marketOverview?.trending?.length || 0
})

const totalVolume = computed(() => {
  return props.marketSummary?.total_volume_24h || 0
})

// Methods
const formatNumber = (num) => {
  if (!num) return '0'
  return Number(num).toLocaleString('en-US')
}

const formatLargeNumber = (num) => {
  if (!num) return '0'
  
  const abs = Math.abs(num)
  if (abs >= 1e12) return (num / 1e12).toFixed(2) + 'T'
  if (abs >= 1e9) return (num / 1e9).toFixed(2) + 'B'
  if (abs >= 1e6) return (num / 1e6).toFixed(2) + 'M'
  if (abs >= 1e3) return (num / 1e3).toFixed(2) + 'K'
  return num.toFixed(2)
}
</script>

<style scoped>
.stats-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6;
}

.stat-card {
  @apply bg-gray-900/50 backdrop-blur-sm rounded-xl border border-gray-800 p-6 hover:border-gray-700 transition-all duration-300;
  @apply hover:shadow-xl hover:shadow-blue-500/10 hover:translate-y-[-2px];
}

.stat-icon-wrapper {
  @apply w-12 h-12 rounded-xl flex items-center justify-center mb-4;
}

.stat-icon-wrapper.primary {
  @apply bg-blue-500/10 text-blue-400;
}

.stat-icon-wrapper.success {
  @apply bg-green-500/10 text-green-400;
}

.stat-icon-wrapper.danger {
  @apply bg-red-500/10 text-red-400;
}

.stat-icon-wrapper.warning {
  @apply bg-yellow-500/10 text-yellow-400;
}

.stat-icon {
  @apply w-6 h-6;
}

.stat-content {
  @apply space-y-1;
}

.stat-label {
  @apply text-gray-400 text-sm font-medium;
}

.stat-value {
  @apply text-2xl font-bold text-white;
}

.stat-value-success {
  @apply text-green-400;
}

.stat-value-danger {
  @apply text-red-400;
}

.stat-meta {
  @apply text-xs text-gray-500;
}

/* Mobile */
@media (max-width: 640px) {
  .stats-grid {
    @apply grid-cols-1;
  }
  
  .stat-card {
    @apply p-4;
  }
  
  .stat-value {
    @apply text-xl;
  }
}
</style>

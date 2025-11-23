<template>
  <div class="enhanced-market-card">
    <!-- Header with live indicator -->
    <div class="card-header">
      <h3 class="card-title">Market Overview</h3>
      
      <div class="flex items-center gap-3">
        <!-- Live Indicator -->
        <div v-if="isLive" class="flex items-center gap-2">
          <span class="live-dot"></span>
          <span class="text-xs text-gray-400">Live</span>
        </div>
        
        <!-- Last Update -->
        <span v-if="lastUpdate" class="text-xs text-gray-500">
          Updated {{ timeAgo }}
        </span>
        
        <!-- Refresh Button -->
        <button
          @click="handleRefresh"
          :disabled="isRefreshing"
          class="refresh-btn"
          title="Refresh data"
        >
          <svg
            class="w-4 h-4 transition-transform"
            :class="{ 'animate-spin': isRefreshing }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !marketData" class="flex justify-center py-12">
      <div class="spinner-large"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-red-400 text-center">{{ error }}</p>
      <button @click="handleRefresh" class="retry-btn">
        Try Again
      </button>
    </div>

    <!-- Market Data -->
    <div v-else class="market-grid">
      <!-- Top Gainers -->
      <div class="market-section gainers">
        <div class="section-header">
          <h4 class="section-title">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            Top Gainers
          </h4>
        </div>
        
        <div v-if="topGainers.length > 0" class="instruments-list">
          <div
            v-for="inst in topGainers.slice(0, 5)"
            :key="inst.symbol"
            class="instrument-item"
            @click="$emit('select-symbol', inst.symbol)"
          >
            <div class="instrument-info">
              <div class="symbol">{{ inst.symbol }}</div>
              <div class="price">${{ formatPrice(inst.price) }}</div>
            </div>
            <div class="instrument-change gain">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
              {{ formatPercent(inst.change_percent_24h) }}%
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No gainers today</div>
      </div>

      <!-- Top Losers -->
      <div class="market-section losers">
        <div class="section-header">
          <h4 class="section-title">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
            </svg>
            Top Losers
          </h4>
        </div>
        
        <div v-if="topLosers.length > 0" class="instruments-list">
          <div
            v-for="inst in topLosers.slice(0, 5)"
            :key="inst.symbol"
            class="instrument-item"
            @click="$emit('select-symbol', inst.symbol)"
          >
            <div class="instrument-info">
              <div class="symbol">{{ inst.symbol }}</div>
              <div class="price">${{ formatPrice(inst.price) }}</div>
            </div>
            <div class="instrument-change loss">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              {{ formatPercent(Math.abs(inst.change_percent_24h)) }}%
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No losers today</div>
      </div>

      <!-- Trending -->
      <div class="market-section trending">
        <div class="section-header">
          <h4 class="section-title">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Trending
          </h4>
        </div>
        
        <div v-if="trending.length > 0" class="instruments-list">
          <div
            v-for="inst in trending.slice(0, 5)"
            :key="inst.symbol"
            class="instrument-item"
            @click="$emit('select-symbol', inst.symbol)"
          >
            <div class="instrument-info">
              <div class="symbol">{{ inst.symbol }}</div>
              <div class="price">${{ formatPrice(inst.price) }}</div>
            </div>
            <div class="instrument-volume">
              Vol: ${{ formatVolume(inst.volume_24h) }}
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No trending</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRealtime } from '@/composables/useRealtime'
import { useMarketStore } from '@/stores/market'

const props = defineProps({
  autoRefresh: {
    type: Boolean,
    default: true,
  },
  refreshInterval: {
    type: Number,
    default: 30000, // 30 seconds
  },
})

const emit = defineEmits(['select-symbol', 'update'])

const marketStore = useMarketStore()
const loading = ref(false)
const error = ref(null)

// Real-time updates
const {
  isUpdating: isRefreshing,
  lastUpdate,
  refresh,
} = useRealtime({
  interval: props.refreshInterval,
  enabled: props.autoRefresh,
  onUpdate: (data) => {
    emit('update', data)
  },
  onError: (err) => {
    error.value = err.message || 'Failed to fetch market data'
  },
})

// Computed
const marketData = computed(() => marketStore.marketOverview)
const topGainers = computed(() => marketData.value?.top_gainers || [])
const topLosers = computed(() => marketData.value?.top_losers || [])
const trending = computed(() => marketData.value?.trending || [])
const isLive = computed(() => props.autoRefresh && !isRefreshing.value)

const timeAgo = computed(() => {
  if (!lastUpdate.value) return ''
  
  const seconds = Math.floor((new Date() - lastUpdate.value) / 1000)
  
  if (seconds < 60) return `${seconds}s ago`
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
  return `${Math.floor(seconds / 3600)}h ago`
})

// Methods
const handleRefresh = async () => {
  error.value = null
  await refresh()
}

const formatPrice = (price) => {
  if (!price) return '0.00'
  return Number(price).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const formatPercent = (percent) => {
  if (!percent) return '0.00'
  return Number(percent).toFixed(2)
}

const formatVolume = (volume) => {
  if (!volume) return '0'
  if (volume >= 1e9) return `${(volume / 1e9).toFixed(2)}B`
  if (volume >= 1e6) return `${(volume / 1e6).toFixed(2)}M`
  if (volume >= 1e3) return `${(volume / 1e3).toFixed(2)}K`
  return volume.toFixed(0)
}

// Initialize
onMounted(async () => {
  loading.value = true
  try {
    await marketStore.fetchMarketOverview()
  } catch (err) {
    error.value = err.message || 'Failed to load market data'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.enhanced-market-card {
  @apply bg-gray-900/50 backdrop-blur-sm rounded-xl border border-gray-800 overflow-hidden;
}

.card-header {
  @apply flex items-center justify-between p-6 border-b border-gray-800;
}

.card-title {
  @apply text-xl font-semibold text-white;
}

.live-dot {
  @apply w-2 h-2 bg-green-500 rounded-full animate-pulse;
}

.refresh-btn {
  @apply p-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed;
}

.spinner-large {
  @apply w-12 h-12 border-4 border-gray-700 border-t-blue-500 rounded-full animate-spin;
}

.error-state {
  @apply py-12 px-6;
}

.retry-btn {
  @apply mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors mx-auto block;
}

.market-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6 p-6;
}

.market-section {
  @apply space-y-4;
}

.section-header {
  @apply flex items-center justify-between;
}

.section-title {
  @apply flex items-center gap-2 text-sm font-semibold uppercase tracking-wide;
}

.gainers .section-title {
  @apply text-green-400;
}

.losers .section-title {
  @apply text-red-400;
}

.trending .section-title {
  @apply text-purple-400;
}

.instruments-list {
  @apply space-y-2;
}

.instrument-item {
  @apply flex items-center justify-between p-3 rounded-lg bg-gray-800/50 hover:bg-gray-800 transition-all cursor-pointer group;
}

.instrument-info {
  @apply flex-1;
}

.symbol {
  @apply font-semibold text-white text-sm group-hover:text-blue-400 transition-colors;
}

.price {
  @apply text-xs text-gray-400 mt-0.5;
}

.instrument-change {
  @apply flex items-center gap-1 font-medium text-sm;
}

.instrument-change.gain {
  @apply text-green-400;
}

.instrument-change.loss {
  @apply text-red-400;
}

.instrument-volume {
  @apply text-xs text-gray-500;
}

.empty-state {
  @apply text-gray-500 text-sm text-center py-8;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .market-grid {
    @apply grid-cols-1;
  }
  
  .card-header {
    @apply flex-col items-start gap-3;
  }
}
</style>

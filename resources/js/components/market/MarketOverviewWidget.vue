<template>
  <Card title="Market Overview" variant="glass">
    <div v-if="loading" class="flex justify-center py-8">
      <div class="spinner"></div>
    </div>
    
    <div v-else-if="error" class="text-red-400 text-center py-4">
      {{ error }}
    </div>
    
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Top Gainers -->
      <div class="space-y-2">
        <h4 class="text-green-400 font-semibold text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          Top Gainers
        </h4>
        <div v-if="topGainers.length > 0" class="space-y-1.5">
          <div 
            v-for="inst in topGainers.slice(0, 5)" 
            :key="inst.symbol"
            class="flex items-center justify-between p-2 rounded-lg bg-white/5 hover:bg-white/10 transition-colors cursor-pointer"
            @click="goToInstrument(inst.symbol)"
          >
            <div>
              <div class="font-semibold text-white text-sm">{{ inst.symbol }}</div>
              <div class="text-xs text-gray-400">{{ formatPrice(inst.price) }}</div>
            </div>
            <div class="text-right">
              <Badge variant="success" size="sm">
                {{ formatPercent(inst.change_percent_24h, 2, true) }}
              </Badge>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-sm text-center py-4">
          No data
        </div>
      </div>
      
      <!-- Top Losers -->
      <div class="space-y-2">
        <h4 class="text-red-400 font-semibold text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
          </svg>
          Top Losers
        </h4>
        <div v-if="topLosers.length > 0" class="space-y-1.5">
          <div 
            v-for="inst in topLosers.slice(0, 5)" 
            :key="inst.symbol"
            class="flex items-center justify-between p-2 rounded-lg bg-white/5 hover:bg-white/10 transition-colors cursor-pointer"
            @click="goToInstrument(inst.symbol)"
          >
            <div>
              <div class="font-semibold text-white text-sm">{{ inst.symbol }}</div>
              <div class="text-xs text-gray-400">{{ formatPrice(inst.price) }}</div>
            </div>
            <div class="text-right">
              <Badge variant="danger" size="sm">
                {{ formatPercent(inst.change_percent_24h, 2, true) }}
              </Badge>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-sm text-center py-4">
          No data
        </div>
      </div>
      
      <!-- Trending -->
      <div class="space-y-2">
        <h4 class="text-purple-400 font-semibold text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          Trending
        </h4>
        <div v-if="trending.length > 0" class="space-y-1.5">
          <div 
            v-for="inst in trending.slice(0, 5)" 
            :key="inst.symbol"
            class="flex items-center justify-between p-2 rounded-lg bg-white/5 hover:bg-white/10 transition-colors cursor-pointer"
            @click="goToInstrument(inst.symbol)"
          >
            <div>
              <div class="font-semibold text-white text-sm">{{ inst.symbol }}</div>
              <div class="text-xs text-gray-400">{{ formatPrice(inst.price) }}</div>
            </div>
            <div class="text-right">
              <Badge 
                :variant="inst.change_percent_24h > 0 ? 'success' : inst.change_percent_24h < 0 ? 'danger' : 'default'" 
                size="sm"
              >
                {{ formatPercent(inst.change_percent_24h, 2, true) }}
              </Badge>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-sm text-center py-4">
          No data
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMarketStore } from '@/stores/market'
import { useFormat } from '@/composables/useFormat'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'

const router = useRouter()
const marketStore = useMarketStore()
const { formatPrice, formatPercent } = useFormat()

const loading = computed(() => marketStore.isLoading)
const error = computed(() => marketStore.error)
const topGainers = computed(() => marketStore.topGainers || [])
const topLosers = computed(() => marketStore.topLosers || [])
const trending = computed(() => marketStore.trendingInstruments || [])

function goToInstrument(symbol) {
  router.push({ name: 'instrument-detail', params: { symbol } })
}

onMounted(async () => {
  if (!marketStore.overview) {
    await marketStore.fetchOverview()
  }
})
</script>

<style scoped>
.spinner {
  @apply w-8 h-8 border-4 border-purple-500 border-t-transparent rounded-full animate-spin;
}
</style>

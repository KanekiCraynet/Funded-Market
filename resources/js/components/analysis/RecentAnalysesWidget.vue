<template>
  <Card title="Recent Analyses" variant="glass">
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h3 class="text-xl font-semibold text-white">Recent Analyses</h3>
        <router-link 
          :to="{ name: 'history' }"
          class="text-sm text-purple-400 hover:text-purple-300 transition-colors"
        >
          View All →
        </router-link>
      </div>
    </template>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="spinner"></div>
    </div>
    
    <div v-else-if="error" class="text-red-400 text-center py-4">
      {{ error }}
    </div>
    
    <div v-else-if="recentAnalyses.length === 0" class="text-center py-8">
      <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-400">No analyses yet</p>
      <router-link 
        :to="{ name: 'analysis' }"
        class="inline-block mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white text-sm transition-colors"
      >
        Generate Your First Analysis
      </router-link>
    </div>
    
    <div v-else class="space-y-3">
      <div 
        v-for="analysis in recentAnalyses" 
        :key="analysis.id"
        class="p-4 rounded-lg bg-white/5 hover:bg-white/10 transition-all cursor-pointer border border-white/5 hover:border-white/20"
        @click="viewAnalysis(analysis.id)"
      >
        <div class="flex items-start justify-between mb-2">
          <div>
            <div class="flex items-center gap-2">
              <h4 class="font-semibold text-white">{{ analysis.symbol }}</h4>
              <Badge :variant="getRecommendationVariant(analysis.recommendation)">
                {{ analysis.recommendation }}
              </Badge>
            </div>
            <p class="text-sm text-gray-400 mt-1">{{ analysis.instrument_name }}</p>
          </div>
          <div class="text-right">
            <div class="text-sm font-semibold text-white">{{ formatConfidence(analysis.confidence) }}</div>
            <div class="text-xs text-gray-400">Confidence</div>
          </div>
        </div>
        
        <div class="flex items-center justify-between text-sm">
          <div class="flex items-center gap-3">
            <Badge :variant="getRiskVariant(analysis.risk_level)" size="sm">
              {{ analysis.risk_level }} RISK
            </Badge>
            <span class="text-gray-400">{{ formatRelativeTime(analysis.created_at) }}</span>
          </div>
          <div class="text-purple-400 text-xs">
            {{ analysis.time_horizon?.replace('_', ' ').toUpperCase() }}
          </div>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useHistoryStore } from '@/stores/history'
import { useFormat } from '@/composables/useFormat'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'

const router = useRouter()
const historyStore = useHistoryStore()
const { formatConfidence, formatRelativeTime } = useFormat()

const loading = computed(() => historyStore.loading)
const error = computed(() => historyStore.error)
const recentAnalyses = computed(() => historyStore.analyses.slice(0, 5))

function getRecommendationVariant(rec) {
  const map = {
    'BUY': 'buy',
    'SELL': 'sell',
    'HOLD': 'hold'
  }
  return map[rec] || 'default'
}

function getRiskVariant(risk) {
  const map = {
    'LOW': 'risk-low',
    'MEDIUM': 'risk-medium',
    'HIGH': 'risk-high'
  }
  return map[risk] || 'default'
}

function viewAnalysis(id) {
  router.push({ name: 'analysis-detail', params: { id } })
}

onMounted(async () => {
  if (!historyStore.analyses || historyStore.analyses.length === 0) {
    await historyStore.fetchHistory({ per_page: 5 })
  }
})
</script>

<style scoped>
.spinner {
  @apply w-8 h-8 border-4 border-purple-500 border-t-transparent rounded-full animate-spin;
}
</style>

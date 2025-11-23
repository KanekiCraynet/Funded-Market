<template>
  <div class="analysis-timeline">
    <div class="timeline-header">
      <h3 class="timeline-title">Recent Analysis</h3>
      <router-link to="/history" class="view-all-link">
        View All →
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="timeline-loading">
      <div v-for="i in 3" :key="i" class="timeline-item-skeleton"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!analyses || analyses.length === 0" class="timeline-empty">
      <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="empty-text">No analysis yet</p>
      <router-link to="/analysis/generate" class="empty-action">
        Generate First Analysis
      </router-link>
    </div>

    <!-- Timeline Items -->
    <div v-else class="timeline-list">
      <div
        v-for="analysis in recentAnalyses"
        :key="analysis.id"
        class="timeline-item"
        @click="viewAnalysis(analysis.id)"
      >
        <!-- Left: Time & Indicator -->
        <div class="timeline-left">
          <div class="timeline-time">{{ formatTime(analysis.created_at) }}</div>
          <div class="timeline-line"></div>
          <div class="timeline-dot" :class="getDotClass(analysis.recommendation)"></div>
        </div>

        <!-- Right: Content -->
        <div class="timeline-content">
          <div class="timeline-card">
            <!-- Header -->
            <div class="card-header">
              <div class="flex items-center gap-3">
                <div class="symbol-badge">{{ analysis.symbol }}</div>
                <div 
                  class="recommendation-badge"
                  :class="getRecommendationClass(analysis.recommendation)"
                >
                  {{ analysis.recommendation }}
                </div>
              </div>
              <div class="score-badge">
                Score: {{ formatScore(analysis.final_score) }}
              </div>
            </div>

            <!-- Details -->
            <div class="card-details">
              <div class="detail-item">
                <span class="detail-label">Confidence:</span>
                <div class="confidence-bar">
                  <div 
                    class="confidence-fill"
                    :style="{ width: `${analysis.confidence * 100}%` }"
                    :class="getConfidenceClass(analysis.confidence)"
                  ></div>
                </div>
                <span class="detail-value">{{ formatPercent(analysis.confidence) }}%</span>
              </div>

              <div class="detail-item">
                <span class="detail-label">Risk:</span>
                <span 
                  class="risk-badge"
                  :class="getRiskClass(analysis.risk_level)"
                >
                  {{ analysis.risk_level }}
                </span>
              </div>

              <div v-if="analysis.time_horizon" class="detail-item">
                <span class="detail-label">Horizon:</span>
                <span class="detail-value">{{ analysis.time_horizon }}</span>
              </div>
            </div>

            <!-- View Button -->
            <button class="view-button">
              <span>View Details</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Load More -->
    <div v-if="hasMore" class="timeline-footer">
      <button @click="loadMore" :disabled="loadingMore" class="load-more-btn">
        <span v-if="loadingMore">Loading...</span>
        <span v-else>Load More</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAnalysisStore } from '@/stores/analysis'

const props = defineProps({
  limit: {
    type: Number,
    default: 5,
  },
})

const router = useRouter()
const analysisStore = useAnalysisStore()

const loading = ref(false)
const loadingMore = ref(false)

const analyses = computed(() => analysisStore.recentAnalyses)
const recentAnalyses = computed(() => analyses.value?.slice(0, props.limit) || [])
const hasMore = computed(() => analyses.value?.length > props.limit)

// Methods
const viewAnalysis = (id) => {
  router.push(`/analysis/${id}`)
}

const loadMore = async () => {
  loadingMore.value = true
  try {
    await analysisStore.fetchHistory({ page: 2 })
  } finally {
    loadingMore.value = false
  }
}

const formatTime = (date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = Math.floor((now - d) / 1000) // seconds

  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
  
  return d.toLocaleDateString()
}

const formatScore = (score) => {
  return (score * 100).toFixed(0)
}

const formatPercent = (value) => {
  return (value * 100).toFixed(1)
}

const getDotClass = (recommendation) => {
  if (recommendation === 'BUY') return 'dot-success'
  if (recommendation === 'SELL') return 'dot-danger'
  return 'dot-neutral'
}

const getRecommendationClass = (rec) => {
  if (rec === 'BUY') return 'rec-buy'
  if (rec === 'SELL') return 'rec-sell'
  return 'rec-hold'
}

const getConfidenceClass = (conf) => {
  if (conf >= 0.7) return 'conf-high'
  if (conf >= 0.4) return 'conf-medium'
  return 'conf-low'
}

const getRiskClass = (risk) => {
  if (risk === 'LOW') return 'risk-low'
  if (risk === 'HIGH') return 'risk-high'
  return 'risk-medium'
}

// Initialize
onMounted(async () => {
  loading.value = true
  try {
    await analysisStore.fetchHistory({ limit: 10 })
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.analysis-timeline {
  @apply bg-gray-900/50 backdrop-blur-sm rounded-xl border border-gray-800 p-6;
}

.timeline-header {
  @apply flex items-center justify-between mb-6;
}

.timeline-title {
  @apply text-xl font-semibold text-white;
}

.view-all-link {
  @apply text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors;
}

.timeline-loading {
  @apply space-y-4;
}

.timeline-item-skeleton {
  @apply h-32 bg-gray-800/50 rounded-lg animate-pulse;
}

.timeline-empty {
  @apply text-center py-12;
}

.empty-icon {
  @apply w-16 h-16 text-gray-600 mx-auto mb-4;
}

.empty-text {
  @apply text-gray-400 mb-4;
}

.empty-action {
  @apply inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors;
}

.timeline-list {
  @apply space-y-6;
}

.timeline-item {
  @apply flex gap-4 cursor-pointer;
}

.timeline-left {
  @apply flex flex-col items-center gap-2 pt-1;
}

.timeline-time {
  @apply text-xs text-gray-500 whitespace-nowrap;
}

.timeline-line {
  @apply w-0.5 flex-1 bg-gray-800 min-h-[40px];
}

.timeline-dot {
  @apply w-3 h-3 rounded-full;
}

.dot-success {
  @apply bg-green-500 shadow-lg shadow-green-500/50;
}

.dot-danger {
  @apply bg-red-500 shadow-lg shadow-red-500/50;
}

.dot-neutral {
  @apply bg-gray-500;
}

.timeline-content {
  @apply flex-1;
}

.timeline-card {
  @apply bg-gray-800/50 rounded-lg p-4 hover:bg-gray-800 transition-all;
  @apply hover:shadow-lg hover:shadow-blue-500/10;
}

.card-header {
  @apply flex items-center justify-between mb-3;
}

.symbol-badge {
  @apply px-3 py-1 bg-blue-500/10 text-blue-400 rounded-lg font-semibold text-sm;
}

.recommendation-badge {
  @apply px-2 py-0.5 rounded text-xs font-semibold;
}

.rec-buy {
  @apply bg-green-500/20 text-green-400;
}

.rec-sell {
  @apply bg-red-500/20 text-red-400;
}

.rec-hold {
  @apply bg-gray-500/20 text-gray-400;
}

.score-badge {
  @apply text-sm text-gray-400;
}

.card-details {
  @apply space-y-2 mb-3;
}

.detail-item {
  @apply flex items-center gap-2 text-sm;
}

.detail-label {
  @apply text-gray-500 min-w-[80px];
}

.detail-value {
  @apply text-gray-300;
}

.confidence-bar {
  @apply flex-1 h-2 bg-gray-700 rounded-full overflow-hidden;
}

.confidence-fill {
  @apply h-full transition-all duration-300;
}

.conf-high {
  @apply bg-green-500;
}

.conf-medium {
  @apply bg-yellow-500;
}

.conf-low {
  @apply bg-red-500;
}

.risk-badge {
  @apply px-2 py-0.5 rounded text-xs font-medium;
}

.risk-low {
  @apply bg-green-500/20 text-green-400;
}

.risk-medium {
  @apply bg-yellow-500/20 text-yellow-400;
}

.risk-high {
  @apply bg-red-500/20 text-red-400;
}

.view-button {
  @apply flex items-center gap-2 text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors;
}

.timeline-footer {
  @apply mt-6 text-center;
}

.load-more-btn {
  @apply px-6 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Mobile */
@media (max-width: 640px) {
  .timeline-item {
    @apply flex-col gap-2;
  }
  
  .timeline-left {
    @apply flex-row items-center;
  }
  
  .timeline-line {
    @apply hidden;
  }
  
  .card-header {
    @apply flex-col items-start gap-2;
  }
}
</style>

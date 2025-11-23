<template>
  <div class="min-h-screen">
    <!-- Header -->
    <header class="border-b border-white/10 backdrop-blur-lg bg-white/5">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center gap-4">
          <button @click="router.push('/dashboard')" class="text-white hover:text-purple-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h1 class="text-2xl font-bold">Analysis History</h1>
          </div>
        </div>
      </div>
    </header>

    <div class="container mx-auto px-4 py-8">
      <div v-if="loading" class="card p-8">
        <div class="flex flex-col items-center space-y-4">
          <div class="animate-spin w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full"></div>
          <p class="text-gray-400">Loading your analysis history...</p>
        </div>
      </div>

      <div v-else-if="analyses.length === 0" class="card p-8 text-center">
        <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
        <h3 class="text-xl font-semibold mb-2">No Analysis History</h3>
        <p class="text-gray-400 mb-6">You haven't generated any analyses yet. Start by analyzing an instrument.</p>
        <router-link to="/analysis" class="btn-primary inline-block">Generate First Analysis</router-link>
      </div>

      <div v-else class="space-y-4">
        <div v-for="analysis in analyses" :key="analysis.id" 
          class="card hover:bg-white/10 transition-all cursor-pointer"
          @click="viewDetails(analysis.id)">
          <div class="grid md:grid-cols-5 gap-6 items-center">
            <div class="md:col-span-2">
              <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-purple-500/30 rounded-lg">
                  <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">{{ analysis.symbol }}</h3>
                  <p class="text-sm text-gray-400">{{ analysis.instrument_name || '-' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2 text-sm text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ formatDate(analysis.created_at) }}
              </div>
            </div>

            <div class="text-center">
              <div :class="[
                'inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold',
                analysis.recommendation === 'BUY' ? 'bg-green-500/20 text-green-400' :
                analysis.recommendation === 'SELL' ? 'bg-red-500/20 text-red-400' :
                'bg-yellow-500/20 text-yellow-400'
              ]">
                {{ analysis.recommendation }}
              </div>
              <div class="mt-2">
                <span :class="[
                  'text-xs px-2 py-1 rounded',
                  analysis.risk_level === 'LOW' ? 'bg-green-500/20 text-green-400' :
                  analysis.risk_level === 'MEDIUM' ? 'bg-yellow-500/20 text-yellow-400' :
                  'bg-red-500/20 text-red-400'
                ]">{{ analysis.risk_level }} RISK</span>
              </div>
            </div>

            <div class="text-center">
              <div class="text-2xl font-bold mb-1">{{ (analysis.confidence * 100).toFixed(0) }}%</div>
              <p class="text-sm text-gray-400 mb-2">Confidence</p>
              <div class="w-full bg-white/10 rounded-full h-2">
                <div class="bg-purple-500 h-2 rounded-full transition-all" :style="{ width: `${analysis.confidence * 100}%` }"></div>
              </div>
            </div>

            <div class="flex justify-end">
              <button @click.stop="viewDetails(analysis.id)" class="btn-secondary">
                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View Details
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="card mt-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-400">
              Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="fetchHistory(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                :class="[
                  'px-4 py-2 rounded-lg transition-colors',
                  pagination.current_page === 1
                    ? 'bg-white/5 text-gray-500 cursor-not-allowed'
                    : 'bg-white/10 text-white hover:bg-white/20'
                ]"
              >
                Previous
              </button>
              
              <div class="flex items-center gap-1">
                <template v-for="page in getPageNumbers()" :key="page">
                  <button
                    v-if="page !== '...'"
                    @click="fetchHistory(page)"
                    :class="[
                      'w-10 h-10 rounded-lg transition-colors',
                      page === pagination.current_page
                        ? 'bg-purple-500 text-white'
                        : 'bg-white/10 text-white hover:bg-white/20'
                    ]"
                  >
                    {{ page }}
                  </button>
                  <span v-else class="px-2 text-gray-500">...</span>
                </template>
              </div>
              
              <button
                @click="fetchHistory(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                :class="[
                  'px-4 py-2 rounded-lg transition-colors',
                  pagination.current_page === pagination.last_page
                    ? 'bg-white/5 text-gray-500 cursor-not-allowed'
                    : 'bg-white/10 text-white hover:bg-white/20'
                ]"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { analysisAPI } from '@/api/client'

const router = useRouter()
const analyses = ref([])
const loading = ref(true)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

onMounted(async () => {
  await fetchHistory()
})

async function fetchHistory(page = 1) {
  loading.value = true
  try {
    const response = await analysisAPI.getHistory({
      per_page: 15,
      page: page
    })
    
    // Backend response: { data: [...], pagination: {...} }
    // Interceptor sudah extract, jadi response.data adalah array analyses
    analyses.value = Array.isArray(response.data) ? response.data : []
    
    // Meta/pagination info
    if (response.meta) {
      pagination.value = response.meta
    }
  } catch (error) {
    console.error('Failed to fetch history:', error)
    analyses.value = []
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    })
  } catch (e) {
    return 'Invalid date'
  }
}

function viewDetails(id) {
  // For now, just navigate to analysis page
  // TODO: In future, create a detail view page
  router.push('/analysis')
}

function getPageNumbers() {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  
  // Handle edge cases
  if (last <= 1) return [1]
  
  if (last <= 7) {
    // Show all pages if 7 or less
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)
    
    // Show ellipsis if current is far from start
    if (current > 3) {
      pages.push('...')
    }
    
    // Show pages around current (avoid duplicates)
    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      if (!pages.includes(i)) {
        pages.push(i)
      }
    }
    
    // Show ellipsis if current is far from end
    if (current < last - 2) {
      pages.push('...')
    }
    
    // Always show last page (avoid duplicate)
    if (!pages.includes(last)) {
      pages.push(last)
    }
  }
  
  return pages
}
</script>

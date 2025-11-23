<template>
  <div class="min-h-screen">
    <!-- Header -->
    <header class="border-b border-white/10 backdrop-blur-lg bg-white/5">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center gap-4">
          <button @click="router.back()" class="text-white hover:text-purple-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            <h1 class="text-2xl font-bold">AI Analysis</h1>
          </div>
        </div>
      </div>
    </header>

    <div class="container mx-auto px-4 py-8">
      <!-- Analysis Form -->
      <div class="card mb-8">
        <h2 class="text-xl font-bold mb-6">Generate Market Analysis</h2>
        
        <form @submit.prevent="handleAnalyze" class="space-y-6">
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium mb-2">Symbol</label>
              <input v-model="form.symbol" placeholder="e.g., BTC, AAPL, EUR/USD" required class="input" list="instruments" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Time Horizon</label>
              <select v-model="form.timeHorizon" class="input">
                <option value="short_term">Short Term (1-4 weeks)</option>
                <option value="medium_term">Medium Term (1-3 months)</option>
                <option value="long_term">Long Term (3-12 months)</option>
              </select>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary w-full">
            <span v-if="loading" class="flex items-center justify-center gap-2">
              <div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div>
              Generating Analysis...
            </span>
            <span v-else class="flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
              Generate AI Analysis
            </span>
          </button>
        </form>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="card p-8">
        <div class="flex flex-col items-center space-y-4">
          <div class="animate-spin w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full"></div>
          <div class="text-center">
            <h3 class="text-xl font-semibold mb-2">Analyzing Market Data...</h3>
            <p class="text-gray-400">Computing 40+ technical indicators, analyzing sentiment, and generating AI insights</p>
          </div>
        </div>
      </div>

      <!-- Results -->
      <div v-if="result && !loading" class="space-y-6">
        <!-- Recommendation Card -->
        <div :class="[
          'card border-2',
          result.recommendation === 'BUY' ? 'border-green-500 bg-green-500/10' :
          result.recommendation === 'SELL' ? 'border-red-500 bg-red-500/10' :
          'border-yellow-500 bg-yellow-500/10'
        ]">
          <div class="grid md:grid-cols-3 gap-8 p-8">
            <div class="text-center">
              <svg v-if="result.recommendation === 'BUY'" class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              <svg v-else-if="result.recommendation === 'SELL'" class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
              </svg>
              <svg v-else class="w-16 h-16 text-yellow-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              <h3 class="text-3xl font-bold mb-2">{{ result.recommendation }}</h3>
              <p class="text-gray-400">Recommendation</p>
            </div>

            <div class="text-center">
              <div class="text-5xl font-bold mb-2">{{ (result.confidence * 100).toFixed(1) }}%</div>
              <p class="text-gray-400 mb-4">Confidence Level</p>
              <div class="w-full bg-white/10 rounded-full h-3">
                <div class="bg-purple-500 h-3 rounded-full transition-all" :style="{ width: `${result.confidence * 100}%` }"></div>
              </div>
            </div>

            <div class="text-center">
              <div :class="[
                'inline-flex items-center gap-2 px-4 py-2 rounded-full mb-4',
                result.risk_level === 'LOW' ? 'bg-green-500/20 text-green-400' :
                result.risk_level === 'MEDIUM' ? 'bg-yellow-500/20 text-yellow-400' :
                'bg-red-500/20 text-red-400'
              ]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="font-semibold">{{ result.risk_level }} RISK</span>
              </div>
              <p class="text-gray-400">Risk Assessment</p>
            </div>
          </div>
        </div>

        <!-- Price Targets -->
        <div class="card">
          <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Price Targets
          </h3>
          <div class="grid md:grid-cols-4 gap-4">
            <div class="p-4 rounded-lg bg-white/5 border border-white/10">
              <p class="text-sm text-gray-400 mb-2">Near Term</p>
              <p class="text-2xl font-bold">${{ result.price_targets.near_term.toFixed(2) }}</p>
            </div>
            <div class="p-4 rounded-lg bg-white/5 border border-white/10">
              <p class="text-sm text-gray-400 mb-2">Medium Term</p>
              <p class="text-2xl font-bold">${{ result.price_targets.medium_term.toFixed(2) }}</p>
            </div>
            <div class="p-4 rounded-lg bg-white/5 border border-white/10">
              <p class="text-sm text-gray-400 mb-2">Long Term</p>
              <p class="text-2xl font-bold">${{ result.price_targets.long_term.toFixed(2) }}</p>
            </div>
            <div class="p-4 rounded-lg bg-red-500/20 border border-red-500/30">
              <p class="text-sm text-red-400 mb-2">Stop Loss</p>
              <p class="text-2xl font-bold text-red-400">${{ result.price_targets.stop_loss.toFixed(2) }}</p>
            </div>
          </div>
        </div>

        <!-- Summaries -->
        <div class="grid md:grid-cols-3 gap-6">
          <div class="card">
            <h4 class="text-lg font-bold mb-4">Technical Analysis</h4>
            <p class="text-gray-300 text-sm leading-relaxed">{{ result.technical_summary }}</p>
          </div>
          <div class="card">
            <h4 class="text-lg font-bold mb-4">Fundamental Analysis</h4>
            <p class="text-gray-300 text-sm leading-relaxed">{{ result.fundamental_summary }}</p>
          </div>
          <div class="card">
            <h4 class="text-lg font-bold mb-4">Sentiment Analysis</h4>
            <p class="text-gray-300 text-sm leading-relaxed">{{ result.sentiment_summary }}</p>
          </div>
        </div>

        <!-- Position Sizing -->
        <div class="card" v-if="result.position_size_recommendation">
          <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Position Size Recommendation
          </h3>
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-gray-400 mb-2">Recommended Size</p>
              <p class="text-3xl font-bold text-purple-400">{{ result.position_size_recommendation.size_percent ?? 10 }}%</p>
              <p class="text-sm text-gray-400 mt-2">Risk Level: {{ result.position_size_recommendation.risk_level ?? 'MODERATE' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-400 mb-2">Rationale</p>
              <p class="text-gray-300 text-sm">{{ result.position_size_recommendation.rationale ?? 'Based on current market conditions and risk assessment' }}</p>
            </div>
          </div>
        </div>

        <!-- Top Drivers -->
        <div class="card" v-if="result.top_drivers && result.top_drivers.length > 0">
          <h3 class="text-xl font-bold mb-4">Top Drivers</h3>
          <div class="space-y-3">
            <div v-for="(driver, index) in result.top_drivers" :key="index" class="flex items-start gap-4 p-3 rounded-lg bg-white/5">
              <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 font-bold">
                {{ index + 1 }}
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-white mb-1">{{ driver.factor }}</h4>
                <p class="text-sm text-gray-400 mb-2">{{ driver.impact }}</p>
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-white/10 rounded-full h-2">
                    <div class="bg-purple-500 h-2 rounded-full transition-all" :style="{ width: `${driver.weight * 100}%` }"></div>
                  </div>
                  <span class="text-xs text-gray-500">{{ (driver.weight * 100).toFixed(1) }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Key Levels -->
        <div class="card" v-if="result.key_levels">
          <h3 class="text-xl font-bold mb-4">Key Technical Levels</h3>
          <div class="grid md:grid-cols-2 gap-6">
            <div v-if="result.key_levels.resistance && result.key_levels.resistance.length > 0">
              <h4 class="text-sm font-semibold text-red-400 mb-3">Resistance Levels</h4>
              <div class="space-y-2">
                <div v-for="(level, index) in result.key_levels.resistance" :key="index" class="flex items-center justify-between p-2 rounded bg-red-500/10">
                  <span class="text-sm text-gray-400">R{{ index + 1 }}</span>
                  <span class="font-semibold text-red-400">${{ Number(level).toFixed(4) }}</span>
                </div>
              </div>
            </div>
            <div v-if="result.key_levels.support && result.key_levels.support.length > 0">
              <h4 class="text-sm font-semibold text-green-400 mb-3">Support Levels</h4>
              <div class="space-y-2">
                <div v-for="(level, index) in result.key_levels.support" :key="index" class="flex items-center justify-between p-2 rounded bg-green-500/10">
                  <span class="text-sm text-gray-400">S{{ index + 1 }}</span>
                  <span class="font-semibold text-green-400">${{ Number(level).toFixed(4) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Catalysts -->
        <div class="card" v-if="result.catalysts && result.catalysts.length > 0">
          <h3 class="text-xl font-bold mb-4">Potential Catalysts</h3>
          <div class="space-y-3">
            <div v-for="(catalyst, index) in result.catalysts" :key="index" class="p-4 rounded-lg bg-white/5 border border-white/10">
              <div class="flex items-start justify-between mb-2">
                <h4 class="font-semibold text-white">{{ catalyst.description }}</h4>
                <span :class="[
                  'px-2 py-1 rounded text-xs font-semibold',
                  catalyst.probability === 'HIGH' ? 'bg-green-500/20 text-green-400' :
                  catalyst.probability === 'MEDIUM' ? 'bg-yellow-500/20 text-yellow-400' :
                  'bg-gray-500/20 text-gray-400'
                ]">{{ catalyst.probability }}</span>
              </div>
              <div class="flex items-center gap-4 text-sm">
                <span class="text-purple-400">{{ catalyst.type }}</span>
                <span class="text-gray-500">•</span>
                <span class="text-gray-400">{{ catalyst.timeline }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Evidence Sentences -->
        <div class="card" v-if="result.evidence_sentences && result.evidence_sentences.length > 0">
          <h3 class="text-xl font-bold mb-4">Evidence & Key Points</h3>
          <ul class="space-y-2">
            <li v-for="(sentence, index) in result.evidence_sentences" :key="index" class="flex items-start gap-3">
              <svg class="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-gray-300 text-sm">{{ sentence }}</span>
            </li>
          </ul>
        </div>

        <!-- AI Explanation -->
        <div class="card">
          <h3 class="text-xl font-bold mb-4">AI Explanation</h3>
          <p class="text-gray-300 leading-relaxed whitespace-pre-line">{{ result.explainability_text }}</p>
        </div>

        <!-- Risk Notes -->
        <div class="card bg-yellow-500/10 border-yellow-500/30" v-if="result.risk_notes">
          <h3 class="text-xl font-bold mb-4 text-yellow-400 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Risk Warnings
          </h3>
          <p class="text-yellow-200 leading-relaxed">{{ result.risk_notes }}</p>
        </div>

        <!-- Metadata -->
        <div class="card bg-white/5">
          <h3 class="text-lg font-bold mb-4">Analysis Metadata</h3>
          <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div>
              <p class="text-gray-400 mb-1">Symbol</p>
              <p class="font-semibold">{{ result.symbol }}</p>
            </div>
            <div>
              <p class="text-gray-400 mb-1">Instrument Name</p>
              <p class="font-semibold">{{ result.instrument_name }}</p>
            </div>
            <div>
              <p class="text-gray-400 mb-1">Type</p>
              <p class="font-semibold">{{ result.instrument_type }}</p>
            </div>
            <div>
              <p class="text-gray-400 mb-1">Time Horizon</p>
              <p class="font-semibold">{{ result.time_horizon }}</p>
            </div>
            <div>
              <p class="text-gray-400 mb-1">Final Score</p>
              <p class="font-semibold">{{ result.final_score ?? 'N/A' }}</p>
            </div>
            <div>
              <p class="text-gray-400 mb-1">Generated At</p>
              <p class="font-semibold">{{ formatDateTime(result.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { analysisAPI } from '@/api/client'

const router = useRouter()
const route = useRoute()
const form = ref({
  symbol: route.query.symbol || '',
  timeHorizon: 'medium_term'
})
const loading = ref(false)
const result = ref(null)

function formatDateTime(dateString) {
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

async function handleAnalyze() {
  if (!form.value.symbol) return
  
  loading.value = true
  result.value = null

  try {
    const response = await analysisAPI.generate({
      symbol: form.value.symbol.toUpperCase(),
      time_horizon: form.value.timeHorizon
    })
    
    // Backend response sudah di-extract oleh interceptor
    // Data format: AnalysisResource dengan semua computational fields
    result.value = response.data
    
  } catch (error) {
    console.error('Analysis failed:', error)
    
    // Handle rate limit error
    const errorMsg = error.userMessage || error.message || 'Analysis failed. Please try again.'
    if (error.response?.status === 429 || errorMsg.includes('rate') || errorMsg.includes('wait')) {
      alert(errorMsg)
    } else {
      alert(errorMsg)
    }
  } finally {
    loading.value = false
  }
}
</script>

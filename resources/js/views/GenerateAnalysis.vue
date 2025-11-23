<template>
  <div class="generate-analysis">
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">Generate AI Analysis</h1>
      <p class="page-subtitle">Get comprehensive market analysis powered by AI</p>
    </div>

    <!-- Symbol Selection -->
    <div class="section symbol-section">
      <h2 class="section-title">Select Symbol</h2>
      
      <div class="symbol-selector">
        <select v-model="selectedSymbol" class="symbol-select" @change="handleSymbolChange">
          <option value="">Choose a symbol...</option>
          <option v-for="symbol in availableSymbols" :key="symbol" :value="symbol">
            {{ symbol }}
          </option>
        </select>
        
        <input
          v-model="customSymbol"
          type="text"
          placeholder="Or enter custom symbol (e.g., BTCUSDT)"
          class="symbol-input"
          @input="handleCustomSymbol"
        />
      </div>
    </div>

    <!-- Market Snapshot -->
    <div v-if="selectedSymbol" class="section snapshot-section">
      <h2 class="section-title">Current Market State</h2>
      
      <div v-if="isLoadingSnapshot" class="snapshot-loading">
        <Skeleton height="100px" />
      </div>
      
      <div v-else class="snapshot-grid">
        <div class="snapshot-item">
          <div class="snapshot-label">Price</div>
          <div class="snapshot-value">{{ formatPrice(marketSnapshot.price) }}</div>
        </div>
        
        <div class="snapshot-item">
          <div class="snapshot-label">24h Change</div>
          <div class="snapshot-value" :class="changeClass">
            {{ formatChange(marketSnapshot.change_percent) }}
          </div>
        </div>
        
        <div class="snapshot-item">
          <div class="snapshot-label">Volume</div>
          <div class="snapshot-value">{{ formatVolume(marketSnapshot.volume) }}</div>
        </div>
        
        <div class="snapshot-item">
          <div class="snapshot-label">Volatility</div>
          <div class="snapshot-value">
            <Badge :variant="getVolatilityVariant(marketSnapshot.volatility)">
              {{ marketSnapshot.volatility || 'Medium' }}
            </Badge>
          </div>
        </div>
      </div>
    </div>

    <!-- Quantitative Indicators Preview -->
    <div v-if="selectedSymbol" class="section indicators-section">
      <h2 class="section-title">Quantitative Indicators</h2>
      
      <div v-if="isLoadingIndicators" class="indicators-loading">
        <Skeleton v-for="i in 4" :key="i" height="120px" />
      </div>
      
      <div v-else class="indicators-grid">
        <IndicatorCard
          name="RSI-14"
          :value="quantIndicators.rsi || 50"
          :min="0"
          :max="100"
          :signal="getRSISignal(quantIndicators.rsi)"
        />
        
        <IndicatorCard
          name="MACD"
          :value="quantIndicators.macd || 0"
          :min="-100"
          :max="100"
          :signal="getMACDSignal(quantIndicators.macd)"
        />
        
        <IndicatorCard
          name="ADX"
          :value="quantIndicators.adx || 25"
          :min="0"
          :max="100"
          :signal="getADXSignal(quantIndicators.adx)"
        />
        
        <IndicatorCard
          name="Trend Score"
          :value="quantIndicators.trend_score || 0"
          :min="-1"
          :max="1"
          :signal="getTrendSignal(quantIndicators.trend_score)"
        />
      </div>
    </div>

    <!-- Sentiment Preview -->
    <div v-if="selectedSymbol" class="section sentiment-section">
      <h2 class="section-title">Sentiment Analysis</h2>
      
      <div v-if="isLoadingSentiment" class="sentiment-loading">
        <Skeleton height="80px" />
      </div>
      
      <div v-else class="sentiment-container">
        <div class="sentiment-gauge">
          <div class="gauge-label">Overall Sentiment</div>
          <div class="gauge-value" :class="getSentimentClass(sentimentData.polarity)">
            {{ formatSentiment(sentimentData.polarity) }}
          </div>
          <div class="gauge-bar">
            <div class="gauge-fill" :style="getSentimentBarStyle(sentimentData.polarity)"></div>
          </div>
        </div>
        
        <div class="sentiment-stats">
          <div class="stat">
            <span class="stat-label">News Coverage:</span>
            <span class="stat-value">{{ sentimentData.news_count || 0 }} articles</span>
          </div>
          <div class="stat">
            <span class="stat-label">Sources:</span>
            <span class="stat-value">{{ sentimentData.sources || 0 }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Generate Button -->
    <div v-if="selectedSymbol" class="section action-section">
      <CountdownButton
        :disabled="!canGenerate"
        :initial-countdown="analysisStore.rateLimitRemaining"
        :duration="60"
        @click="handleGenerate"
      >
        <span v-if="analysisStore.isGenerating">Generating...</span>
        <span v-else>🚀 Generate AI Analysis</span>
      </CountdownButton>
      
      <p v-if="analysisStore.isRateLimited" class="rate-limit-message">
        ⏳ Please wait {{ analysisStore.rateLimitRemaining }}s before generating another analysis
      </p>
      
      <p v-if="errorMessage" class="error-message">
        ❌ {{ errorMessage }}
      </p>
    </div>

    <!-- Analysis Result -->
    <transition name="fade-slide">
      <div v-if="analysisResult" ref="resultSection" class="section result-section">
        <h2 class="section-title">Analysis Result</h2>
        
        <div class="result-container">
          <!-- Score Card -->
          <div class="score-card">
            <div class="score-header">
              <h3>Final Score</h3>
              <Badge :variant="getRecommendationVariant(analysisResult.recommendation)" size="lg">
                {{ analysisResult.recommendation }}
              </Badge>
            </div>
            
            <div class="score-gauge">
              <svg width="200" height="200" viewBox="0 0 200 200">
                <circle cx="100" cy="100" r="80" fill="none" stroke="#e0e0e0" stroke-width="20"/>
                <circle
                  cx="100"
                  cy="100"
                  r="80"
                  fill="none"
                  :stroke="getScoreColor(analysisResult.final_score)"
                  stroke-width="20"
                  :stroke-dasharray="getScoreDashArray(analysisResult.final_score)"
                  stroke-linecap="round"
                  transform="rotate(-90 100 100)"
                />
                <text x="100" y="100" text-anchor="middle" dy=".3em" font-size="32" font-weight="bold">
                  {{ formatScore(analysisResult.final_score) }}
                </text>
              </svg>
            </div>
            
            <div class="confidence-bar">
              <div class="confidence-label">Confidence: {{ formatPercentage(analysisResult.confidence) }}</div>
              <div class="confidence-progress">
                <div class="confidence-fill" :style="{ width: `${analysisResult.confidence * 100}%` }"></div>
              </div>
            </div>
          </div>

          <!-- Insights -->
          <div class="insights-card">
            <h3>Key Drivers</h3>
            <div class="drivers-list">
              <div v-for="(driver, index) in analysisResult.top_drivers" :key="index" class="driver-item">
                <span class="driver-name">{{ driver.factor || driver.name }}</span>
                <Badge :variant="driver.impact === 'positive' ? 'success' : 'danger'" size="sm">
                  {{ driver.impact || 'neutral' }}
                </Badge>
              </div>
            </div>
            
            <h3 class="explainability-title">Analysis Explanation</h3>
            <p class="explainability-text">{{ analysisResult.explainability_text }}</p>
            
            <div v-if="analysisResult.risk_notes" class="risk-notes">
              <h4>⚠️ Risk Notes</h4>
              <p>{{ analysisResult.risk_notes }}</p>
            </div>
          </div>

          <!-- Position Recommendation -->
          <div class="position-card">
            <h3>Position Sizing</h3>
            <div class="position-content">
              <div class="position-item">
                <span class="position-label">Risk Level:</span>
                <Badge :variant="getRiskVariant(analysisResult.risk_level)" size="lg">
                  {{ analysisResult.risk_level }}
                </Badge>
              </div>
              
              <div class="position-item">
                <span class="position-label">Recommended Size:</span>
                <span class="position-value">
                  {{ analysisResult.position_size_recommendation?.size_percent || 10 }}%
                </span>
              </div>
              
              <div class="position-item">
                <span class="position-label">Time Horizon:</span>
                <span class="position-value">{{ formatTimeHorizon(analysisResult.time_horizon) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useAnalysisStore } from '@/stores/analysis'
import { useMarketStore } from '@/stores/market'
import { useToast } from '@/composables/useToast'
import CountdownButton from '@/components/molecules/CountdownButton.vue'
import IndicatorCard from '@/components/molecules/IndicatorCard.vue'
import Badge from '@/components/atoms/Badge.vue'
import Skeleton from '@/components/atoms/Skeleton.vue'

const analysisStore = useAnalysisStore()
const marketStore = useMarketStore()
const toast = useToast()

const selectedSymbol = ref('')
const customSymbol = ref('')
const analysisResult = ref(null)
const errorMessage = ref('')
const resultSection = ref(null)

const isLoadingSnapshot = ref(false)
const isLoadingIndicators = ref(false)
const isLoadingSentiment = ref(false)

const marketSnapshot = ref({
  price: 0,
  change_percent: 0,
  volume: 0,
  volatility: 'Medium'
})

const quantIndicators = ref({
  rsi: 50,
  macd: 0,
  adx: 25,
  trend_score: 0
})

const sentimentData = ref({
  polarity: 0,
  news_count: 0,
  sources: 0
})

const availableSymbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'XRPUSDT', 'ADAUSDT', 'DOGEUSDT', 'MATICUSDT']

const canGenerate = computed(() => {
  return selectedSymbol.value && !analysisStore.isGenerating && !analysisStore.isRateLimited
})

const changeClass = computed(() => ({
  'change-positive': marketSnapshot.value.change_percent > 0,
  'change-negative': marketSnapshot.value.change_percent < 0
}))

function handleSymbolChange() {
  customSymbol.value = ''
  loadSymbolData()
}

function handleCustomSymbol() {
  if (customSymbol.value) {
    selectedSymbol.value = customSymbol.value.toUpperCase()
    loadSymbolData()
  }
}

async function loadSymbolData() {
  if (!selectedSymbol.value) return
  
  // Load market snapshot (mock data for now)
  isLoadingSnapshot.value = true
  setTimeout(() => {
    marketSnapshot.value = {
      price: 43250.50,
      change_percent: 2.35,
      volume: 1250000000,
      volatility: 'Medium'
    }
    isLoadingSnapshot.value = false
  }, 500)
  
  // Load indicators (mock data)
  isLoadingIndicators.value = true
  setTimeout(() => {
    quantIndicators.value = {
      rsi: 65,
      macd: 15,
      adx: 35,
      trend_score: 0.45
    }
    isLoadingIndicators.value = false
  }, 600)
  
  // Load sentiment (mock data)
  isLoadingSentiment.value = true
  setTimeout(() => {
    sentimentData.value = {
      polarity: 0.3,
      news_count: 25,
      sources: 8
    }
    isLoadingSentiment.value = false
  }, 700)
}

async function handleGenerate() {
  errorMessage.value = ''
  analysisResult.value = null
  
  const result = await analysisStore.generate(selectedSymbol.value)
  
  if (result.success) {
    analysisResult.value = result.data
    toast.success('Analysis generated successfully!')
    
    // Smooth scroll to result
    await nextTick()
    resultSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } else {
    errorMessage.value = result.error
    toast.error(result.error)
  }
}

// Helper functions
function formatPrice(price) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 8
  }).format(price)
}

function formatChange(change) {
  const sign = change >= 0 ? '+' : ''
  return `${sign}${change.toFixed(2)}%`
}

function formatVolume(volume) {
  if (volume >= 1e9) return `$${(volume / 1e9).toFixed(2)}B`
  if (volume >= 1e6) return `$${(volume / 1e6).toFixed(2)}M`
  if (volume >= 1e3) return `$${(volume / 1e3).toFixed(2)}K`
  return `$${volume.toFixed(0)}`
}

function formatScore(score) {
  return score.toFixed(2)
}

function formatPercentage(value) {
  return `${(value * 100).toFixed(0)}%`
}

function formatSentiment(polarity) {
  if (polarity > 0.3) return 'Positive'
  if (polarity < -0.3) return 'Negative'
  return 'Neutral'
}

function formatTimeHorizon(horizon) {
  return horizon?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Medium Term'
}

function getVolatilityVariant(volatility) {
  if (volatility === 'High' || volatility === 'Extreme') return 'danger'
  if (volatility === 'Low') return 'success'
  return 'default'
}

function getRSISignal(rsi) {
  if (rsi > 70) return 'STRONG_SELL'
  if (rsi > 60) return 'SELL'
  if (rsi < 30) return 'STRONG_BUY'
  if (rsi < 40) return 'BUY'
  return 'NEUTRAL'
}

function getMACDSignal(macd) {
  if (macd > 50) return 'STRONG_BUY'
  if (macd > 0) return 'BUY'
  if (macd < -50) return 'STRONG_SELL'
  if (macd < 0) return 'SELL'
  return 'NEUTRAL'
}

function getADXSignal(adx) {
  if (adx > 50) return 'STRONG_BUY'
  if (adx > 25) return 'BUY'
  return 'NEUTRAL'
}

function getTrendSignal(score) {
  if (score > 0.5) return 'STRONG_BUY'
  if (score > 0.2) return 'BUY'
  if (score < -0.5) return 'STRONG_SELL'
  if (score < -0.2) return 'SELL'
  return 'NEUTRAL'
}

function getSentimentClass(polarity) {
  if (polarity > 0.3) return 'sentiment-positive'
  if (polarity < -0.3) return 'sentiment-negative'
  return 'sentiment-neutral'
}

function getSentimentBarStyle(polarity) {
  const percentage = ((polarity + 1) / 2) * 100
  let color = '#a0aec0'
  if (polarity > 0.3) color = '#48bb78'
  else if (polarity < -0.3) color = '#f56565'
  
  return { width: `${percentage}%`, backgroundColor: color }
}

function getRecommendationVariant(recommendation) {
  if (recommendation === 'BUY') return 'success'
  if (recommendation === 'SELL') return 'danger'
  return 'default'
}

function getRiskVariant(riskLevel) {
  if (riskLevel === 'HIGH') return 'danger'
  if (riskLevel === 'LOW') return 'success'
  return 'warning'
}

function getScoreColor(score) {
  if (score > 0.3) return '#48bb78'
  if (score < -0.3) return '#f56565'
  return '#a0aec0'
}

function getScoreDashArray(score) {
  const percentage = ((score + 1) / 2) * 100
  const circumference = 2 * Math.PI * 80
  const dash = (percentage / 100) * circumference
  return `${dash} ${circumference}`
}
</script>

<style scoped>
.generate-analysis {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: var(--color-text);
}

.page-subtitle {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin: 0;
}

.section {
  margin-bottom: 32px;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0 0 16px 0;
  color: var(--color-text);
}

/* Symbol Selection */
.symbol-selector {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.symbol-select,
.symbol-input {
  flex: 1;
  min-width: 250px;
  padding: 12px 16px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-surface);
  color: var(--color-text);
  font-size: 1rem;
}

/* Market Snapshot */
.snapshot-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  padding: 20px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
}

.snapshot-item {
  text-align: center;
}

.snapshot-label {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 8px;
}

.snapshot-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text);
}

.change-positive {
  color: var(--color-success);
}

.change-negative {
  color: var(--color-danger);
}

/* Indicators */
.indicators-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

/* Sentiment */
.sentiment-container {
  padding: 20px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  display: flex;
  gap: 24px;
  align-items: center;
}

.sentiment-gauge {
  flex: 1;
}

.gauge-label {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 8px;
}

.gauge-value {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 12px;
}

.sentiment-positive {
  color: var(--color-success);
}

.sentiment-negative {
  color: var(--color-danger);
}

.sentiment-neutral {
  color: var(--color-text-muted);
}

.gauge-bar {
  width: 100%;
  height: 12px;
  background: var(--color-surface-elevated);
  border-radius: 6px;
  overflow: hidden;
}

.gauge-fill {
  height: 100%;
  transition: width 500ms ease, background-color 300ms ease;
  border-radius: 6px;
}

.sentiment-stats {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat {
  display: flex;
  justify-content: space-between;
}

.stat-label {
  color: var(--color-text-muted);
}

.stat-value {
  font-weight: 600;
  color: var(--color-text);
}

/* Action Section */
.action-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 32px;
  background: var(--color-surface);
  border: 2px dashed var(--color-border);
  border-radius: 12px;
}

.rate-limit-message {
  color: var(--color-warning);
  font-weight: 600;
  margin: 0;
}

.error-message {
  color: var(--color-danger);
  font-weight: 600;
  margin: 0;
}

/* Result Section */
.result-section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 24px;
}

.result-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.score-card,
.insights-card,
.position-card {
  padding: 20px;
  background: var(--color-surface-elevated);
  border-radius: 12px;
}

.score-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.score-gauge {
  display: flex;
  justify-content: center;
  margin: 24px 0;
}

.confidence-bar {
  margin-top: 20px;
}

.confidence-label {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 8px;
}

.confidence-progress {
  height: 8px;
  background: var(--color-surface);
  border-radius: 4px;
  overflow: hidden;
}

.confidence-fill {
  height: 100%;
  background: linear-gradient(90deg, #0d6efd, #00b4d8);
  transition: width 500ms ease;
}

.drivers-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 24px;
}

.driver-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--color-surface);
  border-radius: 8px;
}

.driver-name {
  font-weight: 600;
  color: var(--color-text);
}

.explainability-title {
  font-size: 1rem;
  font-weight: 700;
  margin: 16px 0 12px 0;
}

.explainability-text {
  line-height: 1.6;
  color: var(--color-text);
}

.risk-notes {
  margin-top: 20px;
  padding: 16px;
  background: var(--color-warning-light);
  border-left: 3px solid var(--color-warning);
  border-radius: 8px;
}

.risk-notes h4 {
  margin: 0 0 8px 0;
  color: var(--color-warning);
}

.risk-notes p {
  margin: 0;
  color: var(--color-text);
}

.position-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.position-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.position-label {
  font-weight: 600;
  color: var(--color-text-muted);
}

.position-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text);
}

/* Animations */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 500ms ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

@media (max-width: 768px) {
  .generate-analysis {
    padding: 16px;
  }
  
  .symbol-selector {
    flex-direction: column;
  }
  
  .result-container {
    grid-template-columns: 1fr;
  }
}
</style>

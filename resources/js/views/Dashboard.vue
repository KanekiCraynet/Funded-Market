<template>
  <div class="dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <div>
        <h1 class="page-title">Market Dashboard</h1>
        <p class="page-subtitle">Real-time market overview and analysis</p>
      </div>
      
      <RegimePill v-if="currentRegime" :regime="currentRegime" />
    </div>

    <!-- Market Overview Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <div class="stat-label">Total Instruments</div>
          <div class="stat-value">{{ marketStore.instruments.length }}</div>
        </div>
      </div>
      
      <div class="stat-card stat-success">
        <div class="stat-icon">📈</div>
        <div class="stat-content">
          <div class="stat-label">Top Gainers</div>
          <div class="stat-value">{{ marketStore.topGainers.length }}</div>
        </div>
      </div>
      
      <div class="stat-card stat-danger">
        <div class="stat-icon">📉</div>
        <div class="stat-content">
          <div class="stat-label">Top Losers</div>
          <div class="stat-value">{{ marketStore.topLosers.length }}</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">🔥</div>
        <div class="stat-content">
          <div class="stat-label">Trending</div>
          <div class="stat-value">{{ marketStore.trendingInstruments.length }}</div>
        </div>
      </div>
    </div>

    <!-- Price Tiles -->
    <div class="section">
      <h2 class="section-title">Watchlist</h2>
      
      <div v-if="isLoading" class="price-tiles-grid">
        <Skeleton v-for="i in 6" :key="i" height="140px" />
      </div>
      
      <div v-else class="price-tiles-grid">
        <PriceTile
          v-for="symbol in watchlist"
          :key="symbol"
          :symbol="symbol"
          :price="getPriceData(symbol)?.price || 0"
          :change="getPriceData(symbol)?.change || 0"
          :change-percent="getPriceData(symbol)?.change_percent || 0"
          :volume="getPriceData(symbol)?.volume || 0"
          :high="getPriceData(symbol)?.high || 0"
          :low="getPriceData(symbol)?.low || 0"
          @click="handleSymbolClick"
        />
      </div>
    </div>

    <!-- Main Chart Section -->
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">{{ selectedSymbol }} Chart</h2>
        <div class="chart-controls">
          <select v-model="selectedTimeframe" class="timeframe-select">
            <option value="1m">1m</option>
            <option value="5m">5m</option>
            <option value="15m">15m</option>
            <option value="1h">1h</option>
            <option value="4h">4h</option>
            <option value="1d">1d</option>
          </select>
        </div>
      </div>
      
      <div class="chart-container">
        <div v-if="isLoadingChart" class="chart-loading">
          <Skeleton height="400px" />
        </div>
        <div v-else ref="chartElement" class="chart"></div>
      </div>
    </div>

    <!-- Quick Insights -->
    <div class="section">
      <h2 class="section-title">Quick Insights</h2>
      
      <div class="insights-grid">
        <IndicatorCard
          name="RSI-14"
          :value="indicators.rsi || 50"
          :min="0"
          :max="100"
          :signal="getRSISignal(indicators.rsi)"
        />
        
        <IndicatorCard
          name="MACD"
          :value="indicators.macd || 0"
          :min="-100"
          :max="100"
          :signal="getMACDSignal(indicators.macd)"
        />
        
        <IndicatorCard
          name="ADX"
          :value="indicators.adx || 25"
          :min="0"
          :max="100"
          :signal="getADXSignal(indicators.adx)"
        />
        
        <IndicatorCard
          name="Volume Ratio"
          :value="indicators.volume_ratio || 1"
          :min="0"
          :max="3"
          :signal="getVolumeSignal(indicators.volume_ratio)"
        />
      </div>
    </div>

    <!-- Trending & Top Movers -->
    <div class="movers-section">
      <div class="movers-column">
        <h3 class="movers-title">🔥 Trending</h3>
        <div class="movers-list">
          <div v-for="item in marketStore.trendingInstruments.slice(0, 5)" :key="item.symbol" class="mover-item">
            <span class="mover-symbol">{{ item.symbol }}</span>
            <Badge :variant="item.change > 0 ? 'success' : 'danger'" size="sm">
              {{ item.change_percent }}%
            </Badge>
          </div>
        </div>
      </div>
      
      <div class="movers-column">
        <h3 class="movers-title">📈 Top Gainers</h3>
        <div class="movers-list">
          <div v-for="item in marketStore.topGainers.slice(0, 5)" :key="item.symbol" class="mover-item">
            <span class="mover-symbol">{{ item.symbol }}</span>
            <Badge variant="success" size="sm">
              +{{ item.change_percent }}%
            </Badge>
          </div>
        </div>
      </div>
      
      <div class="movers-column">
        <h3 class="movers-title">📉 Top Losers</h3>
        <div class="movers-list">
          <div v-for="item in marketStore.topLosers.slice(0, 5)" :key="item.symbol" class="mover-item">
            <span class="mover-symbol">{{ item.symbol }}</span>
            <Badge variant="danger" size="sm">
              {{ item.change_percent }}%
            </Badge>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useMarketStore } from '@/stores/market'
import PriceTile from '@/components/molecules/PriceTile.vue'
import IndicatorCard from '@/components/molecules/IndicatorCard.vue'
import RegimePill from '@/components/molecules/RegimePill.vue'
import Badge from '@/components/atoms/Badge.vue'
import Skeleton from '@/components/atoms/Skeleton.vue'

const marketStore = useMarketStore()

const watchlist = ref(['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'XRPUSDT', 'ADAUSDT'])
const selectedSymbol = ref('BTCUSDT')
const selectedTimeframe = ref('1h')
const isLoading = ref(false)
const isLoadingChart = ref(false)
const chartElement = ref(null)
const indicators = ref({
  rsi: 50,
  macd: 0,
  adx: 25,
  volume_ratio: 1
})

const currentRegime = computed(() => {
  // Mock regime data - will be replaced with real data
  return {
    regime: 'bull',
    label: 'Bullish',
    strength: 0.75,
    strength_label: 'Strong',
    color: '#48bb78'
  }
})

function getPriceData(symbol) {
  return marketStore.marketData[symbol] || {
    price: 0,
    change: 0,
    change_percent: 0,
    volume: 0,
    high: 0,
    low: 0
  }
}

function handleSymbolClick(symbol) {
  selectedSymbol.value = symbol
  loadChartData(symbol)
}

async function loadChartData(symbol) {
  isLoadingChart.value = true
  try {
    const data = await marketStore.fetchHistoricalData(symbol, selectedTimeframe.value, 100)
    // Chart rendering will be implemented here
    console.log('Chart data loaded:', data)
  } finally {
    isLoadingChart.value = false
  }
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

function getVolumeSignal(ratio) {
  if (ratio > 2) return 'STRONG_BUY'
  if (ratio > 1.5) return 'BUY'
  if (ratio < 0.5) return 'SELL'
  return 'NEUTRAL'
}

onMounted(async () => {
  isLoading.value = true
  
  // Start polling for real-time data
  marketStore.startPolling(30000) // 30 seconds
  
  // Fetch initial data
  await marketStore.fetchMarketOverview()
  await marketStore.fetchInstruments()
  
  // Load initial chart
  await loadChartData(selectedSymbol.value)
  
  isLoading.value = false
})

onUnmounted(() => {
  marketStore.stopPolling()
})
</script>

<style scoped>
.dashboard {
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 8px 0;
}

.page-subtitle {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  gap: 16px;
  align-items: center;
}

.stat-card.stat-success {
  border-left: 3px solid var(--color-success);
}

.stat-card.stat-danger {
  border-left: 3px solid var(--color-danger);
}

.stat-icon {
  font-size: 2rem;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 4px;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text);
}

.section {
  margin-bottom: 32px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 16px 0;
}

.price-tiles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.chart-controls {
  display: flex;
  gap: 8px;
}

.timeframe-select {
  padding: 8px 16px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-surface);
  color: var(--color-text);
  font-size: 0.875rem;
  cursor: pointer;
}

.chart-container {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 16px;
  min-height: 400px;
}

.chart {
  width: 100%;
  height: 400px;
}

.chart-loading {
  width: 100%;
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.movers-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.movers-column {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 20px;
}

.movers-title {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 16px 0;
  color: var(--color-text);
}

.movers-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.mover-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px;
  background: var(--color-surface-elevated);
  border-radius: 8px;
}

.mover-symbol {
  font-weight: 600;
  color: var(--color-text);
}

@media (max-width: 768px) {
  .dashboard {
    padding: 16px;
  }
  
  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .price-tiles-grid {
    grid-template-columns: 1fr;
  }
  
  .movers-section {
    grid-template-columns: 1fr;
  }
}
</style>

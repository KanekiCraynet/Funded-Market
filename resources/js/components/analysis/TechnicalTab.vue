<template>
  <div class="space-y-6">
    <!-- Composite Score Overview -->
    <Card title="Composite Scores" variant="glass">
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-4">
        <div v-for="(score, key) in compositeScores" :key="key" class="text-center">
          <div class="text-3xl font-bold mb-1" :class="getScoreColor(score.value)">
            {{ formatScore(score.value) }}
          </div>
          <div class="text-sm text-gray-400">{{ score.label }}</div>
          <div class="w-full bg-white/10 rounded-full h-1 mt-2">
            <div 
              :class="getScoreBarColor(score.value)"
              class="h-1 rounded-full transition-all"
              :style="{ width: `${score.value * 100}%` }"
            ></div>
          </div>
        </div>
      </div>
    </Card>

    <!-- Indicator Categories -->
    <div class="grid md:grid-cols-2 gap-6">
      <!-- Trend Indicators -->
      <Card title="Trend Indicators" variant="glass">
        <div class="p-4 space-y-3">
          <IndicatorRow 
            v-for="(value, key) in trendIndicators" 
            :key="key"
            :name="formatIndicatorName(key)"
            :value="value"
            :signal="getIndicatorSignal(key, value)"
          />
        </div>
      </Card>

      <!-- Momentum Indicators -->
      <Card title="Momentum Indicators" variant="glass">
        <div class="p-4 space-y-3">
          <IndicatorRow 
            v-for="(value, key) in momentumIndicators" 
            :key="key"
            :name="formatIndicatorName(key)"
            :value="value"
            :signal="getIndicatorSignal(key, value)"
          />
        </div>
      </Card>

      <!-- Volatility Indicators -->
      <Card title="Volatility Indicators" variant="glass">
        <div class="p-4 space-y-3">
          <IndicatorRow 
            v-for="(value, key) in volatilityIndicators" 
            :key="key"
            :name="formatIndicatorName(key)"
            :value="value"
            :signal="getIndicatorSignal(key, value)"
          />
        </div>
      </Card>

      <!-- Volume Indicators -->
      <Card title="Volume Indicators" variant="glass">
        <div class="p-4 space-y-3">
          <IndicatorRow 
            v-for="(value, key) in volumeIndicators" 
            :key="key"
            :name="formatIndicatorName(key)"
            :value="value"
            :signal="getIndicatorSignal(key, value)"
          />
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import IndicatorRow from './IndicatorRow.vue'

const props = defineProps({
  indicators: {
    type: Object,
    default: () => ({})
  }
})

const compositeScores = computed(() => {
  const comp = props.indicators?.composite || {}
  return {
    overall: { label: 'Overall', value: comp.overall_score || 0 },
    trend: { label: 'Trend', value: comp.trend_score || 0 },
    momentum: { label: 'Momentum', value: comp.momentum_score || 0 },
    volatility: { label: 'Volatility', value: comp.volatility_score || 0 },
    volume: { label: 'Volume', value: comp.volume_score || 0 }
  }
})

const trendIndicators = computed(() => props.indicators?.trend || {})
const momentumIndicators = computed(() => props.indicators?.momentum || {})
const volatilityIndicators = computed(() => props.indicators?.volatility || {})
const volumeIndicators = computed(() => props.indicators?.volume || {})

function formatScore(value) {
  if (value == null || isNaN(value)) return '0'
  return Math.round(value * 100)
}

function formatIndicatorName(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function getScoreColor(value) {
  if (value >= 0.7) return 'text-green-400'
  if (value >= 0.5) return 'text-blue-400'
  if (value >= 0.3) return 'text-yellow-400'
  return 'text-red-400'
}

function getScoreBarColor(value) {
  if (value >= 0.7) return 'bg-green-500'
  if (value >= 0.5) return 'bg-blue-500'
  if (value >= 0.3) return 'bg-yellow-500'
  return 'bg-red-500'
}

function getIndicatorSignal(key, value) {
  // RSI
  if (key === 'rsi') {
    if (value > 70) return { signal: 'OVERBOUGHT', color: 'red' }
    if (value < 30) return { signal: 'OVERSOLD', color: 'green' }
    return { signal: 'NEUTRAL', color: 'yellow' }
  }
  
  // ADX
  if (key === 'adx') {
    if (value > 25) return { signal: 'STRONG', color: 'green' }
    if (value < 20) return { signal: 'WEAK', color: 'red' }
    return { signal: 'MODERATE', color: 'yellow' }
  }
  
  // Default
  return { signal: 'NEUTRAL', color: 'gray' }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Overall Risk Score -->
    <Card title="Overall Risk Assessment" variant="glass">
      <div class="p-6">
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-6xl font-bold mb-2" :class="getRiskColor(overallRisk)">
              {{ overallRisk }}
            </div>
            <div class="text-gray-400">Risk Score</div>
            <Badge :variant="getRiskVariant(overallRisk)" size="lg" class="mt-3">
              {{ getRiskLevel(overallRisk) }}
            </Badge>
          </div>
          
          <div class="col-span-2">
            <div class="space-y-4">
              <div>
                <div class="flex justify-between text-sm mb-2">
                  <span class="text-gray-400">Market Risk</span>
                  <span class="font-semibold">{{ riskBreakdown.market }}/100</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-2">
                  <div 
                    :class="getRiskBarColor(riskBreakdown.market)"
                    class="h-2 rounded-full"
                    :style="{ width: `${riskBreakdown.market}%` }"
                  ></div>
                </div>
              </div>
              
              <div>
                <div class="flex justify-between text-sm mb-2">
                  <span class="text-gray-400">Volatility Risk</span>
                  <span class="font-semibold">{{ riskBreakdown.volatility }}/100</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-2">
                  <div 
                    :class="getRiskBarColor(riskBreakdown.volatility)"
                    class="h-2 rounded-full"
                    :style="{ width: `${riskBreakdown.volatility}%` }"
                  ></div>
                </div>
              </div>
              
              <div>
                <div class="flex justify-between text-sm mb-2">
                  <span class="text-gray-400">Liquidity Risk</span>
                  <span class="font-semibold">{{ riskBreakdown.liquidity }}/100</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-2">
                  <div 
                    :class="getRiskBarColor(riskBreakdown.liquidity)"
                    class="h-2 rounded-full"
                    :style="{ width: `${riskBreakdown.liquidity}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Card>

    <!-- Risk Factors -->
    <div class="grid md:grid-cols-2 gap-6">
      <Card title="🔴 Major Risk Factors" variant="glass">
        <div class="p-4 space-y-3">
          <div v-for="(factor, index) in majorRisks" :key="index"
               class="p-3 rounded-lg bg-red-500/10 border border-red-500/20">
            <div class="flex items-start justify-between mb-2">
              <h4 class="font-semibold text-white">{{ factor.title }}</h4>
              <Badge variant="danger" size="sm">{{ factor.severity }}</Badge>
            </div>
            <p class="text-sm text-gray-300">{{ factor.description }}</p>
            <div class="mt-2 text-xs text-red-400">
              Impact: {{ factor.impact }}% | Probability: {{ factor.probability }}%
            </div>
          </div>
        </div>
      </Card>

      <Card title="🟡 Minor Risk Factors" variant="glass">
        <div class="p-4 space-y-3">
          <div v-for="(factor, index) in minorRisks" :key="index"
               class="p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
            <div class="flex items-start justify-between mb-2">
              <h4 class="font-semibold text-white">{{ factor.title }}</h4>
              <Badge variant="warning" size="sm">{{ factor.severity }}</Badge>
            </div>
            <p class="text-sm text-gray-300">{{ factor.description }}</p>
            <div class="mt-2 text-xs text-yellow-400">
              Impact: {{ factor.impact }}% | Probability: {{ factor.probability }}%
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Risk Metrics -->
    <Card title="Risk Metrics & Indicators" variant="glass">
      <div class="p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="(metric, key) in riskMetrics" :key="key" class="text-center p-3 rounded-lg bg-white/5">
            <div class="text-2xl font-bold mb-1" :class="getMetricColor(metric.value, metric.threshold)">
              {{ metric.value }}{{ metric.unit }}
            </div>
            <div class="text-xs text-gray-400">{{ metric.label }}</div>
          </div>
        </div>
      </div>
    </Card>

    <!-- Value at Risk (VaR) -->
    <Card title="Value at Risk Analysis" variant="glass">
      <div class="p-6">
        <div class="grid md:grid-cols-3 gap-6 mb-6">
          <div class="text-center p-4 rounded-lg bg-gradient-to-br from-red-500/20 to-transparent border border-red-500/30">
            <div class="text-sm text-gray-400 mb-2">1-Day VaR (95%)</div>
            <div class="text-3xl font-bold text-red-400">{{ varData.oneDay }}%</div>
            <div class="text-xs text-gray-500 mt-1">Maximum expected loss</div>
          </div>
          
          <div class="text-center p-4 rounded-lg bg-gradient-to-br from-orange-500/20 to-transparent border border-orange-500/30">
            <div class="text-sm text-gray-400 mb-2">1-Week VaR (95%)</div>
            <div class="text-3xl font-bold text-orange-400">{{ varData.oneWeek }}%</div>
            <div class="text-xs text-gray-500 mt-1">Weekly risk exposure</div>
          </div>
          
          <div class="text-center p-4 rounded-lg bg-gradient-to-br from-yellow-500/20 to-transparent border border-yellow-500/30">
            <div class="text-sm text-gray-400 mb-2">1-Month VaR (95%)</div>
            <div class="text-3xl font-bold text-yellow-400">{{ varData.oneMonth }}%</div>
            <div class="text-xs text-gray-500 mt-1">Monthly downside risk</div>
          </div>
        </div>
        
        <div class="p-4 rounded-lg bg-white/5">
          <p class="text-sm text-gray-300">
            <strong class="text-white">Interpretation:</strong> There is a 95% confidence that losses will not exceed these thresholds within the specified timeframe.
          </p>
        </div>
      </div>
    </Card>

    <!-- Risk Mitigation Strategies -->
    <Card title="🛡️ Risk Mitigation Strategies" variant="glass">
      <div class="p-4 space-y-3">
        <div v-for="(strategy, index) in mitigationStrategies" :key="index"
             class="flex gap-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="flex-1">
            <h4 class="font-semibold text-white mb-1">{{ strategy.title }}</h4>
            <p class="text-sm text-gray-300">{{ strategy.description }}</p>
            <div class="flex items-center gap-2 mt-2">
              <Badge variant="info" size="sm">{{ strategy.effectiveness }}% Effective</Badge>
              <span class="text-xs text-gray-400">{{ strategy.implementation }}</span>
            </div>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'

const props = defineProps({
  riskData: {
    type: Object,
    default: () => ({})
  }
})

const overallRisk = computed(() => props.riskData?.overall_risk || 0)
const riskBreakdown = computed(() => props.riskData?.breakdown || {
  market: 0,
  volatility: 0,
  liquidity: 0
})
const majorRisks = computed(() => props.riskData?.major_risks || [])
const minorRisks = computed(() => props.riskData?.minor_risks || [])
const riskMetrics = computed(() => props.riskData?.metrics || {})
const varData = computed(() => props.riskData?.var || {
  oneDay: 0,
  oneWeek: 0,
  oneMonth: 0
})
const mitigationStrategies = computed(() => props.riskData?.mitigation_strategies || [])

function getRiskColor(score) {
  if (score >= 70) return 'text-red-400'
  if (score >= 40) return 'text-yellow-400'
  return 'text-green-400'
}

function getRiskVariant(score) {
  if (score >= 70) return 'danger'
  if (score >= 40) return 'warning'
  return 'success'
}

function getRiskLevel(score) {
  if (score >= 70) return 'HIGH RISK'
  if (score >= 40) return 'MODERATE RISK'
  return 'LOW RISK'
}

function getRiskBarColor(score) {
  if (score >= 70) return 'bg-red-500'
  if (score >= 40) return 'bg-yellow-500'
  return 'bg-green-500'
}

function getMetricColor(value, threshold) {
  if (!threshold) return 'text-white'
  if (value > threshold) return 'text-red-400'
  return 'text-green-400'
}
</script>

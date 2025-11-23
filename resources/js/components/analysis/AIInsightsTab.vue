<template>
  <div class="space-y-6">
    <!-- AI Summary -->
    <Card title="AI-Powered Analysis Summary" variant="glass">
      <div class="p-6">
        <div class="prose prose-invert max-w-none">
          <p class="text-lg text-white/90 leading-relaxed">
            {{ aiSummary || 'Generating AI insights...' }}
          </p>
        </div>
      </div>
    </Card>

    <!-- Key Insights -->
    <div class="grid md:grid-cols-2 gap-6">
      <Card title="🎯 Key Insights" variant="glass">
        <div class="p-4 space-y-3">
          <div v-for="(insight, index) in keyInsights" :key="index"
               class="flex gap-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 text-sm font-bold">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <p class="text-white">{{ insight }}</p>
            </div>
          </div>
        </div>
      </Card>

      <Card title="⚠️ Risk Warnings" variant="glass">
        <div class="p-4 space-y-3">
          <div v-for="(warning, index) in riskWarnings" :key="index"
               class="flex gap-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-white">{{ warning }}</p>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Trading Strategy -->
    <Card title="💡 Recommended Trading Strategy" variant="glass">
      <div class="p-6">
        <div class="grid md:grid-cols-3 gap-6 mb-6">
          <div class="text-center p-4 rounded-lg bg-gradient-to-br from-blue-500/20 to-transparent border border-blue-500/30">
            <div class="text-sm text-gray-400 mb-2">Entry Strategy</div>
            <div class="text-lg font-semibold text-white">{{ strategy.entry || 'N/A' }}</div>
          </div>
          
          <div class="text-center p-4 rounded-lg bg-gradient-to-br from-green-500/20 to-transparent border border-green-500/30">
            <div class="text-sm text-gray-400 mb-2">Position Size</div>
            <div class="text-lg font-semibold text-white">{{ strategy.positionSize || 'N/A' }}</div>
          </div>
          
          <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-500/20 to-transparent border border-purple-500/30">
            <div class="text-sm text-gray-400 mb-2">Time Horizon</div>
            <div class="text-lg font-semibold text-white">{{ strategy.timeHorizon || 'N/A' }}</div>
          </div>
        </div>
        
        <div class="prose prose-invert max-w-none">
          <p class="text-white/80">{{ strategy.description || 'Strategy details not available.' }}</p>
        </div>
      </div>
    </Card>

    <!-- Market Context -->
    <Card title="📊 Market Context & Catalysts" variant="glass">
      <div class="p-4 space-y-4">
        <div v-for="(context, index) in marketContext" :key="index"
             class="p-4 rounded-lg bg-white/5 border-l-4" 
             :class="getContextBorderClass(context.type)">
          <div class="flex items-start justify-between mb-2">
            <h4 class="font-semibold text-white">{{ context.title }}</h4>
            <Badge :variant="getContextVariant(context.impact)">
              {{ context.impact }} Impact
            </Badge>
          </div>
          <p class="text-sm text-gray-300">{{ context.description }}</p>
          <div class="flex items-center gap-2 mt-2 text-xs text-gray-400">
            <span>{{ context.source }}</span>
            <span>•</span>
            <span>{{ context.date }}</span>
          </div>
        </div>
      </div>
    </Card>

    <!-- AI Confidence & Reasoning -->
    <Card title="🤖 AI Reasoning & Confidence" variant="glass">
      <div class="p-6">
        <div class="grid md:grid-cols-4 gap-4 mb-6">
          <div v-for="(metric, key) in confidenceMetrics" :key="key" class="text-center">
            <div class="text-3xl font-bold mb-1" :class="getMetricColor(metric.value)">
              {{ metric.value }}%
            </div>
            <div class="text-sm text-gray-400">{{ metric.label }}</div>
            <div class="w-full bg-white/10 rounded-full h-1 mt-2">
              <div 
                :class="getMetricBarColor(metric.value)"
                class="h-1 rounded-full"
                :style="{ width: `${metric.value}%` }"
              ></div>
            </div>
          </div>
        </div>
        
        <div class="p-4 rounded-lg bg-white/5 border border-white/10">
          <h4 class="font-semibold text-white mb-2">Reasoning Process:</h4>
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300">
            <li v-for="(step, index) in reasoningSteps" :key="index">{{ step }}</li>
          </ol>
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
  aiData: {
    type: Object,
    default: () => ({})
  }
})

const aiSummary = computed(() => props.aiData?.summary || '')
const keyInsights = computed(() => props.aiData?.key_insights || [])
const riskWarnings = computed(() => props.aiData?.risk_warnings || [])
const strategy = computed(() => props.aiData?.strategy || {})
const marketContext = computed(() => props.aiData?.market_context || [])
const confidenceMetrics = computed(() => props.aiData?.confidence_metrics || {})
const reasoningSteps = computed(() => props.aiData?.reasoning_steps || [])

function getContextBorderClass(type) {
  const map = {
    'bullish': 'border-green-500',
    'bearish': 'border-red-500',
    'neutral': 'border-yellow-500',
    'technical': 'border-blue-500'
  }
  return map[type] || 'border-gray-500'
}

function getContextVariant(impact) {
  const map = {
    'HIGH': 'danger',
    'MEDIUM': 'warning',
    'LOW': 'info'
  }
  return map[impact] || 'default'
}

function getMetricColor(value) {
  if (value >= 80) return 'text-green-400'
  if (value >= 60) return 'text-blue-400'
  if (value >= 40) return 'text-yellow-400'
  return 'text-red-400'
}

function getMetricBarColor(value) {
  if (value >= 80) return 'bg-green-500'
  if (value >= 60) return 'bg-blue-500'
  if (value >= 40) return 'bg-yellow-500'
  return 'bg-red-500'
}
</script>

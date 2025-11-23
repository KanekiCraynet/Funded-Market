<template>
  <Card title="Analysis Scores" variant="glass">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
      <!-- Fusion Score -->
      <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-500/20 to-transparent border border-purple-500/30">
        <div class="text-sm text-gray-400 mb-2">Fusion Score</div>
        <div class="text-4xl font-bold text-purple-400 mb-2">
          {{ formatScore(fusionScore) }}
        </div>
        <div class="w-full bg-white/10 rounded-full h-2">
          <div 
            class="h-2 rounded-full bg-gradient-to-r from-purple-600 to-purple-400 transition-all duration-500"
            :style="{ width: `${fusionScore * 100}%` }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Combined Analysis</p>
      </div>
      
      <!-- Quant Score -->
      <div class="text-center p-4 rounded-lg bg-gradient-to-br from-blue-500/20 to-transparent border border-blue-500/30">
        <div class="text-sm text-gray-400 mb-2">Quant Score</div>
        <div class="text-4xl font-bold text-blue-400 mb-2">
          {{ formatScore(quantScore) }}
        </div>
        <div class="w-full bg-white/10 rounded-full h-2">
          <div 
            class="h-2 rounded-full bg-gradient-to-r from-blue-600 to-blue-400 transition-all duration-500"
            :style="{ width: `${quantScore * 100}%` }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Technical Indicators</p>
      </div>
      
      <!-- Sentiment Score -->
      <div class="text-center p-4 rounded-lg bg-gradient-to-br from-pink-500/20 to-transparent border border-pink-500/30">
        <div class="text-sm text-gray-400 mb-2">Sentiment Score</div>
        <div class="text-4xl font-bold text-pink-400 mb-2">
          {{ formatScore(sentimentScore) }}
        </div>
        <div class="w-full bg-white/10 rounded-full h-2">
          <div 
            class="h-2 rounded-full bg-gradient-to-r from-pink-600 to-pink-400 transition-all duration-500"
            :style="{ width: `${sentimentScore * 100}%` }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Market Sentiment</p>
      </div>
    </div>
    
    <!-- Additional Metrics -->
    <div v-if="showDetails" class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 mt-2 border-t border-white/10">
      <div class="text-center">
        <div class="text-2xl font-bold text-white">{{ formatScore(dynamicAlpha) }}</div>
        <div class="text-xs text-gray-400">Dynamic Alpha</div>
      </div>
      
      <div class="text-center">
        <div class="text-2xl font-bold text-white">{{ formatScore(correlationScore) }}</div>
        <div class="text-xs text-gray-400">Correlation</div>
      </div>
      
      <div class="text-center">
        <div class="text-2xl font-bold text-white">{{ formatScore(trendScore) }}</div>
        <div class="text-xs text-gray-400">Trend</div>
      </div>
      
      <div class="text-center">
        <div class="text-2xl font-bold text-white">{{ formatScore(momentumScore) }}</div>
        <div class="text-xs text-gray-400">Momentum</div>
      </div>
    </div>
    
    <!-- Toggle Details -->
    <div class="text-center p-2 border-t border-white/10">
      <button 
        @click="showDetails = !showDetails"
        class="text-sm text-purple-400 hover:text-purple-300 transition-colors"
      >
        {{ showDetails ? 'Hide' : 'Show' }} Additional Metrics
      </button>
    </div>
  </Card>
</template>

<script setup>
import { ref } from 'vue'
import Card from '@/components/ui/Card.vue'

const props = defineProps({
  fusionScore: {
    type: Number,
    default: 0
  },
  quantScore: {
    type: Number,
    default: 0
  },
  sentimentScore: {
    type: Number,
    default: 0
  },
  dynamicAlpha: {
    type: Number,
    default: 0
  },
  correlationScore: {
    type: Number,
    default: 0
  },
  trendScore: {
    type: Number,
    default: 0
  },
  momentumScore: {
    type: Number,
    default: 0
  }
})

const showDetails = ref(false)

function formatScore(value) {
  if (value == null || isNaN(value)) return '0.00'
  return (value * 100).toFixed(0)
}
</script>

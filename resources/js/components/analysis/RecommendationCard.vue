<template>
  <Card variant="glass" :class="cardClasses">
    <div class="text-center p-6">
      <!-- Icon -->
      <div class="mb-4 flex justify-center">
        <div :class="iconContainerClasses">
          <!-- BUY Icon -->
          <svg v-if="recommendation === 'BUY'" class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          
          <!-- SELL Icon -->
          <svg v-else-if="recommendation === 'SELL'" class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
          </svg>
          
          <!-- HOLD Icon -->
          <svg v-else class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
          </svg>
        </div>
      </div>
      
      <!-- Recommendation Text -->
      <h2 :class="recommendationClasses" class="text-5xl font-bold mb-2">
        {{ recommendation }}
      </h2>
      
      <p class="text-gray-400 text-sm mb-4">Recommendation</p>
      
      <!-- Confidence Bar -->
      <div class="w-full bg-white/10 rounded-full h-2 mb-2">
        <div 
          :class="confidenceBarClasses"
          class="h-2 rounded-full transition-all duration-500"
          :style="{ width: `${confidence * 100}%` }"
        ></div>
      </div>
      
      <p class="text-sm text-gray-300">
        <span class="font-semibold">{{ formatConfidence(confidence) }}</span> Confidence
      </p>
      
      <!-- Risk Level -->
      <div class="mt-4 pt-4 border-t border-white/10">
        <div class="flex items-center justify-center gap-2">
          <span class="text-sm text-gray-400">Risk Level:</span>
          <Badge :variant="riskVariant" size="md">
            {{ riskLevel }}
          </Badge>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { useFormat } from '@/composables/useFormat'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'

const props = defineProps({
  recommendation: {
    type: String,
    required: true,
    validator: (value) => ['BUY', 'SELL', 'HOLD'].includes(value)
  },
  confidence: {
    type: Number,
    required: true,
    default: 0
  },
  riskLevel: {
    type: String,
    default: 'MEDIUM',
    validator: (value) => ['LOW', 'MEDIUM', 'HIGH'].includes(value)
  }
})

const { formatConfidence } = useFormat()

const cardClasses = computed(() => {
  const baseClass = 'border-2 transition-all duration-300'
  const map = {
    'BUY': 'border-green-500/50 bg-gradient-to-br from-green-500/10 to-transparent',
    'SELL': 'border-red-500/50 bg-gradient-to-br from-red-500/10 to-transparent',
    'HOLD': 'border-yellow-500/50 bg-gradient-to-br from-yellow-500/10 to-transparent'
  }
  return `${baseClass} ${map[props.recommendation] || map.HOLD}`
})

const iconContainerClasses = computed(() => {
  const baseClass = 'w-20 h-20 rounded-full flex items-center justify-center'
  const map = {
    'BUY': 'bg-green-500/20 text-green-400',
    'SELL': 'bg-red-500/20 text-red-400',
    'HOLD': 'bg-yellow-500/20 text-yellow-400'
  }
  return `${baseClass} ${map[props.recommendation] || map.HOLD}`
})

const recommendationClasses = computed(() => {
  const map = {
    'BUY': 'text-green-400',
    'SELL': 'text-red-400',
    'HOLD': 'text-yellow-400'
  }
  return map[props.recommendation] || map.HOLD
})

const confidenceBarClasses = computed(() => {
  const map = {
    'BUY': 'bg-gradient-to-r from-green-600 to-green-400',
    'SELL': 'bg-gradient-to-r from-red-600 to-red-400',
    'HOLD': 'bg-gradient-to-r from-yellow-600 to-yellow-400'
  }
  return map[props.recommendation] || map.HOLD
})

const riskVariant = computed(() => {
  const map = {
    'LOW': 'risk-low',
    'MEDIUM': 'risk-medium',
    'HIGH': 'risk-high'
  }
  return map[props.riskLevel] || 'risk-medium'
})
</script>

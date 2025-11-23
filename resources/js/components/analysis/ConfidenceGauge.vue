<template>
  <Card title="Confidence Level" variant="glass">
    <div class="flex flex-col items-center py-6">
      <!-- SVG Gauge -->
      <div class="relative w-48 h-48">
        <svg class="transform -rotate-90" viewBox="0 0 200 200">
          <!-- Background Circle -->
          <circle
            cx="100"
            cy="100"
            r="80"
            stroke="rgba(255,255,255,0.1)"
            stroke-width="20"
            fill="none"
          />
          
          <!-- Progress Circle -->
          <circle
            cx="100"
            cy="100"
            r="80"
            :stroke="gaugeColor"
            stroke-width="20"
            fill="none"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="dashOffset"
            stroke-linecap="round"
            class="transition-all duration-1000 ease-out"
          />
        </svg>
        
        <!-- Center Text -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <div :class="textColorClass" class="text-5xl font-bold">
            {{ displayValue }}%
          </div>
          <div class="text-sm text-gray-400 mt-1">Confidence</div>
        </div>
      </div>
      
      <!-- Level Description -->
      <div class="mt-6 text-center">
        <Badge :variant="badgeVariant" size="lg">
          {{ confidenceLevel }}
        </Badge>
        <p class="text-sm text-gray-400 mt-2">{{ confidenceDescription }}</p>
      </div>
      
      <!-- Progress Bar Alternative (for mobile) -->
      <div class="w-full mt-6 md:hidden">
        <div class="bg-white/10 rounded-full h-3">
          <div 
            :class="barColorClass"
            class="h-3 rounded-full transition-all duration-1000"
            :style="{ width: `${confidence * 100}%` }"
          ></div>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'

const props = defineProps({
  confidence: {
    type: Number,
    required: true,
    default: 0,
    validator: (value) => value >= 0 && value <= 1
  }
})

// Animation
const displayValue = ref(0)
const radius = 80
const circumference = 2 * Math.PI * radius

onMounted(() => {
  // Animate number
  const target = Math.round(props.confidence * 100)
  const duration = 1000
  const increment = target / (duration / 16)
  
  const animate = () => {
    if (displayValue.value < target) {
      displayValue.value = Math.min(displayValue.value + increment, target)
      requestAnimationFrame(animate)
    } else {
      displayValue.value = target
    }
  }
  
  animate()
})

const dashOffset = computed(() => {
  const percentage = props.confidence
  return circumference - (percentage * circumference)
})

const gaugeColor = computed(() => {
  const conf = props.confidence
  if (conf >= 0.8) return '#10b981' // Green
  if (conf >= 0.6) return '#3b82f6' // Blue
  if (conf >= 0.4) return '#f59e0b' // Yellow
  return '#ef4444' // Red
})

const textColorClass = computed(() => {
  const conf = props.confidence
  if (conf >= 0.8) return 'text-green-400'
  if (conf >= 0.6) return 'text-blue-400'
  if (conf >= 0.4) return 'text-yellow-400'
  return 'text-red-400'
})

const barColorClass = computed(() => {
  const conf = props.confidence
  if (conf >= 0.8) return 'bg-gradient-to-r from-green-600 to-green-400'
  if (conf >= 0.6) return 'bg-gradient-to-r from-blue-600 to-blue-400'
  if (conf >= 0.4) return 'bg-gradient-to-r from-yellow-600 to-yellow-400'
  return 'bg-gradient-to-r from-red-600 to-red-400'
})

const badgeVariant = computed(() => {
  const conf = props.confidence
  if (conf >= 0.8) return 'success'
  if (conf >= 0.6) return 'info'
  if (conf >= 0.4) return 'warning'
  return 'danger'
})

const confidenceLevel = computed(() => {
  const conf = props.confidence
  if (conf >= 0.8) return 'VERY HIGH'
  if (conf >= 0.6) return 'HIGH'
  if (conf >= 0.4) return 'MODERATE'
  return 'LOW'
})

const confidenceDescription = computed(() => {
  const conf = props.confidence
  if (conf >= 0.8) return 'Strong signals across all indicators'
  if (conf >= 0.6) return 'Good alignment of multiple factors'
  if (conf >= 0.4) return 'Mixed signals, proceed with caution'
  return 'Weak signals, high uncertainty'
})
</script>

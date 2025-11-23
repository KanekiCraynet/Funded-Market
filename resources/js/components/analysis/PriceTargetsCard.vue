<template>
  <Card title="Price Targets & Projections" variant="glass">
    <div class="p-6 space-y-6">
      <!-- Current Price -->
      <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-500/20 to-transparent border border-purple-500/30">
        <div class="text-sm text-gray-400 mb-1">Current Price</div>
        <div class="text-4xl font-bold text-white">{{ formatPrice(currentPrice) }}</div>
        <div class="text-sm text-gray-500 mt-1">{{ symbol }}</div>
      </div>

      <!-- Price Targets -->
      <div class="grid grid-cols-3 gap-4">
        <!-- Near Term -->
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-green-500/10 to-transparent border border-green-500/30">
          <div class="text-xs text-gray-400 mb-2">Near Term (1-3 months)</div>
          <div class="text-3xl font-bold text-green-400 mb-1">
            {{ formatPrice(targets.nearTerm) }}
          </div>
          <div class="text-sm text-gray-300">
            {{ calculateChange(currentPrice, targets.nearTerm) }}
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Confidence: {{ targets.nearTermConfidence }}%
          </div>
        </div>

        <!-- Medium Term -->
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-blue-500/10 to-transparent border border-blue-500/30">
          <div class="text-xs text-gray-400 mb-2">Medium Term (3-6 months)</div>
          <div class="text-3xl font-bold text-blue-400 mb-1">
            {{ formatPrice(targets.mediumTerm) }}
          </div>
          <div class="text-sm text-gray-300">
            {{ calculateChange(currentPrice, targets.mediumTerm) }}
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Confidence: {{ targets.mediumTermConfidence }}%
          </div>
        </div>

        <!-- Long Term -->
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-500/10 to-transparent border border-purple-500/30">
          <div class="text-xs text-gray-400 mb-2">Long Term (6-12 months)</div>
          <div class="text-3xl font-bold text-purple-400 mb-1">
            {{ formatPrice(targets.longTerm) }}
          </div>
          <div class="text-sm text-gray-300">
            {{ calculateChange(currentPrice, targets.longTerm) }}
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Confidence: {{ targets.longTermConfidence }}%
          </div>
        </div>
      </div>

      <!-- Support & Resistance Levels -->
      <div class="grid md:grid-cols-2 gap-4">
        <div class="p-4 rounded-lg bg-white/5">
          <h4 class="text-sm font-semibold text-green-400 mb-3">Support Levels</h4>
          <div class="space-y-2">
            <div v-for="(level, index) in supportLevels" :key="index" 
                 class="flex items-center justify-between text-sm">
              <span class="text-gray-400">S{{ index + 1 }}</span>
              <span class="font-semibold text-white">{{ formatPrice(level) }}</span>
              <span :class="level < currentPrice ? 'text-green-400' : 'text-gray-500'">
                {{ calculateChange(currentPrice, level) }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-4 rounded-lg bg-white/5">
          <h4 class="text-sm font-semibold text-red-400 mb-3">Resistance Levels</h4>
          <div class="space-y-2">
            <div v-for="(level, index) in resistanceLevels" :key="index" 
                 class="flex items-center justify-between text-sm">
              <span class="text-gray-400">R{{ index + 1 }}</span>
              <span class="font-semibold text-white">{{ formatPrice(level) }}</span>
              <span :class="level > currentPrice ? 'text-red-400' : 'text-gray-500'">
                {{ calculateChange(currentPrice, level) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Price Range Visualization -->
      <div class="p-4 rounded-lg bg-white/5">
        <h4 class="text-sm font-semibold text-white mb-3">Expected Price Range (Next 3 Months)</h4>
        <div class="relative h-12 bg-white/10 rounded-lg overflow-hidden">
          <!-- Range bar -->
          <div 
            class="absolute h-full bg-gradient-to-r from-red-500/50 via-yellow-500/50 to-green-500/50"
            :style="{ left: getRangeLeft(), width: getRangeWidth() }"
          ></div>
          
          <!-- Current price marker -->
          <div 
            class="absolute top-0 bottom-0 w-0.5 bg-white"
            :style="{ left: getCurrentPricePosition() }"
          >
            <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs text-white whitespace-nowrap">
              Current
            </div>
          </div>

          <!-- Min/Max labels -->
          <div class="absolute top-0 bottom-0 left-2 flex items-center text-xs text-gray-400">
            {{ formatPrice(priceRange.min) }}
          </div>
          <div class="absolute top-0 bottom-0 right-2 flex items-center text-xs text-gray-400">
            {{ formatPrice(priceRange.max) }}
          </div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 mt-2">
          <span>Pessimistic</span>
          <span>Expected</span>
          <span>Optimistic</span>
        </div>
      </div>

      <!-- Key Drivers for Targets -->
      <div class="p-4 rounded-lg bg-white/5">
        <h4 class="text-sm font-semibold text-white mb-3">Key Drivers for Price Targets</h4>
        <ul class="space-y-2 text-sm text-gray-300">
          <li v-for="(driver, index) in targetDrivers" :key="index" class="flex items-start gap-2">
            <span class="text-purple-400">•</span>
            <span>{{ driver }}</span>
          </li>
        </ul>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/components/ui/Card.vue'

const props = defineProps({
  priceData: {
    type: Object,
    default: () => ({})
  }
})

const symbol = computed(() => props.priceData?.symbol || 'N/A')
const currentPrice = computed(() => props.priceData?.current_price || 0)
const targets = computed(() => props.priceData?.targets || {
  nearTerm: 0,
  nearTermConfidence: 0,
  mediumTerm: 0,
  mediumTermConfidence: 0,
  longTerm: 0,
  longTermConfidence: 0
})
const supportLevels = computed(() => props.priceData?.support_levels || [])
const resistanceLevels = computed(() => props.priceData?.resistance_levels || [])
const priceRange = computed(() => props.priceData?.price_range || { min: 0, max: 0 })
const targetDrivers = computed(() => props.priceData?.target_drivers || [])

function formatPrice(price) {
  if (!price || isNaN(price)) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

function calculateChange(from, to) {
  if (!from || !to) return '0%'
  const change = ((to - from) / from) * 100
  const sign = change >= 0 ? '+' : ''
  return `${sign}${change.toFixed(2)}%`
}

function getRangeLeft() {
  // Calculate percentage position of min price
  return '10%'
}

function getRangeWidth() {
  // Calculate width of range
  return '80%'
}

function getCurrentPricePosition() {
  // Calculate position of current price within range
  const min = priceRange.value.min || 0
  const max = priceRange.value.max || 0
  const current = currentPrice.value || 0
  
  if (max === min) return '50%'
  
  const position = ((current - min) / (max - min)) * 80 + 10
  return `${position}%`
}
</script>

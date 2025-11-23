<template>
  <div class="indicators-page">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">Technical Indicators</h1>
      <p v-if="symbol" class="text-gray-600">Symbol: {{ symbol }}</p>
      <p class="text-gray-600 mt-2">Technical indicators dashboard coming soon...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  symbol: {
    type: String,
    required: false
  }
})

onMounted(() => {
  console.log('IndicatorsPage mounted', props.symbol ? `for symbol: ${props.symbol}` : '')
})
</script>

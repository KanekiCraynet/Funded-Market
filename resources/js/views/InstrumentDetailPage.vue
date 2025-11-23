<template>
  <div class="instrument-detail-page">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">Instrument Detail: {{ symbol }}</h1>
      <p class="text-gray-600">Instrument detail view coming soon...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  symbol: {
    type: String,
    required: true
  }
})

onMounted(() => {
  console.log('InstrumentDetailPage mounted for symbol:', props.symbol)
})
</script>

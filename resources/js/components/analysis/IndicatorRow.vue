<template>
  <div class="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition-colors">
    <div class="flex items-center gap-3 flex-1">
      <div class="text-sm font-medium text-white">{{ name }}</div>
    </div>
    
    <div class="flex items-center gap-3">
      <div class="text-sm font-mono text-gray-300">
        {{ formatValue(value) }}
      </div>
      
      <Badge :variant="signalVariant" size="sm">
        {{ signal.signal }}
      </Badge>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Badge from '@/components/ui/Badge.vue'

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  value: {
    type: [Number, Object],
    required: true
  },
  signal: {
    type: Object,
    default: () => ({ signal: 'NEUTRAL', color: 'gray' })
  }
})

const signalVariant = computed(() => {
  const colorMap = {
    'green': 'success',
    'red': 'danger',
    'yellow': 'warning',
    'blue': 'info',
    'gray': 'default'
  }
  return colorMap[props.signal.color] || 'default'
})

function formatValue(value) {
  if (value == null) return 'N/A'
  if (typeof value === 'object') {
    // Handle objects like MACD
    if (value.value !== undefined) return value.value.toFixed(2)
    return 'N/A'
  }
  if (typeof value === 'number') {
    return value.toFixed(2)
  }
  return String(value)
}
</script>

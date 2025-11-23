<template>
  <div class="price-tile" :class="tileClasses" @click="handleClick">
    <div class="tile-header">
      <span class="symbol">{{ symbol }}</span>
      <Badge :variant="changeBadgeVariant" size="sm">
        {{ changePercentage }}
      </Badge>
    </div>
    
    <div class="tile-body">
      <div class="price">{{ formattedPrice }}</div>
      <div class="change" :class="changeClass">
        {{ changeSign }}{{ formattedChange }}
      </div>
    </div>
    
    <div class="tile-footer">
      <div class="volume">
        <span class="label">Vol:</span>
        <span class="value">{{ formattedVolume }}</span>
      </div>
      <div class="high-low">
        <span class="label">H/L:</span>
        <span class="value">{{ formattedHighLow }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Badge from '../atoms/Badge.vue'

const props = defineProps({
  symbol: {
    type: String,
    required: true
  },
  price: {
    type: Number,
    default: 0
  },
  change: {
    type: Number,
    default: 0
  },
  changePercent: {
    type: Number,
    default: 0
  },
  volume: {
    type: Number,
    default: 0
  },
  high: {
    type: Number,
    default: 0
  },
  low: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['click'])

const tileClasses = computed(() => ({
  'tile-positive': props.change > 0,
  'tile-negative': props.change < 0,
  'tile-neutral': props.change === 0
}))

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8
  }).format(props.price)
})

const formattedChange = computed(() => {
  return Math.abs(props.change).toFixed(2)
})

const changePercentage = computed(() => {
  const sign = props.changePercent >= 0 ? '+' : ''
  return `${sign}${props.changePercent.toFixed(2)}%`
})

const changeSign = computed(() => props.change >= 0 ? '+' : '-')

const changeClass = computed(() => ({
  'change-positive': props.change > 0,
  'change-negative': props.change < 0,
  'change-neutral': props.change === 0
}))

const changeBadgeVariant = computed(() => {
  if (props.change > 0) return 'success'
  if (props.change < 0) return 'danger'
  return 'default'
})

const formattedVolume = computed(() => {
  if (props.volume >= 1_000_000_000) {
    return (props.volume / 1_000_000_000).toFixed(2) + 'B'
  }
  if (props.volume >= 1_000_000) {
    return (props.volume / 1_000_000).toFixed(2) + 'M'
  }
  if (props.volume >= 1_000) {
    return (props.volume / 1_000).toFixed(2) + 'K'
  }
  return props.volume.toFixed(0)
})

const formattedHighLow = computed(() => {
  return `${props.high.toFixed(2)}/${props.low.toFixed(2)}`
})

function handleClick() {
  emit('click', props.symbol)
}
</script>

<style scoped>
.price-tile {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.price-tile:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  border-color: var(--color-primary);
}

.tile-positive {
  border-left: 3px solid var(--color-success);
}

.tile-negative {
  border-left: 3px solid var(--color-danger);
}

.tile-neutral {
  border-left: 3px solid var(--color-text-muted);
}

.tile-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.symbol {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text);
}

.tile-body {
  margin-bottom: 12px;
}

.price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 4px;
}

.change {
  font-size: 0.875rem;
  font-weight: 600;
}

.change-positive {
  color: var(--color-success);
}

.change-negative {
  color: var(--color-danger);
}

.change-neutral {
  color: var(--color-text-muted);
}

.tile-footer {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.label {
  margin-right: 4px;
}

.value {
  color: var(--color-text);
  font-weight: 500;
}
</style>

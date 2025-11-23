<template>
  <div class="indicator-card" :class="cardClasses">
    <div class="card-header">
      <span class="indicator-name">{{ name }}</span>
      <Badge :variant="badgeVariant" size="sm">{{ formattedValue }}</Badge>
    </div>
    
    <div class="card-body">
      <div class="progress-bar">
        <div class="progress-fill" :style="progressStyle"></div>
      </div>
      
      <div class="card-footer">
        <span class="signal" :class="signalClass">{{ signal }}</span>
        <span class="normalized-score">{{ normalizedScore }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Badge from '../atoms/Badge.vue'

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  value: {
    type: Number,
    required: true
  },
  min: {
    type: Number,
    default: 0
  },
  max: {
    type: Number,
    default: 100
  },
  signal: {
    type: String,
    default: 'NEUTRAL',
    validator: (value) => ['BUY', 'SELL', 'NEUTRAL', 'STRONG_BUY', 'STRONG_SELL'].includes(value)
  }
})

const cardClasses = computed(() => ({
  'card-bullish': props.signal.includes('BUY'),
  'card-bearish': props.signal.includes('SELL'),
  'card-neutral': props.signal === 'NEUTRAL'
}))

const formattedValue = computed(() => {
  return props.value.toFixed(2)
})

const normalizedScore = computed(() => {
  // Normalize to [-1, 1] or [0, 1] range depending on min/max
  const range = props.max - props.min
  const normalized = ((props.value - props.min) / range) * 2 - 1
  return normalized.toFixed(2)
})

const progressStyle = computed(() => {
  const percentage = ((props.value - props.min) / (props.max - props.min)) * 100
  const clampedPercentage = Math.max(0, Math.min(100, percentage))
  
  let color = '#a0aec0' // neutral
  if (props.signal.includes('BUY')) {
    color = '#48bb78' // green
  } else if (props.signal.includes('SELL')) {
    color = '#f56565' // red
  }
  
  return {
    width: `${clampedPercentage}%`,
    backgroundColor: color
  }
})

const badgeVariant = computed(() => {
  if (props.signal.includes('BUY')) return 'success'
  if (props.signal.includes('SELL')) return 'danger'
  return 'default'
})

const signalClass = computed(() => ({
  'signal-buy': props.signal.includes('BUY'),
  'signal-sell': props.signal.includes('SELL'),
  'signal-neutral': props.signal === 'NEUTRAL'
}))
</script>

<style scoped>
.indicator-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 16px;
  transition: all 200ms ease;
}

.indicator-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-bullish {
  border-left: 3px solid var(--color-success);
}

.card-bearish {
  border-left: 3px solid var(--color-danger);
}

.card-neutral {
  border-left: 3px solid var(--color-text-muted);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.indicator-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: var(--color-surface-elevated);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 300ms ease, background-color 300ms ease;
  border-radius: 4px;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.signal {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.signal-buy {
  color: var(--color-success);
}

.signal-sell {
  color: var(--color-danger);
}

.signal-neutral {
  color: var(--color-text-muted);
}

.normalized-score {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-muted);
  font-family: monospace;
}
</style>

<template>
  <div class="regime-pill" :style="pillStyle">
    <div class="regime-icon">{{ regimeIcon }}</div>
    <div class="regime-content">
      <div class="regime-label">{{ regime.label || 'Unknown' }}</div>
      <div class="regime-strength">{{ regime.strength_label || 'N/A' }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  regime: {
    type: Object,
    required: true,
    default: () => ({
      regime: 'neutral',
      label: 'Neutral',
      strength: 0.5,
      strength_label: 'Moderate',
      color: '#a0aec0'
    })
  }
})

const pillStyle = computed(() => ({
  '--regime-color': props.regime.color || '#a0aec0'
}))

const regimeIcon = computed(() => {
  switch (props.regime.regime) {
    case 'bull':
      return '📈'
    case 'bear':
      return '📉'
    case 'consolidation':
      return '➡️'
    default:
      return '➖'
  }
})
</script>

<style scoped>
.regime-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--color-surface-elevated);
  border: 2px solid var(--regime-color);
  border-radius: 24px;
  font-size: 0.875rem;
}

.regime-icon {
  font-size: 1.25rem;
}

.regime-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.regime-label {
  font-weight: 700;
  color: var(--regime-color);
  line-height: 1;
}

.regime-strength {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  line-height: 1;
}
</style>

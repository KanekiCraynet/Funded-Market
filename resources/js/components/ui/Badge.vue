<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => [
      'default', 'primary', 'success', 'danger', 'warning', 'info',
      'buy', 'sell', 'hold',
      'risk-low', 'risk-medium', 'risk-high',
      'bullish', 'bearish', 'neutral'
    ].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  dot: {
    type: Boolean,
    default: false
  },
  pill: {
    type: Boolean,
    default: true
  }
})

const badgeClasses = computed(() => {
  const classes = [
    'badge',
    `badge-${props.variant}`,
    `badge-${props.size}`
  ]
  
  if (props.pill) {
    classes.push('badge-pill')
  }
  
  if (props.dot) {
    classes.push('badge-dot')
  }
  
  return classes.join(' ')
})
</script>

<style scoped>
.badge {
  @apply inline-flex items-center gap-1.5 font-semibold border;
}

/* Sizes */
.badge-sm {
  @apply px-2 py-0.5 text-xs;
}

.badge-md {
  @apply px-3 py-1 text-xs;
}

.badge-lg {
  @apply px-4 py-1.5 text-sm;
}

/* Pill shape */
.badge-pill {
  @apply rounded-full;
}

.badge:not(.badge-pill) {
  @apply rounded-md;
}

/* Variants */
.badge-default {
  @apply bg-gray-500/20 text-gray-300 border-gray-500/30;
}

.badge-primary {
  @apply bg-purple-500/20 text-purple-400 border-purple-500/30;
}

.badge-success {
  @apply bg-green-500/20 text-green-400 border-green-500/30;
}

.badge-danger {
  @apply bg-red-500/20 text-red-400 border-red-500/30;
}

.badge-warning {
  @apply bg-yellow-500/20 text-yellow-400 border-yellow-500/30;
}

.badge-info {
  @apply bg-blue-500/20 text-blue-400 border-blue-500/30;
}

/* Recommendation badges */
.badge-buy {
  @apply bg-green-500/20 text-green-400 border-green-500/30;
}

.badge-sell {
  @apply bg-red-500/20 text-red-400 border-red-500/30;
}

.badge-hold {
  @apply bg-yellow-500/20 text-yellow-400 border-yellow-500/30;
}

/* Risk level badges */
.badge-risk-low {
  @apply bg-green-500/20 text-green-400 border-green-500/30;
}

.badge-risk-medium {
  @apply bg-yellow-500/20 text-yellow-400 border-yellow-500/30;
}

.badge-risk-high {
  @apply bg-red-500/20 text-red-400 border-red-500/30;
}

/* Signal badges */
.badge-bullish {
  @apply bg-green-500/20 text-green-400 border-green-500/30;
}

.badge-bearish {
  @apply bg-red-500/20 text-red-400 border-red-500/30;
}

.badge-neutral {
  @apply bg-yellow-500/20 text-yellow-400 border-yellow-500/30;
}

/* Dot indicator */
.badge-dot::before {
  content: '';
  @apply inline-block w-2 h-2 rounded-full;
}

.badge-buy.badge-dot::before,
.badge-bullish.badge-dot::before,
.badge-risk-low.badge-dot::before,
.badge-success.badge-dot::before {
  @apply bg-green-400;
}

.badge-sell.badge-dot::before,
.badge-bearish.badge-dot::before,
.badge-risk-high.badge-dot::before,
.badge-danger.badge-dot::before {
  @apply bg-red-400;
}

.badge-hold.badge-dot::before,
.badge-neutral.badge-dot::before,
.badge-risk-medium.badge-dot::before,
.badge-warning.badge-dot::before {
  @apply bg-yellow-400;
}
</style>

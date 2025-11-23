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
    validator: (value) => ['default', 'success', 'danger', 'warning', 'info'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

const badgeClasses = computed(() => [
  'badge',
  `badge-${props.variant}`,
  `badge-${props.size}`
])
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  border-radius: 6px;
  white-space: nowrap;
}

/* Sizes */
.badge-sm {
  padding: 2px 8px;
  font-size: 0.75rem;
}

.badge-md {
  padding: 4px 12px;
  font-size: 0.875rem;
}

.badge-lg {
  padding: 6px 16px;
  font-size: 1rem;
}

/* Variants */
.badge-default {
  background: var(--color-surface-elevated);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.badge-success {
  background: var(--color-success-light);
  color: var(--color-success);
}

.badge-danger {
  background: var(--color-danger-light);
  color: var(--color-danger);
}

.badge-warning {
  background: var(--color-warning-light);
  color: var(--color-warning);
}

.badge-info {
  background: var(--color-info-light);
  color: var(--color-info);
}
</style>

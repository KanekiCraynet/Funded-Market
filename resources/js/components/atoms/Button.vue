<template>
  <button
    :class="buttonClasses"
    :disabled="disabled || loading"
    :type="type"
    @click="handleClick"
  >
    <span v-if="loading" class="loading-spinner"></span>
    <slot v-else />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'ghost'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  disabled: Boolean,
  loading: Boolean,
  type: {
    type: String,
    default: 'button'
  },
  fullWidth: Boolean,
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => [
  'btn',
  `btn-${props.variant}`,
  `btn-${props.size}`,
  {
    'btn-disabled': props.disabled || props.loading,
    'btn-loading': props.loading,
    'btn-full-width': props.fullWidth,
  }
])

function handleClick(event) {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  white-space: nowrap;
}

/* Sizes */
.btn-sm {
  padding: 8px 16px;
  font-size: 0.875rem;
  border-radius: 8px;
}

.btn-md {
  padding: 12px 24px;
  font-size: 1rem;
  border-radius: 8px;
}

.btn-lg {
  padding: 16px 32px;
  font-size: 1.125rem;
  border-radius: 12px;
}

/* Variants */
.btn-primary {
  background: linear-gradient(135deg, #0d6efd, #00b4d8);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 180, 216, 0.3);
}

.btn-primary:hover:not(.btn-disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 180, 216, 0.4);
}

.btn-primary:active:not(.btn-disabled) {
  transform: scale(0.95);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover:not(.btn-disabled) {
  background: #5a6268;
}

.btn-success {
  background: #198754;
  color: white;
}

.btn-success:hover:not(.btn-disabled) {
  background: #157347;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover:not(.btn-disabled) {
  background: #bb2d3b;
}

.btn-ghost {
  background: transparent;
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.btn-ghost:hover:not(.btn-disabled) {
  background: var(--color-surface-elevated);
}

/* States */
.btn-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-full-width {
  width: 100%;
}

.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

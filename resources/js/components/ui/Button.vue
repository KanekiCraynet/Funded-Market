<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="handleClick"
  >
    <span v-if="loading" class="button-spinner"></span>
    <slot v-else />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'success', 'ghost', 'link'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  fullWidth: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
  const classes = [
    'button',
    `button-${props.variant}`,
    `button-${props.size}`,
  ]
  
  if (props.fullWidth) {
    classes.push('button-full-width')
  }
  
  if (props.disabled || props.loading) {
    classes.push('button-disabled')
  }
  
  return classes.join(' ')
})

function handleClick(event) {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
.button {
  @apply inline-flex items-center justify-center font-semibold rounded-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Variants */
.button-primary {
  @apply bg-purple-600 hover:bg-purple-700 text-white shadow-lg hover:shadow-purple-500/50 focus:ring-purple-500;
}

.button-secondary {
  @apply bg-white/10 hover:bg-white/20 text-white border border-white/20 focus:ring-white/30;
}

.button-danger {
  @apply bg-red-500 hover:bg-red-600 text-white shadow-lg hover:shadow-red-500/50 focus:ring-red-500;
}

.button-success {
  @apply bg-green-500 hover:bg-green-600 text-white shadow-lg hover:shadow-green-500/50 focus:ring-green-500;
}

.button-ghost {
  @apply bg-transparent hover:bg-white/10 text-white;
}

.button-link {
  @apply bg-transparent hover:underline text-purple-400 hover:text-purple-300 shadow-none;
}

/* Sizes */
.button-xs {
  @apply px-3 py-1.5 text-xs;
}

.button-sm {
  @apply px-4 py-2 text-sm;
}

.button-md {
  @apply px-6 py-2.5 text-base;
}

.button-lg {
  @apply px-8 py-3 text-lg;
}

.button-xl {
  @apply px-10 py-4 text-xl;
}

/* Modifiers */
.button-full-width {
  @apply w-full;
}

.button-disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Loading spinner */
.button-spinner {
  @apply inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin;
}
</style>

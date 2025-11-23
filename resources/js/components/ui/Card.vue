<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="card-header">
      <slot name="header">
        <h3 v-if="title" class="card-title">{{ title }}</h3>
      </slot>
    </div>
    
    <div class="card-body">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'bordered', 'elevated', 'glass'].includes(value)
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },
  hoverable: {
    type: Boolean,
    default: false
  }
})

const cardClasses = computed(() => {
  const classes = [
    'card',
    `card-${props.variant}`,
    `card-padding-${props.padding}`
  ]
  
  if (props.hoverable) {
    classes.push('card-hoverable')
  }
  
  return classes.join(' ')
})
</script>

<style scoped>
.card {
  @apply rounded-xl overflow-hidden;
}

/* Variants */
.card-default {
  @apply bg-white/5 backdrop-blur-lg border border-white/10;
}

.card-bordered {
  @apply bg-white/5 border-2 border-purple-500/30;
}

.card-elevated {
  @apply bg-white/5 backdrop-blur-lg shadow-xl;
}

.card-glass {
  @apply bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-lg border border-white/10;
}

/* Padding */
.card-padding-none .card-body {
  @apply p-0;
}

.card-padding-sm .card-body {
  @apply p-3;
}

.card-padding-md .card-body {
  @apply p-6;
}

.card-padding-lg .card-body {
  @apply p-8;
}

/* Hoverable */
.card-hoverable {
  @apply transition-all duration-300 cursor-pointer;
}

.card-hoverable:hover {
  @apply bg-white/10 border-white/20 transform scale-[1.02] shadow-2xl;
}

/* Card sections */
.card-header {
  @apply px-6 py-4 border-b border-white/10;
}

.card-title {
  @apply text-xl font-semibold text-white;
}

.card-body {
  @apply text-gray-200;
}

.card-footer {
  @apply px-6 py-4 border-t border-white/10 bg-white/5;
}
</style>

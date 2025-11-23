<template>
  <div class="skeleton" :class="skeletonClasses" :style="skeletonStyles"></div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  width: {
    type: String,
    default: '100%'
  },
  height: {
    type: String,
    default: '20px'
  },
  circle: Boolean,
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'light', 'dark'].includes(value)
  }
})

const skeletonClasses = computed(() => [
  'skeleton',
  `skeleton-${props.variant}`,
  { 'skeleton-circle': props.circle }
])

const skeletonStyles = computed(() => ({
  width: props.width,
  height: props.height,
  borderRadius: props.circle ? '50%' : undefined
}))
</script>

<style scoped>
.skeleton {
  display: inline-block;
  background: linear-gradient(
    90deg,
    var(--skeleton-base) 0%,
    var(--skeleton-highlight) 50%,
    var(--skeleton-base) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 8px;
}

.skeleton-default {
  --skeleton-base: #e0e0e0;
  --skeleton-highlight: #f0f0f0;
}

.skeleton-light {
  --skeleton-base: #f5f5f5;
  --skeleton-highlight: #ffffff;
}

.skeleton-dark {
  --skeleton-base: #2a2a2a;
  --skeleton-highlight: #3a3a3a;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}
</style>

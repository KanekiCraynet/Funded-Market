<template>
  <button
    :class="buttonClasses"
    :disabled="isDisabled"
    @click="handleClick"
  >
    <span v-if="countdown > 0" class="countdown-text">
      {{ countdown }}s
    </span>
    <span v-else class="button-text">
      <slot>Generate Analysis</slot>
    </span>
  </button>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'

const props = defineProps({
  disabled: Boolean,
  initialCountdown: {
    type: Number,
    default: 0
  },
  duration: {
    type: Number,
    default: 60
  }
})

const emit = defineEmits(['click', 'countdown-complete'])

const countdown = ref(props.initialCountdown)
let intervalId = null

const isDisabled = computed(() => {
  return props.disabled || countdown.value > 0
})

const buttonClasses = computed(() => [
  'countdown-button',
  {
    'countdown-active': countdown.value > 0,
    'countdown-ready': countdown.value === 0 && !props.disabled,
    'countdown-disabled': props.disabled && countdown.value === 0
  }
])

watch(() => props.initialCountdown, (newValue) => {
  if (newValue > 0) {
    startCountdown(newValue)
  }
})

function handleClick() {
  if (!isDisabled.value) {
    emit('click')
    startCountdown(props.duration)
  }
}

function startCountdown(duration) {
  countdown.value = duration
  
  clearInterval(intervalId)
  
  intervalId = setInterval(() => {
    countdown.value--
    
    if (countdown.value <= 0) {
      clearInterval(intervalId)
      emit('countdown-complete')
    }
  }, 1000)
}

onUnmounted(() => {
  clearInterval(intervalId)
})
</script>

<style scoped>
.countdown-button {
  position: relative;
  padding: 16px 32px;
  font-size: 18px;
  font-weight: 600;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.countdown-ready {
  background: linear-gradient(135deg, #0d6efd, #00b4d8);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 180, 216, 0.3);
}

.countdown-ready:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 25px rgba(0, 180, 216, 0.5);
  animation: glow-pulse 2s ease-in-out infinite;
}

.countdown-ready:active {
  transform: scale(0.95);
}

.countdown-active {
  background: #6c757d;
  color: white;
  cursor: not-allowed;
}

.countdown-disabled {
  background: #e9ecef;
  color: #adb5bd;
  cursor: not-allowed;
}

.countdown-text {
  font-family: 'SF Mono', 'Monaco', monospace;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: 2px;
}

.button-text {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* Glow animation */
@keyframes glow-pulse {
  0%, 100% {
    box-shadow: 0 4px 12px rgba(0, 180, 216, 0.3);
  }
  50% {
    box-shadow: 0 0 25px rgba(0, 180, 216, 0.6);
  }
}

/* Shrink animation on click */
@keyframes shrink {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(0.92);
  }
  100% {
    transform: scale(1);
  }
}

.countdown-ready:active {
  animation: shrink 200ms ease-out;
}
</style>

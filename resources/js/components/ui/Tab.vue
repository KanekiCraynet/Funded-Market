<template>
  <div v-if="isActive" class="tab-panel">
    <slot />
  </div>
</template>

<script>
export default {
  name: 'Tab'
}
</script>

<script setup>
import { computed, inject } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  index: {
    type: Number,
    required: true
  }
})

const activeTab = inject('activeTab', 0)

const isActive = computed(() => {
  return activeTab.value === props.index
})
</script>

<style scoped>
.tab-panel {
  @apply animate-fadeIn;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>

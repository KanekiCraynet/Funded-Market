<template>
  <div class="tabs-container">
    <!-- Tab Headers -->
    <div class="tabs-header">
      <button
        v-for="(tab, index) in tabs"
        :key="index"
        :class="['tab-button', { 'tab-active': activeTab === index }]"
        @click="selectTab(index)"
      >
        {{ tab.title }}
      </button>
    </div>
    
    <!-- Tab Content -->
    <div class="tabs-content">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, provide, onMounted, useSlots } from 'vue'

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:modelValue'])

const slots = useSlots()
const activeTab = ref(props.modelValue)
const tabs = ref([])

// Collect tab information from child Tab components
onMounted(() => {
  const defaultSlot = slots.default?.()
  if (defaultSlot) {
    tabs.value = defaultSlot
      .filter(vnode => vnode.type?.name === 'Tab')
      .map(vnode => ({
        title: vnode.props?.title || 'Tab'
      }))
  }
})

function selectTab(index) {
  activeTab.value = index
  emit('update:modelValue', index)
}

// Provide active tab to children
provide('activeTab', activeTab)
</script>

<style scoped>
.tabs-container {
  @apply w-full;
}

.tabs-header {
  @apply flex border-b border-white/10 mb-6 overflow-x-auto;
}

.tab-button {
  @apply px-6 py-3 text-sm font-medium text-gray-400 hover:text-white transition-colors whitespace-nowrap border-b-2 border-transparent hover:border-purple-500/50;
}

.tab-active {
  @apply text-white border-purple-500;
}
</style>

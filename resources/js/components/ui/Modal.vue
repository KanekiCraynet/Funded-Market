<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="handleClose">
        <div :class="modalClasses">
          <div class="modal-header">
            <h3 class="modal-title">
              <slot name="title">{{ title }}</slot>
            </h3>
            <button
              v-if="closable"
              class="modal-close"
              @click="handleClose"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <slot />
          </div>
          
          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value)
  },
  closable: {
    type: Boolean,
    default: true
  },
  persistent: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const modalClasses = computed(() => {
  return ['modal-content', `modal-${props.size}`].join(' ')
})

function handleClose() {
  if (!props.persistent && props.closable) {
    emit('update:modelValue', false)
    emit('close')
  }
}

// Prevent body scroll when modal is open
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4;
}

.modal-content {
  @apply bg-gray-900 border border-white/10 rounded-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col;
}

/* Sizes */
.modal-sm {
  @apply w-full max-w-sm;
}

.modal-md {
  @apply w-full max-w-lg;
}

.modal-lg {
  @apply w-full max-w-2xl;
}

.modal-xl {
  @apply w-full max-w-4xl;
}

.modal-full {
  @apply w-full max-w-7xl;
}

/* Modal sections */
.modal-header {
  @apply flex items-center justify-between px-6 py-4 border-b border-white/10;
}

.modal-title {
  @apply text-xl font-semibold text-white;
}

.modal-close {
  @apply text-gray-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/10;
}

.modal-body {
  @apply flex-1 overflow-y-auto px-6 py-4 text-gray-200;
}

.modal-footer {
  @apply flex items-center justify-end gap-3 px-6 py-4 border-t border-white/10 bg-white/5;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.95);
}
</style>

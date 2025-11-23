<template>
  <div class="input-wrapper">
    <label v-if="label" :for="inputId" class="input-label">
      {{ label }}
      <span v-if="required" class="text-red-400">*</span>
    </label>
    
    <div class="input-container">
      <span v-if="$slots.prefix || icon" class="input-prefix">
        <slot name="prefix">
          <component :is="icon" v-if="icon" class="w-5 h-5" />
        </slot>
      </span>
      
      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />
      
      <span v-if="$slots.suffix" class="input-suffix">
        <slot name="suffix" />
      </span>
    </div>
    
    <p v-if="error" class="input-error">{{ error }}</p>
    <p v-else-if="hint" class="input-hint">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  icon: {
    type: Object,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

const emit = defineEmits(['update:modelValue', 'blur', 'focus'])

const inputId = ref(`input-${Math.random().toString(36).substr(2, 9)}`)
const isFocused = ref(false)

const inputClasses = computed(() => {
  const classes = [
    'input',
    `input-${props.size}`
  ]
  
  if (props.error) {
    classes.push('input-error-state')
  }
  
  if (props.disabled) {
    classes.push('input-disabled')
  }
  
  if (props.$slots.prefix || props.icon) {
    classes.push('input-with-prefix')
  }
  
  if (props.$slots.suffix) {
    classes.push('input-with-suffix')
  }
  
  return classes.join(' ')
})

function handleInput(event) {
  emit('update:modelValue', event.target.value)
}

function handleBlur(event) {
  isFocused.value = false
  emit('blur', event)
}

function handleFocus(event) {
  isFocused.value = true
  emit('focus', event)
}
</script>

<style scoped>
.input-wrapper {
  @apply w-full;
}

.input-label {
  @apply block text-sm font-medium text-gray-300 mb-2;
}

.input-container {
  @apply relative flex items-center;
}

.input {
  @apply w-full bg-white/5 border border-white/10 rounded-lg text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all;
}

/* Sizes */
.input-sm {
  @apply px-3 py-2 text-sm;
}

.input-md {
  @apply px-4 py-2.5 text-base;
}

.input-lg {
  @apply px-5 py-3 text-lg;
}

/* States */
.input-error-state {
  @apply border-red-500/50 focus:ring-red-500;
}

.input-disabled {
  @apply opacity-50 cursor-not-allowed bg-white/3;
}

/* With prefix/suffix */
.input-with-prefix {
  @apply pl-11;
}

.input-with-suffix {
  @apply pr-11;
}

.input-prefix {
  @apply absolute left-3 text-gray-400 pointer-events-none;
}

.input-suffix {
  @apply absolute right-3 text-gray-400;
}

/* Messages */
.input-error {
  @apply mt-1.5 text-sm text-red-400;
}

.input-hint {
  @apply mt-1.5 text-sm text-gray-500;
}
</style>

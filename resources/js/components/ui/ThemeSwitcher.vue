<template>
  <div class="theme-switcher">
    <button
      @click="toggleTheme"
      class="theme-button"
      :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
      <!-- Sun Icon (Light Mode) -->
      <svg
        v-if="isDark"
        class="theme-icon"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
        />
      </svg>

      <!-- Moon Icon (Dark Mode) -->
      <svg
        v-else
        class="theme-icon"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
        />
      </svg>

      <!-- Label (optional) -->
      <span v-if="showLabel" class="theme-label">
        {{ isDark ? 'Light' : 'Dark' }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'

const props = defineProps({
  showLabel: {
    type: Boolean,
    default: false,
  },
})

const { isDark, toggleTheme } = useTheme()
</script>

<style scoped>
.theme-switcher {
  @apply relative;
}

.theme-button {
  @apply flex items-center gap-2 px-3 py-2 rounded-lg;
  @apply bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white;
  @apply transition-all duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900;
}

.theme-icon {
  @apply w-5 h-5 transition-transform duration-200;
}

.theme-button:hover .theme-icon {
  @apply rotate-12 scale-110;
}

.theme-label {
  @apply text-sm font-medium;
}

/* Light mode styles */
:global(.light) .theme-button {
  @apply bg-gray-200 hover:bg-gray-300 text-gray-700 hover:text-gray-900;
}

:global(.light) .theme-button {
  @apply focus:ring-offset-white;
}
</style>

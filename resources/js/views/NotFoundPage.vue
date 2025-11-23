<template>
  <div class="not-found-page min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-gray-300">404</h1>
      <h2 class="text-3xl font-semibold text-gray-700 mt-4">Page Not Found</h2>
      <p class="text-gray-600 mt-2">The page you're looking for doesn't exist.</p>
      <router-link 
        to="/" 
        class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Go Home
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  console.log('NotFoundPage mounted')
})
</script>

<template>
  <div class="min-h-screen">
    <!-- Header -->
    <nav class="container mx-auto px-4 py-6 flex justify-between items-center">
      <div class="flex items-center gap-2">
        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
        <h1 class="text-2xl font-bold">Market Analysis</h1>
      </div>
      <div class="flex gap-4">
        <router-link to="/login" class="text-white hover:text-purple-400 transition">Login</router-link>
        <router-link to="/register" class="btn-primary">Get Started</router-link>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="container mx-auto px-4 py-16">
      <div class="max-w-4xl mx-auto text-center space-y-8">
        <h2 class="text-5xl md:text-7xl font-bold leading-tight">
          AI-Powered Market
          <span class="text-purple-400"> Intelligence</span>
        </h2>
        
        <p class="text-xl text-gray-300 max-w-2xl mx-auto">
          Enterprise-grade market analysis combining quantitative indicators, 
          sentiment analysis, and AI insights for smarter investment decisions.
        </p>

        <div class="flex gap-4 justify-center pt-8">
          <router-link to="/register" class="btn-primary text-lg px-8 py-3">
            Start Free Trial
            <span class="ml-2">→</span>
          </router-link>
          <router-link to="/login" class="btn-secondary text-lg px-8 py-3">
            View Demo
          </router-link>
        </div>
      </div>

      <!-- Features Grid -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mt-24">
        <div class="card hover:bg-white/10 transition-all">
          <div class="text-purple-400 mb-4">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">40+ Technical Indicators</h3>
          <p class="text-gray-400 text-sm">RSI, MACD, Bollinger Bands, ADX, and more quantitative metrics</p>
        </div>

        <div class="card hover:bg-white/10 transition-all">
          <div class="text-purple-400 mb-4">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">AI-Enhanced Analysis</h3>
          <p class="text-gray-400 text-sm">Gemini LLM integration for explainable investment insights</p>
        </div>

        <div class="card hover:bg-white/10 transition-all">
          <div class="text-purple-400 mb-4">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">Multi-Asset Coverage</h3>
          <p class="text-gray-400 text-sm">Crypto, Forex, Stocks, and Commodities in one platform</p>
        </div>

        <div class="card hover:bg-white/10 transition-all">
          <div class="text-purple-400 mb-4">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">Risk Management</h3>
          <p class="text-gray-400 text-sm">Dynamic position sizing and comprehensive risk assessment</p>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="grid md:grid-cols-3 gap-8 mt-24 text-center">
        <div>
          <div class="text-5xl font-bold text-purple-400 mb-2">40+</div>
          <div class="text-gray-300">Technical Indicators</div>
        </div>
        <div>
          <div class="text-5xl font-bold text-purple-400 mb-2">99.9%</div>
          <div class="text-gray-300">Uptime SLA</div>
        </div>
        <div>
          <div class="text-5xl font-bold text-purple-400 mb-2">&lt; 2s</div>
          <div class="text-gray-300">Analysis Speed</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(() => {
  // Redirect to dashboard if already logged in
  if (authStore.isAuthenticated) {
    router.push('/dashboard')
  }
})
</script>

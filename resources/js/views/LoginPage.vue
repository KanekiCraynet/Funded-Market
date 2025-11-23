<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card shadow-2xl">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
          <div class="flex items-center gap-2">
            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <h1 class="text-2xl font-bold">Market Analysis</h1>
          </div>
        </div>

        <h2 class="text-3xl font-bold text-center mb-2">Welcome Back</h2>
        <p class="text-gray-400 text-center mb-8">Sign in to access your dashboard</p>

        <!-- Error Message -->
        <div v-if="errorMessage" class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-6">
          {{ errorMessage }}
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label class="block text-sm font-medium mb-2">Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="you@example.com"
              autocomplete="username"
              required
              class="input"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Password</label>
            <input
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              autocomplete="current-password"
              required
              class="input"
            />
          </div>

          <button
            type="submit"
            :disabled="authStore.isLoading"
            class="btn-primary w-full"
          >
            {{ authStore.isLoading ? 'Signing in...' : 'Sign In' }}
          </button>
        </form>

        <!-- Register Link -->
        <div class="mt-6 text-center">
          <p class="text-gray-400">
            Don't have an account?
            <router-link to="/register" class="text-purple-400 hover:text-purple-300">
              Sign up
            </router-link>
          </p>
        </div>

        <!-- Back Link -->
        <div class="mt-6 text-center">
          <router-link to="/" class="text-gray-400 hover:text-gray-300 text-sm">
            ← Back to home
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: ''
})

const errorMessage = ref('')

async function handleLogin() {
  errorMessage.value = ''
  try {
    await authStore.login(form.value.email, form.value.password)
    router.push('/dashboard')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Login failed. Please try again.'
  }
}
</script>

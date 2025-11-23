<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card shadow-2xl">
        <div class="flex justify-center mb-8">
          <div class="flex items-center gap-2">
            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <h1 class="text-2xl font-bold">Market Analysis</h1>
          </div>
        </div>

        <h2 class="text-3xl font-bold text-center mb-2">Create Account</h2>
        <p class="text-gray-400 text-center mb-8">Start your investment journey</p>

        <div v-if="errorMessage" class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-6">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleRegister" class="space-y-6">
          <div>
            <label class="block text-sm font-medium mb-2">Name</label>
            <input v-model="form.name" type="text" placeholder="John Doe" required class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Email</label>
            <input v-model="form.email" type="email" placeholder="you@example.com" autocomplete="username" required class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Password</label>
            <input v-model="form.password" type="password" placeholder="••••••••" autocomplete="new-password" required minlength="8" class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Confirm Password</label>
            <input v-model="form.password_confirmation" type="password" placeholder="••••••••" autocomplete="new-password" required minlength="8" class="input" />
          </div>

          <button type="submit" :disabled="authStore.isLoading" class="btn-primary w-full">
            {{ authStore.isLoading ? 'Creating account...' : 'Create Account' }}
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-gray-400">
            Already have an account?
            <router-link to="/login" class="text-purple-400 hover:text-purple-300">Sign in</router-link>
          </p>
        </div>

        <div class="mt-6 text-center">
          <router-link to="/" class="text-gray-400 hover:text-gray-300 text-sm">← Back to home</router-link>
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
const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errorMessage = ref('')

async function handleRegister() {
  errorMessage.value = ''
  
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Passwords do not match'
    return
  }

  try {
    await authStore.register(form.value.name, form.value.email, form.value.password, form.value.password_confirmation)
    router.push('/dashboard')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Registration failed'
  }
}
</script>

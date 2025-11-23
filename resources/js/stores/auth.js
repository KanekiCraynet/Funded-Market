import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const isLoading = ref(false)
  const error = ref(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const userName = computed(() => user.value?.name || 'Guest')
  const userEmail = computed(() => user.value?.email || '')

  // Actions
  async function login(email, password) {
    isLoading.value = true
    error.value = null

    try {
      const response = await axios.post('/api/v1/auth/login', {
        email,
        password,
      })

      const { data } = response.data

      token.value = data.token
      user.value = data.user

      localStorage.setItem('auth_token', data.token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`

      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function register(name, email, password, passwordConfirmation) {
    isLoading.value = true
    error.value = null

    try {
      const response = await axios.post('/api/v1/auth/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      const { data } = response.data

      token.value = data.token
      user.value = data.user

      localStorage.setItem('auth_token', data.token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`

      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    isLoading.value = true
    error.value = null

    try {
      await axios.post('/api/v1/auth/logout')
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      delete axios.defaults.headers.common['Authorization']
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return

    isLoading.value = true
    error.value = null

    try {
      const response = await axios.get('/api/v1/auth/user')
      user.value = response.data.data
    } catch (err) {
      console.error('Fetch user error:', err)
      // Token might be invalid, logout
      await logout()
    } finally {
      isLoading.value = false
    }
  }

  function initialize() {
    if (token.value) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      fetchUser()
    }
  }

  // Check authentication status (for router guard)
  const checkPerformed = ref(false)
  
  async function checkAuth() {
    if (checkPerformed.value) return
    
    checkPerformed.value = true
    
    if (token.value) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      await fetchUser()
    }
  }

  return {
    // State
    user,
    token,
    isLoading,
    error,
    checkPerformed,

    // Getters
    isAuthenticated,
    userName,
    userEmail,

    // Actions
    login,
    register,
    logout,
    fetchUser,
    initialize,
    checkAuth,
  }
})

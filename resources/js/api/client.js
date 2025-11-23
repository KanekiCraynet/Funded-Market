import axios from 'axios'

const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  withCredentials: true
})

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor - handle Laravel response format
apiClient.interceptors.response.use(
  (response) => {
    // Backend returns { success: true/false, data: {...} }
    // Keep original format for compatibility
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      window.location.href = '/login'
    }
    
    // Format error message from Laravel validation or response
    let errorMessage = 'An error occurred'
    
    if (error.response?.data?.errors) {
      // Laravel validation errors
      errorMessage = Object.values(error.response.data.errors).flat().join(', ')
    } else if (error.response?.data?.message) {
      // Laravel response message
      errorMessage = error.response.data.message
    } else if (error.message) {
      // Network or other errors
      errorMessage = error.message
    }
    
    // Ensure error has user-friendly message
    error.userMessage = errorMessage
    
    return Promise.reject(error)
  }
)

// API methods sesuai backend Laravel
export const authAPI = {
  /**
   * Register new user
   * @param {Object} data - { name, email, password, password_confirmation }
   * @returns {Promise} - { user, token, token_type }
   */
  register: (data) => apiClient.post('/auth/register', data),
  
  /**
   * Login user
   * @param {Object} data - { email, password }
   * @returns {Promise} - { user, token, token_type }
   */
  login: (data) => apiClient.post('/auth/login', data),
  
  /**
   * Logout current user
   */
  logout: () => apiClient.post('/auth/logout'),
  
  /**
   * Get current authenticated user
   * @returns {Promise} - { user, recent_analyses, subscription }
   */
  getUser: () => apiClient.get('/auth/user'),
  
  /**
   * Update user profile
   * @param {Object} data - { name?, phone?, preferences? }
   */
  updateProfile: (data) => apiClient.put('/auth/profile', data),
  
  /**
   * Refresh auth token
   */
  refreshToken: () => apiClient.post('/auth/refresh')
}

export const marketAPI = {
  /**
   * Get market overview
   * @returns {Promise} - { trending, top_gainers, top_losers, market_summary }
   */
  getOverview: () => apiClient.get('/market/overview'),
  
  /**
   * Get all instruments with filters
   * @param {Object} params - { type?, exchange?, search?, sector?, sort_by?, per_page? }
   * @returns {Promise} - { data: [...], pagination: {...}, filters: {...} }
   */
  getInstruments: (params) => apiClient.get('/market/instruments', { params }),
  
  /**
   * Get instrument by symbol
   * @param {string} symbol
   * @returns {Promise} - { instrument, real_time_data, historical_data, market_stats }
   */
  getInstrument: (symbol) => apiClient.get(`/market/${symbol}`),
  
  /**
   * Get real-time tickers
   * @returns {Promise} - Array of instruments
   */
  getTickers: () => apiClient.get('/market/tickers'),
  
  /**
   * Get sectors performance
   * @returns {Promise} - Array of sectors with stats
   */
  getSectors: () => apiClient.get('/market/sectors'),
  
  /**
   * Get user watchlist
   */
  getWatchlist: () => apiClient.get('/market/watchlist')
}

export const analysisAPI = {
  /**
   * Generate new analysis
   * @param {Object} data - { symbol, time_horizon? }
   * @returns {Promise} - Analysis object with full computational results
   */
  generate: (data) => apiClient.post('/analysis/generate', data),
  
  /**
   * Get analysis history
   * @param {Object} params - { symbol?, recommendation?, date_from?, date_to?, per_page? }
   * @returns {Promise} - { data: [...], pagination: {...} }
   */
  getHistory: (params) => apiClient.get('/analysis/history', { params }),
  
  /**
   * Get single analysis by ID
   * @param {string} id
   * @returns {Promise} - Full analysis object
   */
  getById: (id) => apiClient.get(`/analysis/${id}`),
  
  /**
   * Delete analysis
   * @param {string} id
   */
  delete: (id) => apiClient.delete(`/analysis/${id}`),
  
  /**
   * Get user analysis statistics
   * @returns {Promise} - { overall, recommendation_distribution, risk_level_distribution, top_symbols, recent_performance }
   */
  getStats: () => apiClient.get('/analysis/stats'),
  
  /**
   * Export analyses to CSV
   * @param {Object} params - { symbol?, recommendation?, date_from?, date_to?, limit? }
   */
  export: (params) => apiClient.get('/analysis/export', { params })
}

export const quantAPI = {
  /**
   * Get technical indicators for symbol
   * @param {string} symbol
   * @returns {Promise} - All 40+ technical indicators
   */
  getIndicators: (symbol) => apiClient.get(`/quant/indicators/${symbol}`),
  
  /**
   * Get trend analysis
   * @param {string} symbol
   */
  getTrends: (symbol) => apiClient.get(`/quant/trends/${symbol}`),
  
  /**
   * Get volatility metrics
   * @param {string} symbol
   */
  getVolatility: (symbol) => apiClient.get(`/quant/volatility/${symbol}`)
}

export const sentimentAPI = {
  /**
   * Get sentiment analysis for symbol
   * @param {string} symbol
   */
  getSentiment: (symbol) => apiClient.get(`/sentiment/${symbol}`),
  
  /**
   * Get news sentiment
   * @param {string} symbol
   */
  getNews: (symbol) => apiClient.get(`/sentiment/news/${symbol}`)
}

// Health check
export const healthCheck = () => axios.get('http://127.0.0.1:8000/api/health')

export default apiClient

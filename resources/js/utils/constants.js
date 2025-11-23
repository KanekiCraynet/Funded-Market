/**
 * Application Constants
 * 
 * Centralized constants for the entire application
 */

// API Configuration
export const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
export const API_VERSION = 'v1'
export const API_TIMEOUT = 30000 // 30 seconds

// Rate Limiting
export const RATE_LIMIT_DURATION = 60 // seconds
export const RATE_LIMIT_MESSAGE = 'Please wait before generating another analysis'

// Polling Intervals
export const MARKET_DATA_POLL_INTERVAL = 30000 // 30 seconds
export const QUEUE_STATUS_POLL_INTERVAL = 5000 // 5 seconds

// Pagination
export const DEFAULT_PAGE_SIZE = 15
export const MAX_PAGE_SIZE = 50
export const PAGE_SIZE_OPTIONS = [10, 15, 25, 50]

// Chart Configuration
export const CHART_COLORS = {
  bullish: '#48bb78',
  bearish: '#f56565',
  neutral: '#a0aec0',
  primary: '#0d6efd',
  secondary: '#6c757d'
}

export const CHART_TIMEFRAMES = [
  { value: '1m', label: '1 Minute' },
  { value: '5m', label: '5 Minutes' },
  { value: '15m', label: '15 Minutes' },
  { value: '1h', label: '1 Hour' },
  { value: '4h', label: '4 Hours' },
  { value: '1d', label: '1 Day' },
  { value: '1w', label: '1 Week' }
]

// Market Symbols
export const DEFAULT_SYMBOLS = [
  'BTCUSDT',
  'ETHUSDT',
  'BNBUSDT',
  'SOLUSDT',
  'XRPUSDT',
  'ADAUSDT',
  'DOGEUSDT',
  'MATICUSDT'
]

export const FOREX_PAIRS = [
  'EURUSD',
  'GBPUSD',
  'USDJPY',
  'AUDUSD',
  'USDCAD',
  'USDCHF'
]

export const STOCK_SYMBOLS = [
  'AAPL',
  'GOOGL',
  'MSFT',
  'AMZN',
  'TSLA',
  'META',
  'NVDA',
  'AMD'
]

// Recommendations
export const RECOMMENDATION_TYPES = {
  BUY: 'BUY',
  SELL: 'SELL',
  HOLD: 'HOLD',
  STRONG_BUY: 'STRONG_BUY',
  STRONG_SELL: 'STRONG_SELL'
}

export const RECOMMENDATION_COLORS = {
  BUY: 'success',
  SELL: 'danger',
  HOLD: 'default',
  STRONG_BUY: 'success',
  STRONG_SELL: 'danger'
}

// Risk Levels
export const RISK_LEVELS = {
  LOW: 'LOW',
  MEDIUM: 'MEDIUM',
  HIGH: 'HIGH'
}

export const RISK_LEVEL_COLORS = {
  LOW: 'success',
  MEDIUM: 'warning',
  HIGH: 'danger'
}

// Time Horizons
export const TIME_HORIZONS = {
  SHORT_TERM: 'short_term',
  MEDIUM_TERM: 'medium_term',
  LONG_TERM: 'long_term'
}

// Volatility Regimes
export const VOLATILITY_REGIMES = {
  ULTRA_LOW: 'ultra_low',
  LOW: 'low',
  MEDIUM: 'medium',
  HIGH: 'high',
  EXTREME: 'extreme'
}

// Market Regimes
export const MARKET_REGIMES = {
  BULL: 'bull',
  BEAR: 'bear',
  NEUTRAL: 'neutral',
  CONSOLIDATION: 'consolidation'
}

export const MARKET_REGIME_COLORS = {
  bull: '#48bb78',
  bear: '#f56565',
  neutral: '#a0aec0',
  consolidation: '#ed8936'
}

// Indicator Signals
export const INDICATOR_SIGNALS = {
  STRONG_BUY: 'STRONG_BUY',
  BUY: 'BUY',
  NEUTRAL: 'NEUTRAL',
  SELL: 'SELL',
  STRONG_SELL: 'STRONG_SELL'
}

// Storage Keys
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  USER_PREFERENCES: 'user_preferences',
  THEME_MODE: 'theme_mode',
  WATCHLIST: 'watchlist',
  LAST_SYMBOL: 'last_selected_symbol'
}

// Theme
export const THEME_MODES = {
  LIGHT: 'light',
  DARK: 'dark',
  AUTO: 'auto'
}

// Error Messages
export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Network error. Please check your connection.',
  UNAUTHORIZED: 'Your session has expired. Please log in again.',
  RATE_LIMITED: 'Too many requests. Please wait before trying again.',
  SERVER_ERROR: 'Server error. Please try again later.',
  VALIDATION_ERROR: 'Please check your input and try again.',
  NOT_FOUND: 'The requested resource was not found.',
  UNKNOWN_ERROR: 'An unexpected error occurred.'
}

// Success Messages
export const SUCCESS_MESSAGES = {
  ANALYSIS_GENERATED: 'Analysis generated successfully!',
  ANALYSIS_DELETED: 'Analysis deleted successfully.',
  SETTINGS_SAVED: 'Settings saved successfully.',
  LOGIN_SUCCESS: 'Welcome back!',
  LOGOUT_SUCCESS: 'Logged out successfully.',
  REGISTRATION_SUCCESS: 'Account created successfully!'
}

// HTTP Status Codes
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE_ENTITY: 422,
  TOO_MANY_REQUESTS: 429,
  SERVER_ERROR: 500,
  SERVICE_UNAVAILABLE: 503
}

// Validation Rules
export const VALIDATION = {
  MIN_PASSWORD_LENGTH: 8,
  MAX_SYMBOL_LENGTH: 20,
  MIN_SYMBOL_LENGTH: 2,
  MAX_COMMENT_LENGTH: 500
}

// Animation Durations (ms)
export const ANIMATION = {
  FAST: 150,
  NORMAL: 200,
  SLOW: 300,
  SMOOTH: 500
}

// Breakpoints (px)
export const BREAKPOINTS = {
  XS: 320,
  SM: 640,
  MD: 768,
  LG: 1024,
  XL: 1280,
  XXL: 1536
}

// Feature Flags
export const FEATURES = {
  WEBSOCKET_ENABLED: false,
  SOCIAL_TRADING: false,
  PORTFOLIO_TRACKING: false,
  BACKTESTING: false,
  ALERTS: false
}

// External Links
export const EXTERNAL_LINKS = {
  DOCUMENTATION: 'https://docs.market-analysis.com',
  SUPPORT: 'https://support.market-analysis.com',
  STATUS_PAGE: 'https://status.market-analysis.com',
  GITHUB: 'https://github.com/your-org/market-analysis'
}

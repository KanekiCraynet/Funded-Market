/**
 * Design System Theme Configuration
 * 
 * Provides consistent design tokens across the entire application
 */

export const theme = {
  /**
   * Color Palette
   */
  colors: {
    light: {
      background: '#f8f9fa',
      surface: '#ffffff',
      surfaceElevated: '#ffffff',
      primary: '#0d6efd',
      primaryHover: '#0b5ed7',
      secondary: '#6c757d',
      secondaryHover: '#5a6268',
      text: '#212529',
      textMuted: '#6c757d',
      textLight: '#adb5bd',
      success: '#198754',
      successLight: '#d1e7dd',
      danger: '#dc3545',
      dangerLight: '#f8d7da',
      warning: '#ffc107',
      warningLight: '#fff3cd',
      info: '#0dcaf0',
      infoLight: '#cff4fc',
      border: '#dee2e6',
      divider: '#e9ecef',
    },
    dark: {
      background: '#0E0E0E',
      surface: '#1A1A1A',
      surfaceElevated: '#242424',
      primary: '#00b4d8',
      primaryHover: '#0096c7',
      secondary: '#4a5568',
      secondaryHover: '#5a6a7a',
      text: '#f8f9fa',
      textMuted: '#a0aec0',
      textLight: '#718096',
      success: '#48bb78',
      successLight: '#1a3e2c',
      danger: '#f56565',
      dangerLight: '#3e1a1a',
      warning: '#ed8936',
      warningLight: '#3e2a1a',
      info: '#4299e1',
      infoLight: '#1a2e3e',
      border: '#2d3748',
      divider: '#4a5568',
    },
  },

  /**
   * Spacing Scale (8px base)
   */
  spacing: {
    0: '0',
    1: '4px',    // xs
    2: '8px',    // sm
    3: '12px',   // md
    4: '16px',   // lg
    5: '20px',
    6: '24px',   // xl
    8: '32px',   // 2xl
    10: '40px',
    12: '48px',
    16: '64px',
    20: '80px',
    24: '96px',
  },

  /**
   * Border Radius
   */
  radius: {
    none: '0',
    sm: '4px',
    md: '8px',
    lg: '12px',
    xl: '16px',
    full: '9999px',
  },

  /**
   * Typography
   */
  typography: {
    fontFamily: {
      sans: "'Inter', 'Satoshi', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif",
      mono: "'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace",
    },
    fontSize: {
      xs: '0.75rem',    // 12px
      sm: '0.875rem',   // 14px
      base: '1rem',     // 16px
      lg: '1.125rem',   // 18px
      xl: '1.25rem',    // 20px
      '2xl': '1.5rem',  // 24px
      '3xl': '1.875rem', // 30px
      '4xl': '2.25rem', // 36px
      '5xl': '3rem',    // 48px
    },
    fontWeight: {
      light: 300,
      normal: 400,
      medium: 500,
      semibold: 600,
      bold: 700,
      extrabold: 800,
    },
    lineHeight: {
      tight: 1.25,
      normal: 1.5,
      relaxed: 1.75,
    },
  },

  /**
   * Shadows
   */
  shadows: {
    sm: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
    md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
    lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
    xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
    '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
    glow: '0 0 20px rgba(0, 180, 216, 0.3)',
  },

  /**
   * Transitions
   */
  transitions: {
    fast: '150ms cubic-bezier(0.4, 0, 0.2, 1)',
    base: '200ms cubic-bezier(0.4, 0, 0.2, 1)',
    slow: '300ms cubic-bezier(0.4, 0, 0.2, 1)',
    smooth: '500ms cubic-bezier(0.4, 0, 0.2, 1)',
  },

  /**
   * Z-Index Scale
   */
  zIndex: {
    dropdown: 1000,
    sticky: 1020,
    fixed: 1030,
    modalBackdrop: 1040,
    modal: 1050,
    popover: 1060,
    tooltip: 1070,
  },

  /**
   * Breakpoints for responsive design
   */
  breakpoints: {
    xs: '320px',
    sm: '640px',
    md: '768px',
    lg: '1024px',
    xl: '1280px',
    '2xl': '1536px',
  },

  /**
   * Chart Colors (for financial charts)
   */
  chartColors: {
    bullish: '#48bb78',
    bearish: '#f56565',
    neutral: '#a0aec0',
    volume: {
      up: 'rgba(72, 187, 120, 0.3)',
      down: 'rgba(245, 101, 101, 0.3)',
    },
    indicators: {
      ema50: '#00b4d8',
      ema200: '#ed8936',
      macd: '#4299e1',
      signal: '#ed8936',
      rsi: '#9f7aea',
    },
  },

  /**
   * Animation Easings
   */
  easings: {
    easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
    easeOut: 'cubic-bezier(0, 0, 0.2, 1)',
    easeIn: 'cubic-bezier(0.4, 0, 1, 1)',
    bounce: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
  },
}

/**
 * Get current theme based on mode
 */
export function getCurrentTheme(mode = 'light') {
  return {
    ...theme,
    colors: theme.colors[mode] || theme.colors.light,
    mode,
  }
}

export default theme

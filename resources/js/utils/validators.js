/**
 * Validation Utilities
 * 
 * Centralized validation functions
 */

/**
 * Validate email format
 */
export function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

/**
 * Validate symbol format
 */
export function isValidSymbol(symbol) {
  // 2-20 characters, alphanumeric
  const re = /^[A-Z0-9]{2,20}$/
  return re.test(symbol)
}

/**
 * Validate password strength
 */
export function isValidPassword(password) {
  // At least 8 characters
  return password && password.length >= 8
}

/**
 * Get password strength
 */
export function getPasswordStrength(password) {
  if (!password) return { score: 0, label: 'none' }
  
  let score = 0
  
  // Length
  if (password.length >= 8) score++
  if (password.length >= 12) score++
  if (password.length >= 16) score++
  
  // Complexity
  if (/[a-z]/.test(password)) score++
  if (/[A-Z]/.test(password)) score++
  if (/[0-9]/.test(password)) score++
  if (/[^A-Za-z0-9]/.test(password)) score++
  
  const labels = ['weak', 'weak', 'fair', 'good', 'strong', 'very strong', 'excellent']
  
  return {
    score,
    label: labels[Math.min(score, labels.length - 1)]
  }
}

/**
 * Validate number range
 */
export function isInRange(value, min, max) {
  const num = Number(value)
  return !isNaN(num) && num >= min && num <= max
}

/**
 * Validate required field
 */
export function isRequired(value) {
  if (typeof value === 'string') {
    return value.trim().length > 0
  }
  return value !== null && value !== undefined && value !== ''
}

/**
 * Validate minimum length
 */
export function hasMinLength(value, minLength) {
  return value && value.length >= minLength
}

/**
 * Validate maximum length
 */
export function hasMaxLength(value, maxLength) {
  return !value || value.length <= maxLength
}

/**
 * Validate numeric value
 */
export function isNumeric(value) {
  return !isNaN(parseFloat(value)) && isFinite(value)
}

/**
 * Validate integer
 */
export function isInteger(value) {
  return Number.isInteger(Number(value))
}

/**
 * Validate positive number
 */
export function isPositive(value) {
  return isNumeric(value) && Number(value) > 0
}

/**
 * Validate URL format
 */
export function isValidUrl(url) {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

/**
 * Validate date format
 */
export function isValidDate(date) {
  const d = new Date(date)
  return d instanceof Date && !isNaN(d)
}

/**
 * Validate phone number (basic)
 */
export function isValidPhone(phone) {
  // Basic validation: 10-15 digits with optional + and spaces
  const re = /^\+?[\d\s]{10,15}$/
  return re.test(phone)
}

/**
 * Validate alphanumeric
 */
export function isAlphanumeric(value) {
  const re = /^[a-zA-Z0-9]+$/
  return re.test(value)
}

/**
 * Sanitize input (remove HTML tags)
 */
export function sanitizeInput(input) {
  if (typeof input !== 'string') return input
  return input.replace(/<[^>]*>/g, '')
}

/**
 * Validate JSON string
 */
export function isValidJSON(str) {
  try {
    JSON.parse(str)
    return true
  } catch {
    return false
  }
}

/**
 * Form validation helper
 */
export function validateForm(values, rules) {
  const errors = {}
  
  for (const [field, fieldRules] of Object.entries(rules)) {
    const value = values[field]
    
    for (const rule of fieldRules) {
      const { type, message, ...params } = rule
      
      switch (type) {
        case 'required':
          if (!isRequired(value)) {
            errors[field] = message || 'This field is required'
          }
          break
          
        case 'email':
          if (value && !isValidEmail(value)) {
            errors[field] = message || 'Invalid email format'
          }
          break
          
        case 'minLength':
          if (value && !hasMinLength(value, params.min)) {
            errors[field] = message || `Minimum ${params.min} characters required`
          }
          break
          
        case 'maxLength':
          if (value && !hasMaxLength(value, params.max)) {
            errors[field] = message || `Maximum ${params.max} characters allowed`
          }
          break
          
        case 'numeric':
          if (value && !isNumeric(value)) {
            errors[field] = message || 'Must be a number'
          }
          break
          
        case 'range':
          if (value && !isInRange(value, params.min, params.max)) {
            errors[field] = message || `Must be between ${params.min} and ${params.max}`
          }
          break
          
        case 'custom':
          if (!params.validate(value)) {
            errors[field] = message || 'Validation failed'
          }
          break
      }
      
      // Stop at first error for this field
      if (errors[field]) break
    }
  }
  
  return {
    isValid: Object.keys(errors).length === 0,
    errors
  }
}

/**
 * Example usage:
 * 
 * const values = { email: 'test@example.com', password: 'secret' }
 * const rules = {
 *   email: [
 *     { type: 'required', message: 'Email is required' },
 *     { type: 'email', message: 'Invalid email' }
 *   ],
 *   password: [
 *     { type: 'required' },
 *     { type: 'minLength', min: 8, message: 'Password too short' }
 *   ]
 * }
 * 
 * const { isValid, errors } = validateForm(values, rules)
 */

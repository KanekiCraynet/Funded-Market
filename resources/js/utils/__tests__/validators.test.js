import { describe, it, expect } from 'vitest'
import {
  isValidEmail,
  isValidSymbol,
  isValidPassword,
  getPasswordStrength,
  isInRange,
  isRequired,
  hasMinLength,
  hasMaxLength,
  isNumeric,
  isInteger,
  isPositive,
  isAlphanumeric,
  sanitizeInput,
  validateForm
} from '../validators'

describe('Validators', () => {
  describe('isValidEmail', () => {
    it('validates correct email', () => {
      expect(isValidEmail('test@example.com')).toBe(true)
    })

    it('rejects invalid email', () => {
      expect(isValidEmail('invalid.email')).toBe(false)
      expect(isValidEmail('test@')).toBe(false)
      expect(isValidEmail('@example.com')).toBe(false)
    })
  })

  describe('isValidSymbol', () => {
    it('validates correct symbol', () => {
      expect(isValidSymbol('BTCUSDT')).toBe(true)
      expect(isValidSymbol('BTC')).toBe(true)
    })

    it('rejects invalid symbol', () => {
      expect(isValidSymbol('a')).toBe(false) // too short
      expect(isValidSymbol('btcusdt')).toBe(false) // lowercase
      expect(isValidSymbol('BTC-USD')).toBe(false) // special chars
    })
  })

  describe('isValidPassword', () => {
    it('validates password with 8+ characters', () => {
      expect(isValidPassword('password123')).toBe(true)
    })

    it('rejects short password', () => {
      expect(isValidPassword('pass')).toBe(false)
    })
  })

  describe('getPasswordStrength', () => {
    it('rates weak password', () => {
      const result = getPasswordStrength('pass')
      expect(result.score).toBeLessThan(3)
      expect(result.label).toBe('weak')
    })

    it('rates strong password', () => {
      const result = getPasswordStrength('MyP@ssw0rd123!')
      expect(result.score).toBeGreaterThan(4)
    })
  })

  describe('isInRange', () => {
    it('validates number in range', () => {
      expect(isInRange(5, 1, 10)).toBe(true)
    })

    it('rejects number out of range', () => {
      expect(isInRange(15, 1, 10)).toBe(false)
      expect(isInRange(0, 1, 10)).toBe(false)
    })
  })

  describe('isRequired', () => {
    it('validates non-empty value', () => {
      expect(isRequired('test')).toBe(true)
      expect(isRequired(123)).toBe(true)
    })

    it('rejects empty value', () => {
      expect(isRequired('')).toBe(false)
      expect(isRequired('   ')).toBe(false)
      expect(isRequired(null)).toBe(false)
      expect(isRequired(undefined)).toBe(false)
    })
  })

  describe('hasMinLength', () => {
    it('validates string with sufficient length', () => {
      expect(hasMinLength('hello', 3)).toBe(true)
    })

    it('rejects string too short', () => {
      expect(hasMinLength('hi', 5)).toBe(false)
    })
  })

  describe('hasMaxLength', () => {
    it('validates string within limit', () => {
      expect(hasMaxLength('hello', 10)).toBe(true)
    })

    it('rejects string too long', () => {
      expect(hasMaxLength('hello world', 5)).toBe(false)
    })
  })

  describe('isNumeric', () => {
    it('validates numbers', () => {
      expect(isNumeric(123)).toBe(true)
      expect(isNumeric('123')).toBe(true)
      expect(isNumeric('123.45')).toBe(true)
    })

    it('rejects non-numbers', () => {
      expect(isNumeric('abc')).toBe(false)
      expect(isNumeric('12abc')).toBe(false)
    })
  })

  describe('isInteger', () => {
    it('validates integers', () => {
      expect(isInteger(5)).toBe(true)
      expect(isInteger('10')).toBe(true)
    })

    it('rejects decimals', () => {
      expect(isInteger(5.5)).toBe(false)
    })
  })

  describe('isPositive', () => {
    it('validates positive numbers', () => {
      expect(isPositive(5)).toBe(true)
      expect(isPositive('10.5')).toBe(true)
    })

    it('rejects non-positive', () => {
      expect(isPositive(0)).toBe(false)
      expect(isPositive(-5)).toBe(false)
    })
  })

  describe('isAlphanumeric', () => {
    it('validates alphanumeric strings', () => {
      expect(isAlphanumeric('abc123')).toBe(true)
    })

    it('rejects special characters', () => {
      expect(isAlphanumeric('abc-123')).toBe(false)
      expect(isAlphanumeric('abc 123')).toBe(false)
    })
  })

  describe('sanitizeInput', () => {
    it('removes HTML tags', () => {
      expect(sanitizeInput('<script>alert("xss")</script>')).toBe('alert("xss")')
      expect(sanitizeInput('<b>Bold</b> text')).toBe('Bold text')
    })

    it('preserves plain text', () => {
      expect(sanitizeInput('Plain text')).toBe('Plain text')
    })
  })

  describe('validateForm', () => {
    it('validates form with all valid fields', () => {
      const values = {
        email: 'test@example.com',
        password: 'password123'
      }
      const rules = {
        email: [
          { type: 'required' },
          { type: 'email' }
        ],
        password: [
          { type: 'required' },
          { type: 'minLength', min: 8 }
        ]
      }
      
      const { isValid, errors } = validateForm(values, rules)
      expect(isValid).toBe(true)
      expect(Object.keys(errors)).toHaveLength(0)
    })

    it('returns errors for invalid fields', () => {
      const values = {
        email: 'invalid',
        password: 'short'
      }
      const rules = {
        email: [{ type: 'email', message: 'Invalid email' }],
        password: [{ type: 'minLength', min: 8, message: 'Too short' }]
      }
      
      const { isValid, errors } = validateForm(values, rules)
      expect(isValid).toBe(false)
      expect(errors.email).toBe('Invalid email')
      expect(errors.password).toBe('Too short')
    })
  })
})

import { describe, it, expect } from 'vitest'
import {
  formatCurrency,
  formatCompactNumber,
  formatPercentage,
  formatDecimalAsPercentage,
  formatScore,
  truncateText,
  capitalize,
  formatSentiment
} from '../formatters'

describe('Formatters', () => {
  describe('formatCurrency', () => {
    it('formats USD currency correctly', () => {
      expect(formatCurrency(1234.56)).toBe('$1,234.56')
    })

    it('handles large numbers', () => {
      expect(formatCurrency(1234567.89)).toContain('1,234,567.89')
    })

    it('handles small decimals', () => {
      expect(formatCurrency(0.00123456)).toContain('0.00123456')
    })
  })

  describe('formatCompactNumber', () => {
    it('formats billions', () => {
      expect(formatCompactNumber(1500000000)).toBe('1.50B')
    })

    it('formats millions', () => {
      expect(formatCompactNumber(2500000)).toBe('2.50M')
    })

    it('formats thousands', () => {
      expect(formatCompactNumber(3500)).toBe('3.50K')
    })

    it('formats small numbers', () => {
      expect(formatCompactNumber(500)).toBe('500')
    })
  })

  describe('formatPercentage', () => {
    it('formats positive percentage', () => {
      expect(formatPercentage(5.25)).toBe('+5.25%')
    })

    it('formats negative percentage', () => {
      expect(formatPercentage(-3.75)).toBe('-3.75%')
    })

    it('respects decimal places', () => {
      expect(formatPercentage(1.23456, 3)).toBe('+1.235%')
    })
  })

  describe('formatDecimalAsPercentage', () => {
    it('converts decimal to percentage', () => {
      expect(formatDecimalAsPercentage(0.15)).toBe('15%')
    })

    it('respects decimal places', () => {
      expect(formatDecimalAsPercentage(0.1234, 2)).toBe('12.34%')
    })
  })

  describe('formatScore', () => {
    it('formats score with default decimals', () => {
      expect(formatScore(0.123456)).toBe('0.123')
    })

    it('respects decimal parameter', () => {
      expect(formatScore(0.123456, 5)).toBe('0.12346')
    })
  })

  describe('truncateText', () => {
    it('truncates long text', () => {
      const longText = 'a'.repeat(150)
      const result = truncateText(longText, 100)
      expect(result).toHaveLength(103) // 100 + '...'
      expect(result).toContain('...')
    })

    it('does not truncate short text', () => {
      const shortText = 'Hello World'
      expect(truncateText(shortText, 100)).toBe(shortText)
    })
  })

  describe('capitalize', () => {
    it('capitalizes first letter', () => {
      expect(capitalize('hello')).toBe('Hello')
    })

    it('lowercases rest of string', () => {
      expect(capitalize('HELLO')).toBe('Hello')
    })
  })

  describe('formatSentiment', () => {
    it('returns Positive for high polarity', () => {
      expect(formatSentiment(0.5)).toBe('Positive')
    })

    it('returns Negative for low polarity', () => {
      expect(formatSentiment(-0.5)).toBe('Negative')
    })

    it('returns Neutral for mid polarity', () => {
      expect(formatSentiment(0.1)).toBe('Neutral')
    })
  })
})

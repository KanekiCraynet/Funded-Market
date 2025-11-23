<template>
  <div class="space-y-6">
    <!-- Overall Sentiment Score -->
    <Card title="Overall Market Sentiment" variant="glass">
      <div class="p-6 text-center">
        <div class="mb-4">
          <div :class="sentimentColorClass" class="text-6xl font-bold">
            {{ formatSentiment(overallScore) }}
          </div>
          <div class="text-gray-400 mt-2">Overall Sentiment Score</div>
        </div>
        
        <!-- Sentiment Gauge -->
        <div class="relative w-full h-4 bg-white/10 rounded-full overflow-hidden">
          <div 
            class="absolute h-full bg-gradient-to-r from-red-500 via-yellow-500 to-green-500"
            :style="{ width: `${(overallScore + 1) * 50}%` }"
          ></div>
          <div class="absolute inset-0 flex items-center justify-between px-2 text-xs text-white/60">
            <span>Bearish</span>
            <span>Neutral</span>
            <span>Bullish</span>
          </div>
        </div>
        
        <Badge :variant="sentimentBadgeVariant" size="lg" class="mt-4">
          {{ sentimentLabel }}
        </Badge>
      </div>
    </Card>

    <!-- Sentiment Breakdown -->
    <div class="grid md:grid-cols-3 gap-6">
      <!-- News Sentiment -->
      <Card title="News Sentiment" variant="glass">
        <div class="p-4">
          <div class="text-center mb-4">
            <div class="text-4xl font-bold" :class="getSentimentColor(newsScore)">
              {{ formatSentiment(newsScore) }}
            </div>
            <div class="text-sm text-gray-400 mt-1">News Articles</div>
          </div>
          
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-400">Positive</span>
              <span class="text-green-400">{{ newsBreakdown.positive }}%</span>
            </div>
            <div class="w-full bg-white/10 rounded-full h-2">
              <div 
                class="bg-green-500 h-2 rounded-full"
                :style="{ width: `${newsBreakdown.positive}%` }"
              ></div>
            </div>
            
            <div class="flex justify-between text-sm">
              <span class="text-gray-400">Neutral</span>
              <span class="text-yellow-400">{{ newsBreakdown.neutral }}%</span>
            </div>
            <div class="w-full bg-white/10 rounded-full h-2">
              <div 
                class="bg-yellow-500 h-2 rounded-full"
                :style="{ width: `${newsBreakdown.neutral}%` }"
              ></div>
            </div>
            
            <div class="flex justify-between text-sm">
              <span class="text-gray-400">Negative</span>
              <span class="text-red-400">{{ newsBreakdown.negative }}%</span>
            </div>
            <div class="w-full bg-white/10 rounded-full h-2">
              <div 
                class="bg-red-500 h-2 rounded-full"
                :style="{ width: `${newsBreakdown.negative}%` }"
              ></div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Social Media Sentiment -->
      <Card title="Social Media" variant="glass">
        <div class="p-4">
          <div class="text-center mb-4">
            <div class="text-4xl font-bold" :class="getSentimentColor(socialScore)">
              {{ formatSentiment(socialScore) }}
            </div>
            <div class="text-sm text-gray-400 mt-1">Social Signals</div>
          </div>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">Twitter Volume</span>
              <span class="text-sm font-semibold">{{ socialMetrics.twitterVolume }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">Reddit Posts</span>
              <span class="text-sm font-semibold">{{ socialMetrics.redditPosts }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">Trending Score</span>
              <Badge :variant="getTrendingVariant(socialMetrics.trendingScore)">
                {{ socialMetrics.trendingScore }}/100
              </Badge>
            </div>
          </div>
        </div>
      </Card>

      <!-- Market Mood -->
      <Card title="Market Mood" variant="glass">
        <div class="p-4">
          <div class="text-center mb-4">
            <div class="text-4xl font-bold" :class="getSentimentColor(marketMoodScore)">
              {{ formatSentiment(marketMoodScore) }}
            </div>
            <div class="text-sm text-gray-400 mt-1">Fear & Greed</div>
          </div>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">Fear Index</span>
              <span class="text-sm font-semibold text-red-400">{{ moodMetrics.fearIndex }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">Greed Index</span>
              <span class="text-sm font-semibold text-green-400">{{ moodMetrics.greedIndex }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">Volatility</span>
              <Badge :variant="getVolatilityVariant(moodMetrics.volatility)">
                {{ moodMetrics.volatility }}
              </Badge>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Key Sentiment Drivers -->
    <Card title="Key Sentiment Drivers" variant="glass">
      <div class="p-4 space-y-3">
        <div v-for="(driver, index) in sentimentDrivers" :key="index" 
             class="flex items-center justify-between p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
          <div class="flex-1">
            <div class="font-medium text-white">{{ driver.factor }}</div>
            <div class="text-sm text-gray-400">{{ driver.description }}</div>
          </div>
          <div class="flex items-center gap-3">
            <div class="text-right">
              <div class="text-sm font-semibold">{{ driver.impact }}%</div>
              <div class="text-xs text-gray-400">Impact</div>
            </div>
            <Badge :variant="driver.sentiment === 'positive' ? 'success' : driver.sentiment === 'negative' ? 'danger' : 'warning'">
              {{ driver.sentiment }}
            </Badge>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'

const props = defineProps({
  sentimentData: {
    type: Object,
    default: () => ({})
  }
})

const overallScore = computed(() => props.sentimentData?.overall_score || 0)
const newsScore = computed(() => props.sentimentData?.news_sentiment || 0)
const socialScore = computed(() => props.sentimentData?.social_sentiment || 0)
const marketMoodScore = computed(() => props.sentimentData?.market_mood || 0)

const newsBreakdown = computed(() => props.sentimentData?.news_breakdown || {
  positive: 0,
  neutral: 0,
  negative: 0
})

const socialMetrics = computed(() => props.sentimentData?.social_metrics || {
  twitterVolume: 0,
  redditPosts: 0,
  trendingScore: 0
})

const moodMetrics = computed(() => props.sentimentData?.mood_metrics || {
  fearIndex: 0,
  greedIndex: 0,
  volatility: 'MEDIUM'
})

const sentimentDrivers = computed(() => props.sentimentData?.drivers || [])

const sentimentColorClass = computed(() => {
  const score = overallScore.value
  if (score >= 0.3) return 'text-green-400'
  if (score >= -0.3) return 'text-yellow-400'
  return 'text-red-400'
})

const sentimentBadgeVariant = computed(() => {
  const score = overallScore.value
  if (score >= 0.3) return 'success'
  if (score >= -0.3) return 'warning'
  return 'danger'
})

const sentimentLabel = computed(() => {
  const score = overallScore.value
  if (score >= 0.5) return 'VERY BULLISH'
  if (score >= 0.3) return 'BULLISH'
  if (score >= -0.3) return 'NEUTRAL'
  if (score >= -0.5) return 'BEARISH'
  return 'VERY BEARISH'
})

function formatSentiment(value) {
  if (value == null || isNaN(value)) return '0.00'
  return value.toFixed(2)
}

function getSentimentColor(score) {
  if (score >= 0.3) return 'text-green-400'
  if (score >= -0.3) return 'text-yellow-400'
  return 'text-red-400'
}

function getTrendingVariant(score) {
  if (score >= 70) return 'success'
  if (score >= 40) return 'info'
  return 'default'
}

function getVolatilityVariant(level) {
  if (level === 'HIGH') return 'danger'
  if (level === 'LOW') return 'success'
  return 'warning'
}
</script>

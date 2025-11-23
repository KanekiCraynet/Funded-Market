<template>
  <Card title="Watchlist" variant="glass">
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h3 class="text-xl font-semibold text-white">Watchlist</h3>
        <router-link 
          :to="{ name: 'watchlist' }"
          class="text-sm text-purple-400 hover:text-purple-300 transition-colors"
        >
          Manage →
        </router-link>
      </div>
    </template>

    <div v-if="!hasInstruments" class="text-center py-8">
      <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
      <p class="text-gray-400 mb-4">No instruments in watchlist</p>
      <router-link 
        :to="{ name: 'market' }"
        class="inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white text-sm transition-colors"
      >
        Browse Market
      </router-link>
    </div>
    
    <div v-else class="space-y-2">
      <div 
        v-for="inst in watchlistInstruments.slice(0, 5)" 
        :key="inst.symbol"
        class="flex items-center justify-between p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors cursor-pointer group"
        @click="goToInstrument(inst.symbol)"
      >
        <div class="flex items-center gap-3">
          <button
            @click.stop="removeFromWatchlist(inst.symbol)"
            class="text-pink-500 hover:text-pink-400 opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </button>
          <div>
            <div class="font-semibold text-white text-sm">{{ inst.symbol }}</div>
            <div class="text-xs text-gray-400">{{ inst.name }}</div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-400">{{ inst.type }}</div>
        </div>
      </div>
      
      <div v-if="watchlistCount > 5" class="text-center pt-2">
        <router-link 
          :to="{ name: 'watchlist' }"
          class="text-sm text-purple-400 hover:text-purple-300"
        >
          + {{ watchlistCount - 5 }} more
        </router-link>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWatchlistStore } from '@/stores/watchlist'
import Card from '@/components/ui/Card.vue'

const router = useRouter()
const watchlistStore = useWatchlistStore()

const hasInstruments = computed(() => watchlistStore.hasInstruments)
const watchlistInstruments = computed(() => watchlistStore.sortedByAddedDate)
const watchlistCount = computed(() => watchlistStore.watchlistCount)

function goToInstrument(symbol) {
  router.push({ name: 'instrument-detail', params: { symbol } })
}

function removeFromWatchlist(symbol) {
  watchlistStore.removeFromWatchlist(symbol)
}
</script>

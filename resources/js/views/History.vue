<template>
  <div class="history">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Analysis History</h1>
        <p class="page-subtitle">View and manage your past analyses</p>
      </div>
      
      <div v-if="analysisStore.hasHistory" class="stats-summary">
        <div class="stat">
          <span class="stat-label">Total:</span>
          <span class="stat-value">{{ analysisStore.analysisStats.total }}</span>
        </div>
        <div class="stat">
          <Badge variant="success">{{ analysisStore.analysisStats.buySignals }} BUY</Badge>
        </div>
        <div class="stat">
          <Badge variant="danger">{{ analysisStore.analysisStats.sellSignals }} SELL</Badge>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-grid">
        <div class="filter-group">
          <label class="filter-label">Symbol</label>
          <input
            v-model="filters.symbol"
            type="text"
            placeholder="e.g., BTCUSDT"
            class="filter-input"
            @input="handleFilterChange"
          />
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Recommendation</label>
          <select v-model="filters.recommendation" class="filter-select" @change="handleFilterChange">
            <option value="">All</option>
            <option value="BUY">BUY</option>
            <option value="SELL">SELL</option>
            <option value="HOLD">HOLD</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Date From</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="filter-input"
            @change="handleFilterChange"
          />
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Date To</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="filter-input"
            @change="handleFilterChange"
          />
        </div>
      </div>
      
      <div class="filter-actions">
        <Button size="sm" @click="applyFilters">Apply Filters</Button>
        <Button variant="ghost" size="sm" @click="clearFilters">Clear</Button>
      </div>
    </div>

    <!-- History Table -->
    <div class="table-section">
      <div v-if="analysisStore.isLoading" class="table-loading">
        <Skeleton v-for="i in 5" :key="i" height="60px" />
      </div>
      
      <div v-else-if="!analysisStore.hasHistory" class="empty-state">
        <div class="empty-icon">📊</div>
        <h3>No Analysis History</h3>
        <p>Generate your first analysis to see it here</p>
        <Button @click="navigateToGenerate">Generate Analysis</Button>
      </div>
      
      <div v-else class="table-container">
        <table class="history-table">
          <thead>
            <tr>
              <th @click="handleSort('created_at')">
                Date
                <span class="sort-icon">{{ getSortIcon('created_at') }}</span>
              </th>
              <th @click="handleSort('symbol')">
                Symbol
                <span class="sort-icon">{{ getSortIcon('symbol') }}</span>
              </th>
              <th @click="handleSort('recommendation')">
                Signal
                <span class="sort-icon">{{ getSortIcon('recommendation') }}</span>
              </th>
              <th @click="handleSort('final_score')">
                Score
                <span class="sort-icon">{{ getSortIcon('final_score') }}</span>
              </th>
              <th @click="handleSort('confidence')">
                Confidence
                <span class="sort-icon">{{ getSortIcon('confidence') }}</span>
              </th>
              <th @click="handleSort('risk_level')">
                Risk
                <span class="sort-icon">{{ getSortIcon('risk_level') }}</span>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="analysis in sortedHistory" :key="analysis.id" class="table-row">
              <td>{{ formatDate(analysis.created_at) }}</td>
              <td>
                <span class="symbol-badge">{{ analysis.symbol || analysis.instrument?.symbol }}</span>
              </td>
              <td>
                <Badge :variant="getRecommendationVariant(analysis.recommendation)">
                  {{ analysis.recommendation }}
                </Badge>
              </td>
              <td>
                <span :class="getScoreClass(analysis.final_score)">
                  {{ formatScore(analysis.final_score) }}
                </span>
              </td>
              <td>
                <div class="confidence-cell">
                  <span>{{ formatPercentage(analysis.confidence) }}</span>
                  <div class="confidence-bar">
                    <div class="confidence-fill" :style="{ width: `${analysis.confidence * 100}%` }"></div>
                  </div>
                </div>
              </td>
              <td>
                <Badge :variant="getRiskVariant(analysis.risk_level)" size="sm">
                  {{ analysis.risk_level }}
                </Badge>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn" title="View Details" @click="viewAnalysis(analysis)">
                    👁️
                  </button>
                  <button class="action-btn" title="View JSON" @click="viewJSON(analysis)">
                    📄
                  </button>
                  <button class="action-btn action-btn-danger" title="Delete" @click="deleteAnalysis(analysis.id)">
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="analysisStore.hasHistory" class="pagination">
      <Button
        variant="ghost"
        size="sm"
        :disabled="currentPage <= 1"
        @click="goToPage(currentPage - 1)"
      >
        Previous
      </Button>
      
      <div class="page-numbers">
        <button
          v-for="page in visiblePages"
          :key="page"
          class="page-btn"
          :class="{ 'page-btn-active': page === currentPage }"
          @click="goToPage(page)"
        >
          {{ page }}
        </button>
      </div>
      
      <Button
        variant="ghost"
        size="sm"
        :disabled="currentPage >= totalPages"
        @click="goToPage(currentPage + 1)"
      >
        Next
      </Button>
    </div>

    <!-- JSON Viewer Modal -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="showJSONModal" class="modal-overlay" @click="closeJSONModal">
          <div class="modal-content json-modal" @click.stop>
            <div class="modal-header">
              <h3>Analysis JSON</h3>
              <button class="modal-close" @click="closeJSONModal">×</button>
            </div>
            <div class="modal-body">
              <pre class="json-content">{{ formatJSON(selectedAnalysis) }}</pre>
            </div>
            <div class="modal-footer">
              <Button @click="copyJSON">Copy JSON</Button>
              <Button variant="ghost" @click="closeJSONModal">Close</Button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Detail Modal -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="showDetailModal" class="modal-overlay" @click="closeDetailModal">
          <div class="modal-content detail-modal" @click.stop>
            <div class="modal-header">
              <h3>Analysis Details - {{ selectedAnalysis?.symbol }}</h3>
              <button class="modal-close" @click="closeDetailModal">×</button>
            </div>
            <div class="modal-body">
              <div v-if="selectedAnalysis" class="detail-content">
                <div class="detail-section">
                  <h4>Overview</h4>
                  <div class="detail-grid">
                    <div class="detail-item">
                      <span class="detail-label">Recommendation:</span>
                      <Badge :variant="getRecommendationVariant(selectedAnalysis.recommendation)">
                        {{ selectedAnalysis.recommendation }}
                      </Badge>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Score:</span>
                      <span>{{ formatScore(selectedAnalysis.final_score) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Confidence:</span>
                      <span>{{ formatPercentage(selectedAnalysis.confidence) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Risk Level:</span>
                      <Badge :variant="getRiskVariant(selectedAnalysis.risk_level)">
                        {{ selectedAnalysis.risk_level }}
                      </Badge>
                    </div>
                  </div>
                </div>
                
                <div class="detail-section">
                  <h4>Key Drivers</h4>
                  <ul class="drivers-list">
                    <li v-for="(driver, index) in selectedAnalysis.top_drivers" :key="index">
                      {{ driver.factor || driver.name }} - {{ driver.impact || 'neutral' }}
                    </li>
                  </ul>
                </div>
                
                <div class="detail-section">
                  <h4>Explanation</h4>
                  <p>{{ selectedAnalysis.explainability_text }}</p>
                </div>
                
                <div v-if="selectedAnalysis.risk_notes" class="detail-section">
                  <h4>Risk Notes</h4>
                  <p class="risk-notes">{{ selectedAnalysis.risk_notes }}</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <Button variant="ghost" @click="closeDetailModal">Close</Button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAnalysisStore } from '@/stores/analysis'
import { useToast } from '@/composables/useToast'
import Button from '@/components/atoms/Button.vue'
import Badge from '@/components/atoms/Badge.vue'
import Skeleton from '@/components/atoms/Skeleton.vue'

const router = useRouter()
const analysisStore = useAnalysisStore()
const toast = useToast()

const filters = ref({
  symbol: '',
  recommendation: '',
  date_from: '',
  date_to: ''
})

const sortBy = ref('created_at')
const sortOrder = ref('desc')
const currentPage = ref(1)
const showJSONModal = ref(false)
const showDetailModal = ref(false)
const selectedAnalysis = ref(null)

const totalPages = computed(() => analysisStore.pagination.totalPages || 1)

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  const half = Math.floor(maxVisible / 2)
  
  let start = Math.max(1, currentPage.value - half)
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const sortedHistory = computed(() => {
  const history = [...analysisStore.history]
  
  history.sort((a, b) => {
    let aVal = a[sortBy.value]
    let bVal = b[sortBy.value]
    
    // Handle nested properties
    if (sortBy.value === 'symbol') {
      aVal = a.symbol || a.instrument?.symbol
      bVal = b.symbol || b.instrument?.symbol
    }
    
    if (aVal === bVal) return 0
    
    const comparison = aVal < bVal ? -1 : 1
    return sortOrder.value === 'asc' ? comparison : -comparison
  })
  
  return history
})

function handleSort(column) {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortOrder.value = 'desc'
  }
}

function getSortIcon(column) {
  if (sortBy.value !== column) return '↕️'
  return sortOrder.value === 'asc' ? '↑' : '↓'
}

function handleFilterChange() {
  // Debounce can be added here if needed
}

async function applyFilters() {
  currentPage.value = 1
  await fetchHistory()
}

function clearFilters() {
  filters.value = {
    symbol: '',
    recommendation: '',
    date_from: '',
    date_to: ''
  }
  applyFilters()
}

async function fetchHistory() {
  await analysisStore.fetchHistory({
    ...filters.value,
    page: currentPage.value,
    per_page: 15
  })
}

async function goToPage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  await fetchHistory()
}

function viewAnalysis(analysis) {
  selectedAnalysis.value = analysis
  showDetailModal.value = true
}

function viewJSON(analysis) {
  selectedAnalysis.value = analysis
  showJSONModal.value = true
}

async function deleteAnalysis(id) {
  if (!confirm('Are you sure you want to delete this analysis?')) return
  
  const result = await analysisStore.deleteAnalysis(id)
  
  if (result.success) {
    toast.success('Analysis deleted successfully')
  } else {
    toast.error('Failed to delete analysis')
  }
}

function closeJSONModal() {
  showJSONModal.value = false
  selectedAnalysis.value = null
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedAnalysis.value = null
}

function formatJSON(data) {
  return JSON.stringify(data, null, 2)
}

function copyJSON() {
  const json = formatJSON(selectedAnalysis.value)
  navigator.clipboard.writeText(json)
  toast.success('JSON copied to clipboard')
}

function navigateToGenerate() {
  router.push('/generate')
}

// Helper functions
function formatDate(date) {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatScore(score) {
  return score.toFixed(3)
}

function formatPercentage(value) {
  return `${(value * 100).toFixed(0)}%`
}

function getScoreClass(score) {
  if (score > 0.3) return 'score-positive'
  if (score < -0.3) return 'score-negative'
  return 'score-neutral'
}

function getRecommendationVariant(recommendation) {
  if (recommendation === 'BUY') return 'success'
  if (recommendation === 'SELL') return 'danger'
  return 'default'
}

function getRiskVariant(riskLevel) {
  if (riskLevel === 'HIGH') return 'danger'
  if (riskLevel === 'LOW') return 'success'
  return 'warning'
}

onMounted(() => {
  fetchHistory()
})
</script>

<style scoped>
.history {
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  gap: 24px;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: var(--color-text);
}

.page-subtitle {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin: 0;
}

.stats-summary {
  display: flex;
  gap: 16px;
  align-items: center;
}

.stat {
  display: flex;
  gap: 8px;
  align-items: center;
}

.stat-label {
  color: var(--color-text-muted);
  font-size: 0.875rem;
}

.stat-value {
  font-weight: 700;
  color: var(--color-text);
}

/* Filters */
.filters-section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.filter-input,
.filter-select {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-surface-elevated);
  color: var(--color-text);
  font-size: 0.875rem;
}

.filter-actions {
  display: flex;
  gap: 12px;
}

/* Table */
.table-section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  overflow: hidden;
}

.table-container {
  overflow-x: auto;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
}

.history-table thead {
  background: var(--color-surface-elevated);
}

.history-table th {
  padding: 16px;
  text-align: left;
  font-weight: 700;
  color: var(--color-text);
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
}

.history-table th:hover {
  background: var(--color-surface);
}

.sort-icon {
  margin-left: 4px;
  opacity: 0.5;
}

.history-table td {
  padding: 16px;
  border-top: 1px solid var(--color-border);
}

.table-row:hover {
  background: var(--color-surface-elevated);
}

.symbol-badge {
  display: inline-block;
  padding: 4px 12px;
  background: var(--color-primary);
  color: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

.score-positive {
  color: var(--color-success);
  font-weight: 700;
}

.score-negative {
  color: var(--color-danger);
  font-weight: 700;
}

.score-neutral {
  color: var(--color-text-muted);
  font-weight: 600;
}

.confidence-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.confidence-bar {
  width: 60px;
  height: 4px;
  background: var(--color-surface-elevated);
  border-radius: 2px;
  overflow: hidden;
}

.confidence-fill {
  height: 100%;
  background: linear-gradient(90deg, #0d6efd, #00b4d8);
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: var(--color-surface-elevated);
  cursor: pointer;
  transition: all 150ms ease;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.action-btn-danger:hover {
  background: var(--color-danger-light);
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: var(--color-text);
}

.empty-state p {
  margin: 0 0 24px 0;
  color: var(--color-text-muted);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.page-numbers {
  display: flex;
  gap: 8px;
}

.page-btn {
  padding: 8px 14px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: var(--color-surface);
  color: var(--color-text);
  cursor: pointer;
  transition: all 150ms ease;
}

.page-btn:hover {
  background: var(--color-surface-elevated);
}

.page-btn-active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--color-surface);
  border-radius: 16px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
}

.modal-header h3 {
  margin: 0;
  color: var(--color-text);
}

.modal-close {
  font-size: 2rem;
  border: none;
  background: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.modal-close:hover {
  background: var(--color-surface-elevated);
  color: var(--color-text);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid var(--color-border);
}

.json-content {
  background: var(--color-surface-elevated);
  padding: 16px;
  border-radius: 8px;
  overflow-x: auto;
  font-size: 0.875rem;
  line-height: 1.6;
  color: var(--color-text);
}

.detail-section {
  margin-bottom: 24px;
}

.detail-section h4 {
  margin: 0 0 12px 0;
  color: var(--color-text);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-label {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  font-weight: 600;
}

.drivers-list {
  margin: 0;
  padding-left: 20px;
  color: var(--color-text);
}

.drivers-list li {
  margin-bottom: 8px;
}

.risk-notes {
  padding: 12px;
  background: var(--color-warning-light);
  border-left: 3px solid var(--color-warning);
  border-radius: 8px;
  color: var(--color-text);
}

/* Modal animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 200ms ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .history {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
  }
  
  .stats-summary {
    flex-wrap: wrap;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .history-table {
    font-size: 0.875rem;
  }
  
  .history-table th,
  .history-table td {
    padding: 12px 8px;
  }
}
</style>

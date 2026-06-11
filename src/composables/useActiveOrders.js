import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '../services/api'

const activeOrders = ref([])
const loading = ref(false)
let pollId = null

async function fetchActiveOrders() {
  const token = localStorage.getItem('tavliq_token')
  if (!token) return
  try {
    const res = await api.get('/orders', {
      params: { status: 'pending,preparing,ready', per_page: 50 }
    })
    const raw = res.data?.data
    const fetched = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : []
    activeOrders.value = fetched
  } catch (e) {
    // silently fail — will retry on next poll
  }
}

function startPolling(intervalMs = 15000) {
  if (pollId) return
  fetchActiveOrders()
  pollId = setInterval(fetchActiveOrders, intervalMs)
}

function stopPolling() {
  if (pollId) {
    clearInterval(pollId)
    pollId = null
  }
}

export function useActiveOrders() {
  const count = computed(() => activeOrders.value.length)

  function hasActiveForRestaurant(restaurantId) {
    return activeOrders.value.some(o => o.restaurant_id === restaurantId)
  }

  function getActiveForRestaurant(restaurantId) {
    return activeOrders.value.filter(o => o.restaurant_id === restaurantId)
  }

  return {
    activeOrders,
    count,
    loading,
    fetchActiveOrders,
    startPolling,
    stopPolling,
    hasActiveForRestaurant,
    getActiveForRestaurant,
  }
}

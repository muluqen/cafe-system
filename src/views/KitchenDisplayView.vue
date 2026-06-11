<template>
  <div :class="['kds', { 'kds--fullscreen': isFullscreen }]">
    <header class="kds__header">
      <div class="kds__header-left">
        <div class="kds__title">KDS</div>
        <div v-if="showTabs" class="kds__station-tabs">
          <button
            v-if="canViewKitchen"
            :class="['kds__tab', { 'kds__tab--active': activeView === 'kitchen' }]"
            @click="activeView = 'kitchen'"
          >Kitchen</button>
          <button
            v-if="canViewBarista"
            :class="['kds__tab', { 'kds__tab--active': activeView === 'barista' }]"
            @click="activeView = 'barista'"
          >Barista</button>
          <button
            v-if="canViewExpeditor"
            :class="['kds__tab', { 'kds__tab--active': activeView === 'all' }]"
            @click="activeView = 'all'"
          >Expeditor</button>
        </div>
        <div v-else class="kds__station-badge">{{ activeView === 'all' ? 'Expeditor' : capitalize(activeView) }}</div>
        <div class="kds__type-filter">
          <button :class="['kds__type-btn', { 'kds__type-btn--active': orderTypeFilter === 'all' }]" @click="orderTypeFilter = 'all'">All</button>
          <button :class="['kds__type-btn', { 'kds__type-btn--active': orderTypeFilter === 'dine_in' }]" @click="orderTypeFilter = 'dine_in'">🍽 Dine-in</button>
          <button :class="['kds__type-btn', { 'kds__type-btn--active': orderTypeFilter === 'takeaway' }]" @click="orderTypeFilter = 'takeaway'">🥡 Takeaway</button>
        </div>
      </div>
      <div class="kds__stats">
        <span class="kds__stat kds__stat--pending">{{ pendingCount }} new</span>
        <span class="kds__stat kds__stat--preparing">{{ activeCount }} active</span>
      </div>
      <div class="kds__clock">{{ clock }}</div>
      <button class="kds__refresh" @click="loadOrders">↻</button>
      <button class="kds__fullscreen" @click="toggleFullscreen">
        {{ isFullscreen ? '⊡' : '⛶' }}
      </button>
    </header>

    <!-- No Access -->
    <div v-if="!hasAnyAccess" class="kds__no-access">
      <div class="kds__no-access-icon">🔒</div>
      <div class="kds__no-access-text">No KDS access</div>
      <div class="kds__no-access-sub">Ask your manager to grant KDS permissions</div>
    </div>

    <!-- Station View (Kitchen / Barista) -->
    <div v-else-if="activeView !== 'all'" class="kds__queue">
      <div v-if="stationQueue.length === 0" class="kds__empty-large">
        <div class="kds__empty-icon">✓</div>
        <div class="kds__empty-text">All caught up!</div>
      </div>
      <div
        v-for="order in stationQueue"
        :key="order.id"
        :class="['kds-order', urgencyClass(order), { 'kds-order--expanded': expandedOrder === order.id, 'kds-order--done': isStationDone(order) }]"
      >
        <!-- Compact Card Header (always visible) -->
        <div class="kds-order__header" @click="toggleExpand(order.id)">
          <div class="kds-order__header-left">
            <span class="kds-order__number">#{{ order.order_number }}</span>
            <span class="kds-order__table">
              {{ order.table?.name || order.table_id ? `Table ${order.table_id}` : 'Takeaway' }}
            </span>
          </div>
          <div class="kds-order__header-right">
            <span class="kds-order__item-count">
              {{ getReadyCount(order) }}/{{ getStationItems(order).length }}
            </span>
            <span :class="['kds-order__timer', urgencyTimerClass(order)]">
              {{ timer(order.placed_at) }}
            </span>
            <span class="kds-order__expand-icon">{{ expandedOrder === order.id ? '▾' : '▸' }}</span>
          </div>
        </div>

        <!-- Order-level notes (always visible) -->
        <div v-if="order.notes && order.notes !== 'POS Order'" class="kds-order__notes">
          📝 {{ order.notes }}
        </div>

        <!-- Expanded Details -->
        <div v-if="expandedOrder === order.id" class="kds-order__details">
          <div
            v-for="item in getStationItems(order)"
            :key="item.id"
            :class="['kds-item', { 'kds-item--done': item.status === 'ready' || item.status === 'completed' }]"
          >
            <label class="kds-item__checkbox">
              <input
                type="checkbox"
                :checked="item.status === 'ready' || item.status === 'completed'"
                :disabled="item.status === 'ready' || item.status === 'completed'"
                @change="toggleItemReady(item)"
              />
              <span class="kds-item__checkmark"></span>
            </label>
            <div class="kds-item__content">
              <div class="kds-item__line">
                <span class="kds-item__qty">{{ item.quantity }}×</span>
                <span class="kds-item__name">{{ item.item_name }}</span>
              </div>
              <!-- Customization tags -->
              <div v-if="item.customized_ingredients && item.customized_ingredients.length" class="kds-item__customizations">
                <span
                  v-for="ci in item.customized_ingredients"
                  :key="ci.ingredient_id || ci.name"
                  :class="['kds-tag', { 'kds-tag--removed': ci.quantity_required === 0, 'kds-tag--extra': ci.default_quantity != null && ci.quantity_required > ci.default_quantity, 'kds-tag--less': ci.default_quantity != null && ci.quantity_required < ci.default_quantity && ci.quantity_required > 0 }]"
                >
                  <template v-if="ci.quantity_required === 0">No {{ ci.name || ci.ingredient_name || 'Ingredient' }}</template>
                  <template v-else-if="ci.default_quantity != null && ci.quantity_required !== ci.default_quantity">
                    {{ ci.name || ci.ingredient_name || 'Ingredient' }}
                    <span v-if="ci.quantity_required > ci.default_quantity">+{{ (ci.quantity_required - ci.default_quantity).toFixed(0) }}{{ ci.unit }}</span>
                    <span v-else>-{{ (ci.default_quantity - ci.quantity_required).toFixed(0) }}{{ ci.unit }}</span>
                  </template>
                  <template v-else>{{ ci.name || ci.ingredient_name || 'Ingredient' }}: {{ ci.quantity_required }}{{ ci.unit }}</template>
                </span>
              </div>
              <!-- Item notes -->
              <div v-if="item.notes" class="kds-item__item-notes">
                📝 {{ item.notes }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Expeditor View (2 columns) -->
    <div v-else class="kds__board">
      <!-- Pending Orders -->
      <div class="kds__col">
        <div class="kds__col-title kds__col-title--pending">
          New Orders
          <span class="kds__col-count">{{ pendingOrders.length }}</span>
        </div>
        <div v-for="order in pendingOrders" :key="order.id" :class="['kds-card', urgencyClass(order)]">
          <div class="kds-card__header">
            <span class="kds-card__number">#{{ order.order_number }}</span>
            <span :class="['kds-card__timer', urgencyTimerClass(order)]">{{ timer(order.placed_at) }}</span>
          </div>
          <div class="kds-card__table">
            {{ order.table?.name || order.table_id ? `Table ${order.table_id}` : 'Takeaway' }}
          </div>
          <div v-if="order.notes && order.notes !== 'POS Order'" class="kds-card__notes">
            📝 {{ order.notes }}
          </div>
          <!-- Station progress -->
          <div class="kds-card__stations">
            <span
              v-for="station in getActiveStations(order)"
              :key="station"
              :class="['kds-station-badge', `kds-station-badge--${getStationStatus(order, station)}`]"
            >
              {{ station === 'kitchen' ? '🍳' : '☕' }}
              {{ capitalize(station) }}
              <span v-if="getStationStatus(order, station) === 'done'">✓</span>
            </span>
          </div>
          <div class="kds-card__items">
            <div v-for="item in getOrderItems(order)" :key="item.id" class="kds-card-item">
              <span class="kds-card-item__qty">{{ item.quantity }}×</span>
              <span class="kds-card-item__name">{{ item.item_name }}</span>
              <span class="kds-card-item__station" :class="`station--${getItemStation(item)}`">
                {{ getItemStation(item) }}
              </span>
            </div>
          </div>
        </div>
        <div v-if="pendingOrders.length === 0" class="kds__empty">No pending orders</div>
      </div>

      <!-- Ready to Serve -->
      <div class="kds__col">
        <div class="kds__col-title kds__col-title--ready">
          Ready to Serve
          <span class="kds__col-count">{{ readyOrders.length }}</span>
        </div>
        <div v-for="order in readyOrders" :key="order.id" class="kds-card kds-card--ready">
          <div class="kds-card__header">
            <span class="kds-card__number">#{{ order.order_number }}</span>
            <span class="kds-card__time">{{ timer(order.placed_at) }}</span>
          </div>
          <div class="kds-card__table">
            {{ order.table?.name || order.table_id ? `Table ${order.table_id}` : 'Takeaway' }}
          </div>
          <div class="kds-card__stations">
            <span
              v-for="station in getActiveStations(order)"
              :key="station"
              class="kds-station-badge kds-station-badge--done"
            >
              {{ station === 'kitchen' ? '🍳' : '☕' }}
              {{ capitalize(station) }} ✓
            </span>
          </div>
          <div class="kds-card__items">
            <div v-for="item in getOrderItems(order)" :key="item.id" class="kds-card-item kds-card-item--done">
              <span class="kds-card-item__qty">{{ item.quantity }}×</span>
              <span class="kds-card-item__name">{{ item.item_name }}</span>
            </div>
          </div>
          <button class="kds-card__action kds-card__action--serve" @click="serveOrder(order)">
            Serve to Customer
          </button>
        </div>
        <div v-if="readyOrders.length === 0" class="kds__empty">No orders ready</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/authStore'
import { useToast } from '../composables/useToast'

const auth = useAuthStore()
const toast = useToast()
const orders = ref([])
const clock = ref('')
const now = ref(Date.now())
const expandedOrder = ref(null)
let clockId = null
let tickId = null

// Permission-based station access
const canViewKitchen = computed(() => auth.canRead('kds_kitchen'))
const canViewBarista = computed(() => auth.canRead('kds_barista'))
const canViewExpeditor = computed(() => auth.canRead('kds_expeditor'))

const hasAnyAccess = computed(() => canViewKitchen.value || canViewBarista.value || canViewExpeditor.value)

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : ''

const allowedStations = computed(() => {
  const stations = []
  if (canViewKitchen.value) stations.push('kitchen')
  if (canViewBarista.value) stations.push('barista')
  if (canViewExpeditor.value) stations.push('all')
  return stations
})

const showTabs = computed(() => allowedStations.value.length > 1)

const defaultView = computed(() => {
  if (canViewKitchen.value) return 'kitchen'
  if (canViewBarista.value) return 'barista'
  return 'all'
})

const activeView = ref(defaultView.value)
const stationFilter = activeView
const orderTypeFilter = ref('all')
const isFullscreen = ref(false)
const autoHideOrders = ref(new Map())

function toggleExpand(orderId) {
  expandedOrder.value = expandedOrder.value === orderId ? null : orderId
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()
    isFullscreen.value = true
  } else {
    document.exitFullscreen()
    isFullscreen.value = false
  }
}

function getItemStation(item) {
  return item.routing_station || item.station || item.routed_to || 'kitchen'
}

function getOrderItems(order) {
  return order.order_items || order.orderItems || order.items || []
}

function getStationItems(order) {
  const items = getOrderItems(order)
  if (stationFilter.value === 'all') return items
  return items.filter(i => getItemStation(i) === stationFilter.value)
}

function getReadyCount(order) {
  return getStationItems(order).filter(i => i.status === 'ready' || i.status === 'completed').length
}

function isStationDone(order) {
  const items = getStationItems(order)
  return items.length > 0 && items.every(i => i.status === 'ready' || i.status === 'completed')
}

function isOrderFullyDone(order) {
  const items = getOrderItems(order)
  return items.length > 0 && items.every(i => i.status === 'ready' || i.status === 'completed')
}

function getActiveStations(order) {
  const stations = new Set()
  getOrderItems(order).forEach(item => {
    stations.add(getItemStation(item))
  })
  return Array.from(stations)
}

function getStationStatus(order, station) {
  const items = getOrderItems(order).filter(i => getItemStation(i) === station)
  if (items.length === 0) return 'pending'
  if (items.every(i => i.status === 'ready' || i.status === 'completed')) return 'done'
  return 'pending'
}

function elapsedMinutes(placedAt) {
  if (!placedAt) return 0
  return (now.value - new Date(placedAt)) / 60000
}

function urgencyClass(order) {
  const m = elapsedMinutes(order.placed_at)
  if (m > 10) return 'kds-urgent'
  if (m > 5) return 'kds-warning'
  return ''
}

function urgencyTimerClass(order) {
  const m = elapsedMinutes(order.placed_at)
  if (m > 10) return 'timer--danger'
  if (m > 5) return 'timer--warning'
  return 'timer--normal'
}

function timer(placedAt) {
  const m = elapsedMinutes(placedAt)
  if (m < 1) return '<1m'
  if (m < 60) return Math.floor(m) + 'm'
  return Math.floor(m / 60) + 'h' + (m % 60 > 0 ? ' ' + Math.floor(m % 60) + 'm' : '')
}

// Sorted queue for station view
const stationQueue = computed(() => {
  const station = stationFilter.value
  let filtered = orders.value

  // Apply order type filter
  filtered = filtered.filter(o => {
    if (autoHideOrders.value.has(o.id)) return false
    if (orderTypeFilter.value !== 'all') {
      const isTakeaway = !o.table_id
      if (orderTypeFilter.value === 'takeaway' && !isTakeaway) return false
      if (orderTypeFilter.value === 'dine_in' && isTakeaway) return false
    }
    return true
  })

  if (station !== 'all') {
    filtered = filtered
      .map(order => {
        const relevantItems = getOrderItems(order).filter(i => getItemStation(i) === station)
        return { ...order, order_items: relevantItems }
      })
      .filter(order => order.order_items.length > 0)
  }

  // Show orders that are NOT fully done (for this station)
  return filtered
    .filter(order => !isStationDone(order))
    .sort((a, b) => new Date(a.placed_at) - new Date(b.placed_at))
})

const filteredOrders = computed(() => {
  return orders.value.filter(o => {
    if (autoHideOrders.value.has(o.id)) return false
    if (orderTypeFilter.value !== 'all') {
      const isTakeaway = !o.table_id
      if (orderTypeFilter.value === 'takeaway' && !isTakeaway) return false
      if (orderTypeFilter.value === 'dine_in' && isTakeaway) return false
    }
    return true
  })
})

const pendingOrders = computed(() =>
  filteredOrders.value.filter(o => !isOrderFullyDone(o))
)

const readyOrders = computed(() =>
  filteredOrders.value.filter(o => isOrderFullyDone(o))
)

const pendingCount = computed(() => pendingOrders.value.length)
const activeCount = computed(() => {
  return filteredOrders.value.filter(o => {
    const items = getOrderItems(o)
    const hasStarted = items.some(i => i.status === 'ready' || i.status === 'completed')
    return hasStarted && !isOrderFullyDone(o)
  }).length
})

// API Actions
async function loadOrders() {
  try {
    const res = await api.get('/orders', {
      params: { per_page: 50, status: 'pending,preparing,ready' }
    })
    const raw = res.data?.data
    let fetched = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : []
    orders.value = fetched.map(o => {
      const items = o.order_items || o.orderItems || o.items || []
      return { ...o, order_items: items }
    })
  } catch(e) {
    console.error('[KDS] Fetch failed:', e)
    toast.error('Failed to load orders')
  }
}

async function toggleItemReady(item) {
  if (item.status === 'ready' || item.status === 'completed') return

  try {
    await api.patch(`/order_items/${item.id}`, { status: 'ready' })
    // Update local state immediately
    item.status = 'ready'
  } catch(e) {
    console.error('[KDS] Failed to mark item ready:', e)
    toast.error('Failed to update item')
  }
}

async function serveOrder(order) {
  try {
    await api.patch(`/orders/${order.id}`, { status: 'completed' })
    toast.success(`Order #${order.order_number} served!`)
    autoHideOrders.value.set(order.id, true)
    setTimeout(() => {
      orders.value = orders.value.filter(o => o.id !== order.id)
      autoHideOrders.value.delete(order.id)
    }, 4000)
  } catch(e) {
    toast.error('Failed to complete order')
  }
}

function updateClock() {
  clock.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

onMounted(() => {
  loadOrders()
  updateClock()
  clockId = setInterval(updateClock, 1000)
  tickId = setInterval(() => { now.value = Date.now() }, 5000)
  document.addEventListener('fullscreenchange', onFullscreenChange)

  // WebSocket: listen for new orders and item status changes
  import('../services/echo.js').then(({ getEcho }) => {
    const echo = getEcho()
    const restaurantId = auth.user?.restaurant_id
    if (restaurantId) {
      echo.channel(`kds.${restaurantId}`)
        .listen('.order.placed', () => {
          loadOrders()
        })
        .listen('.item.status', (e) => {
          // Update item status locally without full reload
          const order = orders.value.find(o => o.id === e.order_id)
          if (order) {
            const item = getOrderItems(order).find(i => i.id === e.item_id)
            if (item) {
              item.status = e.new_status
            }
          }
        })
    }
  })
})

onUnmounted(() => {
  clearInterval(clockId)
  clearInterval(tickId)
  document.removeEventListener('fullscreenchange', onFullscreenChange)

  import('../services/echo.js').then(({ getEcho }) => {
    const echo = getEcho()
    const restaurantId = auth.user?.restaurant_id
    if (restaurantId) {
      echo.leaveChannel(`kds.${restaurantId}`)
    }
  })
})

function onFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}
</script>

<style scoped>
.kds {
  background: #09090B;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.kds--fullscreen {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #000;
}

/* Header */
.kds__header {
  background: #111113;
  border-bottom: 1px solid #27272A;
  padding: 0.75rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  position: sticky;
  top: 0;
  z-index: 20;
}

.kds__header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.kds__title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #FAFAFA;
}

.kds__station-tabs {
  display: flex;
  gap: 0.25rem;
  background: #27272A;
  border-radius: 0.5rem;
  padding: 0.2rem;
}

.kds__tab {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.3rem 0.75rem;
  border: none;
  border-radius: 0.35rem;
  background: transparent;
  color: #71717A;
  cursor: pointer;
  transition: all 150ms;
}

.kds__tab:hover { color: #FAFAFA; }

.kds__tab--active {
  background: #FAFAFA;
  color: #09090B;
}

.kds__station-badge {
  font-size: 0.65rem;
  font-weight: 700;
  color: #06B6D4;
  background: rgba(6, 182, 212, 0.12);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.kds__stats {
  display: flex;
  gap: 0.5rem;
  margin-left: auto;
}

.kds__stat {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.kds__stat--pending { color: #F9C22E; background: rgba(249, 194, 46, 0.12); }
.kds__stat--preparing { color: #06B6D4; background: rgba(6, 182, 212, 0.12); }

.kds__clock {
  font-family: 'Courier New', monospace;
  color: #71717A;
  font-size: 0.85rem;
}

.kds__refresh,
.kds__fullscreen {
  background: #27272A;
  border: none;
  color: #FAFAFA;
  font-size: 1.1rem;
  width: 34px;
  height: 34px;
  border-radius: 0.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 150ms;
}

.kds__refresh:hover,
.kds__fullscreen:hover { background: #3F3F46; }

.kds__type-filter {
  display: flex;
  gap: 0.25rem;
  margin-left: 0.75rem;
}

.kds__type-btn {
  background: #27272A;
  border: none;
  color: #A1A1AA;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.3rem 0.6rem;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 150ms;
}

.kds__type-btn:hover { color: #FAFAFA; background: #3F3F46; }

.kds__type-btn--active {
  color: #06B6D4;
  background: rgba(6, 182, 212, 0.15);
}

/* Station Queue */
.kds__queue {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.kds__empty-large {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #52525B;
  gap: 0.5rem;
}

.kds__empty-icon { font-size: 3rem; opacity: 0.3; }
.kds__empty-text { font-size: 1rem; font-weight: 600; }

.kds__no-access {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #52525B;
  gap: 0.5rem;
}

.kds__no-access-icon { font-size: 3rem; }
.kds__no-access-text { font-size: 1.1rem; font-weight: 700; color: #A1A1AA; }
.kds__no-access-sub { font-size: 0.85rem; color: #71717A; }

/* Order Card */
.kds-order {
  background: #18181B;
  border-radius: 0.75rem;
  border: 2px solid #27272A;
  overflow: hidden;
  transition: border-color 200ms, box-shadow 200ms;
}

.kds-order.kds-warning {
  border-color: #F9C22E;
  box-shadow: 0 0 15px rgba(249, 194, 46, 0.15);
}

.kds-order.kds-urgent {
  border-color: #EF4444;
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.25);
  animation: pulse-urgent 1.5s ease-in-out infinite;
}

.kds-order--done {
  border-color: #22C55E;
  opacity: 0.6;
}

@keyframes pulse-urgent {
  0%, 100% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.25); }
  50% { box-shadow: 0 0 35px rgba(239, 68, 68, 0.45); }
}

.kds-order__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  cursor: pointer;
  user-select: none;
  transition: background 150ms;
}

.kds-order__header:hover { background: rgba(255, 255, 255, 0.03); }

.kds-order__header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.kds-order__number {
  font-size: 1rem;
  font-weight: 800;
  color: #FAFAFA;
}

.kds-order__table {
  font-size: 0.7rem;
  color: #A1A1AA;
  background: #27272A;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.kds-order__header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.kds-order__item-count {
  font-size: 0.7rem;
  font-weight: 700;
  color: #06B6D4;
  background: rgba(6, 182, 212, 0.12);
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.kds-order__timer {
  font-family: 'Courier New', monospace;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.timer--normal { color: #A1A1AA; background: #27272A; }
.timer--warning { color: #F9C22E; background: rgba(249, 194, 46, 0.15); }
.timer--danger { color: #EF4444; background: rgba(239, 68, 68, 0.15); }

.kds-order__expand-icon {
  color: #52525B;
  font-size: 0.8rem;
}

.kds-order__notes {
  background: rgba(6, 182, 212, 0.08);
  border-left: 3px solid #06B6D4;
  padding: 0.4rem 0.75rem;
  font-size: 0.75rem;
  color: #A5F3FC;
  margin: 0 0.75rem 0.5rem;
  border-radius: 0 4px 4px 0;
  word-break: break-word;
}

/* Expanded Details */
.kds-order__details {
  padding: 0 0.75rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  border-top: 1px solid #27272A;
}

/* Item Row */
.kds-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  background: #27272A;
  border-radius: 0.5rem;
  transition: opacity 200ms, background 200ms;
}

.kds-item--done {
  opacity: 0.4;
}

.kds-item--done .kds-item__name {
  text-decoration: line-through;
}

/* Custom Checkbox */
.kds-item__checkbox {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.kds-item__checkbox input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.kds-item__checkmark {
  width: 28px;
  height: 28px;
  border: 2px solid #3F3F46;
  border-radius: 0.35rem;
  background: #18181B;
  cursor: pointer;
  transition: all 200ms;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kds-item__checkbox input:disabled + .kds-item__checkmark {
  cursor: default;
}

.kds-item__checkbox input:checked + .kds-item__checkmark {
  background: #22C55E;
  border-color: #22C55E;
}

.kds-item__checkbox input:checked + .kds-item__checkmark::after {
  content: '✓';
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
}

.kds-item__checkbox input:not(:checked) + .kds-item__checkmark:hover {
  border-color: #22C55E;
  background: rgba(34, 197, 94, 0.1);
}

.kds-item__content {
  flex: 1;
  min-width: 0;
}

.kds-item__line {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.kds-item__qty {
  font-weight: 700;
  color: #F9C22E;
  min-width: 24px;
  font-size: 0.9rem;
}

.kds-item__name {
  flex: 1;
  color: #FAFAFA;
  font-weight: 500;
  font-size: 0.9rem;
}

/* Customization Tags */
.kds-item__customizations {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin-top: 0.35rem;
}

.kds-tag {
  font-size: 0.65rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.06);
  color: #A1A1AA;
}

.kds-tag--removed {
  color: #F87171;
  background: rgba(248, 113, 113, 0.12);
  text-decoration: line-through;
}

.kds-tag--extra {
  color: #F9C22E;
  background: rgba(249, 194, 46, 0.12);
}

.kds-tag--less {
  color: #60A5FA;
  background: rgba(96, 165, 250, 0.12);
}

.kds-item__item-notes {
  font-size: 0.65rem;
  color: #F9C22E;
  font-style: italic;
  margin-top: 0.3rem;
}

/* Expeditor Board */
.kds__board {
  display: flex;
  gap: 1rem;
  flex: 1;
  padding: 1rem;
  overflow-x: auto;
}

.kds__col {
  flex: 1;
  min-width: 300px;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow-y: auto;
  background: #111113;
  border: 1px solid #27272A;
  border-radius: 1rem;
  padding: 1rem;
}

.kds__col-title {
  text-align: center;
  font-size: 0.9rem;
  font-weight: 700;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #27272A;
  position: sticky;
  top: 0;
  z-index: 5;
  background: #111113;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.kds__col-count {
  font-size: 0.7rem;
  font-weight: 700;
  background: #27272A;
  padding: 0.1rem 0.4rem;
  border-radius: 9999px;
  color: #A1A1AA;
}

.kds__col-title--pending { color: #F9C22E; }
.kds__col-title--ready { color: #10B981; }

.kds__empty {
  text-align: center;
  color: #52525B;
  font-style: italic;
  padding: 2rem;
  font-size: 0.85rem;
}

/* Expeditor Card */
.kds-card {
  background: #18181B;
  border-radius: 0.75rem;
  padding: 1rem;
  border: 2px solid #27272A;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  transition: border-color 200ms;
}

.kds-card--ready { border-color: #10B981; }
.kds-card.kds-warning { border-color: #F9C22E; }
.kds-card.kds-urgent { border-color: #EF4444; animation: pulse-urgent 1.5s ease-in-out infinite; }

.kds-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.kds-card__number { font-size: 1rem; font-weight: 800; color: #FAFAFA; }

.kds-card__timer,
.kds-card__time {
  font-family: 'Courier New', monospace;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.4rem;
  border-radius: 9999px;
}

.kds-card__table { font-size: 0.75rem; color: #A1A1AA; }

.kds-card__notes {
  background: rgba(255, 255, 255, 0.05);
  border-left: 3px solid #71717A;
  padding: 0.3rem 0.5rem;
  font-size: 0.7rem;
  color: #D4D4D8;
  border-radius: 0 4px 4px 0;
  word-break: break-word;
}

.kds-card__stations {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  padding: 0.3rem 0;
}

.kds-station-badge {
  font-size: 0.65rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
}

.kds-station-badge--pending {
  color: #A1A1AA;
  background: rgba(161, 161, 170, 0.1);
}

.kds-station-badge--done {
  color: #22C55E;
  background: rgba(34, 197, 94, 0.15);
}

.kds-card__items { display: flex; flex-direction: column; gap: 0.25rem; }

.kds-card-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.5rem;
  background: #27272A;
  border-radius: 0.3rem;
  font-size: 0.8rem;
}

.kds-card-item--done { opacity: 0.4; }
.kds-card-item--done .kds-card-item__name { text-decoration: line-through; }

.kds-card-item__qty { font-weight: 700; color: #F9C22E; min-width: 20px; }
.kds-card-item__name { flex: 1; color: #FAFAFA; }

.kds-card-item__station {
  font-size: 0.55rem;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 9999px;
  text-transform: uppercase;
}

.station--kitchen { background: rgba(255, 60, 172, 0.15); color: #FF3CAC; }
.station--barista { background: rgba(6, 182, 212, 0.15); color: #06B6D4; }

/* Expeditor Actions */
.kds-card__action {
  width: 100%;
  padding: 0.55rem;
  border: none;
  border-radius: 0.4rem;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 150ms;
  margin-top: 0.25rem;
}

.kds-card__action--serve {
  background: linear-gradient(135deg, #8B5CF6, #7C3AED);
  color: white;
}

.kds-card__action--serve:hover {
  box-shadow: 0 0 15px rgba(139, 92, 246, 0.4);
}

@media (max-width: 768px) {
  .kds__board { flex-direction: column; }
  .kds__col { min-width: auto; }
  .kds__header { flex-wrap: wrap; gap: 0.5rem; }
  .kds__stats { margin-left: 0; }
}
</style>

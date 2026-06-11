<template>
  <div class="dashboard">
    <!-- Greeting -->
    <div class="greeting animate-on-scroll">
      <div class="greeting__text">{{ greeting }}, {{ auth.user?.name || 'Manager' }} 👋</div>
      <div class="greeting__sub">{{ todayDate }}</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div v-for="(stat, i) in statCards" :key="i" class="stat-card animate-on-scroll">
        <div class="stat-card__icon" :style="{ background: stat.iconBg, color: stat.iconColor }">{{ stat.icon }}</div>
        <div class="stat-card__value">{{ stat.value }}</div>
        <div class="stat-card__label">{{ stat.label }}</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <router-link v-for="a in actions" :key="a.label" :to="a.to" class="action-card animate-on-scroll">
        <div class="action-card__icon">{{ a.icon }}</div>
        <div class="action-card__label">{{ a.label }}</div>
      </router-link>
    </div>

    <!-- Recent Orders -->
    <div class="section animate-on-scroll">
      <div class="section__header">
        <div class="section__title">Recent Orders</div>
        <span class="badge">{{ recentOrders.length }}</span>
      </div>
      <div v-if="loading" class="loading-rows">
        <div v-for="i in 4" :key="i" class="shimmer-row"/>
      </div>
      <div v-else-if="recentOrders.length" class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Order #</th><th>Status</th><th>Total</th><th>Time</th></tr>
          </thead>
          <tbody>
            <tr v-for="o in recentOrders" :key="o.id">
              <td>{{ o.order_number || '#'+o.id }}</td>
              <td><span class="badge" :class="'badge--'+statusColor(o.status)">{{ o.status }}</span></td>
              <td>${{ Number(o.total||0).toFixed(2) }}</td>
              <td>{{ formatTime(o.placed_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty">No orders yet today.</div>
    </div>

    <!-- Low Stock -->
    <div v-if="lowStock.length" class="section animate-on-scroll">
      <div class="section__header">
        <div class="section__title">⚠️ Low Stock Alerts</div>
        <span class="badge badge--danger">{{ lowStock.length }}</span>
      </div>
      <div class="stock-list">
        <div v-for="s in lowStock" :key="s.id" class="stock-row">
          <span class="stock-row__name">{{ s.name }}</span>
          <span class="stock-row__stock">{{ s.current_stock }} {{ s.unit }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/authStore'
import api from '../services/api'

const auth = useAuthStore()
const loading = ref(true)
const orders = ref([])
const lowStock = ref([])
const activeTables = ref(0)
const revenue = ref(0)

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})

const todayDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const statCards = computed(() => [
  { icon: '📋', value: orders.value.length, label: 'Orders Today', iconBg: 'rgba(249,115,22,0.10)', iconColor: '#F97316' },
  { icon: '💰', value: '$'+revenue.value.toFixed(2), label: 'Revenue Today', iconBg: 'rgba(16,185,129,0.10)', iconColor: '#10B981' },
  { icon: '🪑', value: activeTables.value, label: 'Active Tables', iconBg: 'rgba(6,182,212,0.10)', iconColor: '#06B6D4' },
  { icon: '⚠️', value: lowStock.value.length, label: 'Low Stock', iconBg: 'rgba(244,63,94,0.10)', iconColor: '#F43F5E' },
])

const actions = [
  { icon: '🧾', label: 'New Order', to: '/app/pos' },
  { icon: '🍳', label: 'Kitchen View', to: '/app/kitchen' },
  { icon: '📈', label: 'View Analytics', to: '/app/analytics' },
]

const recentOrders = computed(() => orders.value.slice(0, 10))

function statusColor(s) {
  if (['completed','paid','closed'].includes(s)) return 'success'
  if (['pending','new'].includes(s)) return 'warning'
  if (['preparing'].includes(s)) return 'info'
  if (['cancelled','voided'].includes(s)) return 'danger'
  return 'neutral'
}

function formatTime(d) { return d ? new Date(d).toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }) : 'N/A' }

onMounted(async () => {
  const rid = auth.user?.restaurant_id
  if (!rid) { loading.value = false; return }

  try {
    const ordersRes = await api.get('/orders', { params: { restaurant_id: rid, per_page: 100 } })
    const ordersRaw = ordersRes.data?.data
    orders.value = Array.isArray(ordersRaw) ? ordersRaw : Array.isArray(ordersRaw?.data) ? ordersRaw.data : []
    revenue.value = orders.value.reduce((s, o) => ['completed','paid','closed'].includes(o.status) ? s + Number(o.total||0) : s, 0)
  } catch(e) { console.error('orders failed', e) }

  try {
    const stockRes = await api.get('/ingredients', { params: { restaurant_id: rid, low_stock: true, per_page: 100 } })
    const stockRaw = stockRes.data?.data
    lowStock.value = Array.isArray(stockRaw) ? stockRaw : Array.isArray(stockRaw?.data) ? stockRaw.data : []
  } catch(e) { console.error('ingredients failed', e) }

  try {
    const tablesRes = await api.get('/tables', { params: { restaurant_id: rid } })
    const tablesRaw = tablesRes.data?.data
    const tablesData = Array.isArray(tablesRaw) ? tablesRaw : Array.isArray(tablesRaw?.data) ? tablesRaw.data : []
    activeTables.value = tablesData.filter(t => t?.status === 'occupied').length
  } catch(e) { console.error('tables failed', e) }

  loading.value = false

  const obs = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting){e.target.classList.add('is-visible');obs.unobserve(e.target)} }), { threshold:0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => obs.observe(el))
})
</script>

<style scoped>
.dashboard{max-width:1400px}

.greeting{background:#111113;border:1px solid #27272A;border-left:3px solid #F97316;border-radius:1rem;padding:1.25rem 1.5rem;margin-bottom:1.5rem}
.greeting__text{font-size:1.5rem;font-weight:800;color:#FAFAFA}
.greeting__sub{font-size:0.8rem;color:#A1A1AA;margin-top:0.25rem}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.stat-card{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.5rem;transition:all 250ms ease}
.stat-card:hover{transform:translateY(-3px);border-color:rgba(249,115,22,0.3);box-shadow:0 8px 30px rgba(0,0,0,0.4)}
.stat-card__icon{width:48px;height:48px;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:0.75rem}
.stat-card__value{font-size:2rem;font-weight:800;color:#FAFAFA;line-height:1}
.stat-card__label{font-size:0.8rem;color:#A1A1AA;margin-top:0.25rem}

.quick-actions{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.action-card{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.25rem;cursor:pointer;transition:all 150ms ease;text-align:center}
.action-card:hover{border-color:rgba(249,115,22,0.3);background:rgba(249,115,22,0.04)}
.action-card__icon{font-size:2rem;margin-bottom:0.5rem}
.action-card__label{color:#FAFAFA;font-weight:600;font-size:0.9rem}

.section{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.5rem;margin-bottom:1.5rem}
.section__header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #27272A}
.section__title{font-size:1rem;font-weight:700;color:#FAFAFA}

.badge{display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600;background:rgba(249,115,22,0.12);color:#FB923C}
.badge--success{background:rgba(16,185,129,0.12);color:#10B981}
.badge--warning{background:rgba(245,158,11,0.12);color:#F59E0B}
.badge--info{background:rgba(6,182,212,0.12);color:#06B6D4}
.badge--danger{background:rgba(244,63,94,0.12);color:#F43F5E}
.badge--neutral{background:rgba(161,161,170,0.12);color:#A1A1AA}

.table-wrap{overflow-x:auto}
.table{width:100%;border-collapse:collapse}
.table th{background:#18181B;color:#52525B;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;padding:0.75rem 1rem;text-align:left;border-bottom:1px solid #27272A}
.table td{padding:0.75rem 1rem;border-bottom:1px solid #1C1C1F;color:#FAFAFA;font-size:0.875rem}
.table tr:hover td{background:#18181B}

.stock-list{display:flex;flex-direction:column}
.stock-row{display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid #1C1C1F;font-size:0.875rem}
.stock-row:last-child{border-bottom:none}
.stock-row__name{color:#FAFAFA;font-weight:500}
.stock-row__stock{color:#F43F5E;font-weight:600}

.loading-rows{display:flex;flex-direction:column;gap:0.5rem}
.shimmer-row{height:44px;background:linear-gradient(90deg,#1C1C1F 25%,#27272A 50%,#1C1C1F 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:0.5rem}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

.empty{text-align:center;padding:2rem;color:#52525B;font-size:0.9rem}

.animate-on-scroll{opacity:0;transform:translateY(20px);transition:opacity 0.5s ease,transform 0.5s ease}
.animate-on-scroll.is-visible{opacity:1;transform:translateY(0)}

@media(max-width:1024px){.stats-grid{grid-template-columns:repeat(2,1fr)}.quick-actions{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){.stats-grid{grid-template-columns:1fr}.quick-actions{grid-template-columns:1fr}}
</style>

<template>
  <div class="analytics">
    <div class="analytics__header animate-on-scroll">
      <div>
        <div class="analytics__title">Analytics</div>
        <div class="analytics__sub">Monitor your restaurant performance</div>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon stat-icon--orange">💰</div>
        <div class="stat-card__value">${{ totalRevenue.toFixed(2) }}</div>
        <div class="stat-card__label">Revenue</div>
      </div>
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon stat-icon--cyan">📋</div>
        <div class="stat-card__value">{{ totalOrders }}</div>
        <div class="stat-card__label">Orders</div>
      </div>
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon stat-icon--green">⏱️</div>
        <div class="stat-card__value">{{ avgPrepTime }}m</div>
        <div class="stat-card__label">Avg Prep</div>
      </div>
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon stat-icon--yellow">🪑</div>
        <div class="stat-card__value">{{ activeTables }}</div>
        <div class="stat-card__label">Active Tables</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const totalRevenue = ref(0)
const totalOrders = ref(0)
const avgPrepTime = ref(14)
const activeTables = ref(0)

onMounted(async () => {
  try {
    const [ordersRes, tablesRes] = await Promise.all([
      api.get('/orders', { params: { per_page: 100 } }),
      api.get('/tables'),
    ])
    const ordersRaw = ordersRes.data?.data
    const orders = Array.isArray(ordersRaw) ? ordersRaw : Array.isArray(ordersRaw?.data) ? ordersRaw.data : []
    totalOrders.value = orders.length
    totalRevenue.value = orders.reduce((s, o) => s + Number(o.total || 0), 0)
    const tablesRaw = tablesRes.data?.data
    const tablesData = Array.isArray(tablesRaw) ? tablesRaw : Array.isArray(tablesRaw?.data) ? tablesRaw.data : []
    activeTables.value = tablesData.filter(t => t.status === 'occupied').length
  } catch(e) {}

  const obs = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting){e.target.classList.add('is-visible');obs.unobserve(e.target)} }), { threshold:0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => obs.observe(el))
})
</script>

<style scoped>
.analytics{max-width:1400px}

.analytics__header{background:#111113;border:1px solid #27272A;border-left:3px solid #06B6D4;border-radius:1rem;padding:1.25rem 1.5rem;margin-bottom:1.5rem}
.analytics__title{font-size:1.5rem;font-weight:800;color:#FAFAFA}
.analytics__sub{font-size:0.8rem;color:#A1A1AA;margin-top:0.25rem}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.stat-card{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.5rem;transition:all 250ms ease}
.stat-card:hover{transform:translateY(-3px);border-color:rgba(249,115,22,0.3);box-shadow:0 8px 30px rgba(0,0,0,0.4)}
.stat-card__icon{width:48px;height:48px;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:0.75rem}
.stat-icon--orange{background:rgba(249,115,22,0.10);color:#F97316}
.stat-icon--cyan{background:rgba(6,182,212,0.10);color:#06B6D4}
.stat-icon--green{background:rgba(16,185,129,0.10);color:#10B981}
.stat-icon--yellow{background:rgba(245,158,11,0.10);color:#F59E0B}
.stat-card__value{font-size:2rem;font-weight:800;color:#FAFAFA;line-height:1}
.stat-card__label{font-size:0.8rem;color:#A1A1AA;margin-top:0.25rem}

.animate-on-scroll{opacity:0;transform:translateY(20px);transition:opacity 0.5s ease,transform 0.5s ease}
.animate-on-scroll.is-visible{opacity:1;transform:translateY(0)}

@media(max-width:1024px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){.stats-grid{grid-template-columns:1fr}}
</style>

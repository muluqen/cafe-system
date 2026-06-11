<template>
  <div class="pulse">
    <div class="pulse__hero animate-on-scroll">
      <div>
        <div class="pulse__title">Restaurant Pulse</div>
        <div class="pulse__sub">Compliments, complaints, and guest feedback</div>
      </div>
      <div class="pulse__avg">
        <div class="pulse__avg-label">Average Rating</div>
        <div class="pulse__avg-value">{{ averageRating }}</div>
        <div class="pulse__avg-count">{{ feedbackEntries.length }} notes collected</div>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon" style="background:rgba(16,185,129,0.10);color:#10B981">👍</div>
        <div class="stat-card__value">{{ complimentCount }}</div>
        <div class="stat-card__label">Compliments</div>
      </div>
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon" style="background:rgba(244,63,94,0.10);color:#F43F5E">👎</div>
        <div class="stat-card__value">{{ complaintCount }}</div>
        <div class="stat-card__label">Complaints</div>
      </div>
      <div class="stat-card animate-on-scroll">
        <div class="stat-card__icon" style="background:rgba(6,182,212,0.10);color:#06B6D4">💬</div>
        <div class="stat-card__value">{{ noteCount }}</div>
        <div class="stat-card__label">Chef Notes</div>
      </div>
    </div>

    <div class="section animate-on-scroll">
      <div class="section__header">
        <div class="section__title">Live Feed</div>
      </div>
      <div v-if="loading" class="empty">Loading feedback...</div>
      <div v-else-if="feedbackEntries.length" class="feed">
        <div v-for="entry in feedbackEntries" :key="entry.id" class="feed-card">
          <div class="feed-card__head">
            <span class="feed-card__name">{{ entry.customer_name || entry.customerName || 'Customer' }}</span>
            <span class="badge">{{ entry.rating }}/5</span>
          </div>
          <div class="feed-card__date">{{ formatDate(entry.created_at || entry.createdAt) }}</div>
          <div v-if="entry.compliment" class="feed-card__text"><strong>Compliment:</strong> {{ entry.compliment }}</div>
          <div v-if="entry.complaint" class="feed-card__text"><strong>Complaint:</strong> {{ entry.complaint }}</div>
          <div v-if="entry.note" class="feed-card__text"><strong>Message:</strong> {{ entry.note }}</div>
          <div v-if="entry.comment && !entry.compliment && !entry.complaint && !entry.note" class="feed-card__text"><strong>Message:</strong> {{ entry.comment }}</div>
          <div v-if="entry.tags?.length" class="feed-card__tags">
            <span v-for="tag in entry.tags" :key="tag" class="tag">{{ tag }}</span>
          </div>
        </div>
      </div>
      <div v-else class="empty">No feedback yet. Customer voice will appear here.</div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/authStore'
import api from '../services/api'

const auth = useAuthStore()
const feedbackEntries = ref([])
const loading = ref(true)

const complimentCount = computed(() => feedbackEntries.value.filter(e => e.compliment).length)
const complaintCount = computed(() => feedbackEntries.value.filter(e => e.complaint).length)
const noteCount = computed(() => feedbackEntries.value.filter(e => e.note).length)
const averageRating = computed(() => {
  if (!feedbackEntries.value.length) return 'N/A'
  const t = feedbackEntries.value.reduce((s, e) => s + Number(e.rating || 0), 0)
  return (t / feedbackEntries.value.length).toFixed(1) + '/5'
})

function formatDate(v) { return v ? new Date(v).toLocaleString() : '' }

async function loadFeedback() {
  try {
    const restaurantId = auth.user?.restaurant_id
    if (!restaurantId) return
    const res = await api.get('/feedback', { params: { restaurant_id: restaurantId } })
    const body = res?.data
    const items = body?.data?.data || body?.data || body
    feedbackEntries.value = Array.isArray(items) ? items : []
  } catch {
    feedbackEntries.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadFeedback()
  const obs = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting){e.target.classList.add('is-visible');obs.unobserve(e.target)} }), { threshold:0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => obs.observe(el))
})
</script>

<style scoped>
.pulse{max-width:1400px}

.pulse__hero{background:#111113;border:1px solid #27272A;border-left:3px solid #F97316;border-radius:1rem;padding:1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;gap:1.5rem}
.pulse__title{font-size:1.5rem;font-weight:800;color:#FAFAFA}
.pulse__sub{font-size:0.8rem;color:#A1A1AA;margin-top:0.25rem}
.pulse__avg{text-align:center}
.pulse__avg-label{font-size:0.65rem;color:#52525B;text-transform:uppercase;letter-spacing:0.08em;font-weight:700}
.pulse__avg-value{font-size:2rem;font-weight:800;color:#FAFAFA;line-height:1.2}
.pulse__avg-count{font-size:0.75rem;color:#A1A1AA}

.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.stat-card{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.5rem;transition:all 250ms ease}
.stat-card:hover{transform:translateY(-3px);border-color:rgba(249,115,22,0.3);box-shadow:0 8px 30px rgba(0,0,0,0.4)}
.stat-card__icon{width:48px;height:48px;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:0.75rem}
.stat-card__value{font-size:2rem;font-weight:800;color:#FAFAFA;line-height:1}
.stat-card__label{font-size:0.8rem;color:#A1A1AA;margin-top:0.25rem}

.section{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.5rem;margin-bottom:1.5rem}
.section__header{margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #27272A}
.section__title{font-size:1rem;font-weight:700;color:#FAFAFA}

.feed{display:flex;flex-direction:column;gap:0.75rem}
.feed-card{background:#18181B;border:1px solid #27272A;border-radius:0.75rem;padding:1.25rem;transition:border-color 150ms ease}
.feed-card:hover{border-color:rgba(249,115,22,0.2)}
.feed-card__head{display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem}
.feed-card__name{font-weight:600;color:#FAFAFA}
.feed-card__date{font-size:0.75rem;color:#52525B;margin-bottom:0.75rem}
.feed-card__text{font-size:0.875rem;color:#A1A1AA;margin-bottom:0.375rem;line-height:1.5}
.feed-card__text strong{color:#FAFAFA}
.feed-card__tags{display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.5rem}
.tag{padding:0.2rem 0.5rem;background:rgba(6,182,212,0.10);color:#06B6D4;border-radius:9999px;font-size:0.7rem;font-weight:600}

.badge{display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600;background:rgba(249,115,22,0.12);color:#FB923C}
.empty{text-align:center;padding:2rem;color:#52525B;font-size:0.9rem}

.animate-on-scroll{opacity:0;transform:translateY(20px);transition:opacity 0.5s ease,transform 0.5s ease}
.animate-on-scroll.is-visible{opacity:1;transform:translateY(0)}

@media(max-width:768px){.pulse__hero{flex-direction:column;align-items:flex-start}.stats-grid{grid-template-columns:1fr}}
</style>

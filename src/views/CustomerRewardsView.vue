<template>
  <div class="customer-rewards">
    <PageHeader title="My Rewards" />

    <!-- Points Card -->
    <div class="points-card">
      <div class="points-card__value">{{ totalPoints }}</div>
      <div class="points-card__label">Points</div>
      <div class="points-card__subtitle">Earn 10 points per order</div>
      <div class="points-card__progress">
        <div class="progress-bar">
          <div class="progress-bar__fill" :style="{ width: progressPercent + '%' }" />
        </div>
        <div class="progress-labels">
          <span :class="['tier-label', { 'tier-label--active': totalPoints >= 0 }]">Bronze</span>
          <span :class="['tier-label', { 'tier-label--active': totalPoints >= 500 }]">Silver</span>
          <span :class="['tier-label', { 'tier-label--active': totalPoints >= 1500 }]">Gold</span>
        </div>
      </div>
      <BaseBadge :variant="tierVariant">{{ currentTier }} Member</BaseBadge>
    </div>

    <!-- How to Earn -->
    <div class="section">
      <h3 class="section__title">How to earn points</h3>
      <div class="earn-list">
        <div class="earn-item">
          <span class="earn-item__icon">📦</span>
          <div class="earn-item__info">
            <span class="earn-item__action">Place an order</span>
            <span class="earn-item__points">+10 points</span>
          </div>
        </div>
        <div class="earn-item">
          <span class="earn-item__icon">💬</span>
          <div class="earn-item__info">
            <span class="earn-item__action">Leave feedback</span>
            <span class="earn-item__points">+5 points</span>
          </div>
        </div>
        <div class="earn-item">
          <span class="earn-item__icon">👥</span>
          <div class="earn-item__info">
            <span class="earn-item__action">Refer a friend</span>
            <span class="earn-item__points">+20 points</span>
          </div>
        </div>
      </div>
    </div>

    <p class="note">Points are calculated from your order history (thesis demo).</p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import customerService from "../services/customerService";
import PageHeader from "../components/ui/PageHeader.vue";
import BaseBadge from "../components/ui/BaseBadge.vue";

const orderCount = ref(0);

const totalPoints = computed(() => orderCount.value * 10);

const currentTier = computed(() => {
  if (totalPoints.value >= 1500) return "Gold";
  if (totalPoints.value >= 500) return "Silver";
  return "Bronze";
});

const tierVariant = computed(() => {
  if (totalPoints.value >= 1500) return "warning";
  if (totalPoints.value >= 500) return "info";
  return "neutral";
});

const progressPercent = computed(() => {
  if (totalPoints.value >= 1500) return 100;
  if (totalPoints.value >= 500) return ((totalPoints.value - 500) / 1000) * 100;
  return (totalPoints.value / 500) * 100;
});

onMounted(async () => {
  try {
    const res = await customerService.getMyOrders();
    const body = res.data;
    const raw = body?.data;
    const orders = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
    orderCount.value = orders.length;
  } catch {}

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el)
  })
});
</script>

<style scoped>
.points-card {
  background: linear-gradient(135deg, rgba(255,60,172,0.10), rgba(6,182,212,0.08));
  border: 1px solid rgba(255,60,172,0.2);
  border-radius: var(--radius-2xl);
  padding: var(--space-8);
  text-align: center;
  color: white;
  margin-bottom: var(--space-8);
}

.points-card__value {
  font-size: var(--text-4xl);
  font-weight: 800;
  color: var(--color-primary-light);
}

.points-card__label { font-size: var(--text-lg); opacity: 0.9; margin-bottom: var(--space-1); }
.points-card__subtitle { font-size: var(--text-sm); opacity: 0.7; margin-bottom: var(--space-6); }

.points-card__progress { margin-bottom: var(--space-4); }
.progress-bar { height: 6px; background: rgba(255,255,255,0.1); border-radius: var(--radius-full); margin-bottom: var(--space-2); }
.progress-bar__fill {
  height: 100%;
  background: linear-gradient(90deg, #FF3CAC, #FF6FC8, #06B6D4);
  background-size: 200% 100%;
  border-radius: var(--radius-full);
  transition: width 0.5s;
  animation: shimmer-progress 2s linear infinite;
}

@keyframes shimmer-progress {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.progress-labels { display: flex; justify-content: space-between; font-size: var(--text-xs); opacity: 0.8; }

.section { margin-bottom: var(--space-6); }
.section__title { margin: 0 0 var(--space-4); font-size: var(--text-lg); font-weight: var(--font-semibold); color: var(--color-text-primary); }

.earn-list { display: flex; flex-direction: column; gap: var(--space-3); }
.earn-item { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-4); background: var(--color-bg-elevated); border: 1px solid var(--color-border); border-radius: var(--radius-lg); }
.earn-item__icon { font-size: 1.5rem; }
.earn-item__info { flex: 1; display: flex; justify-content: space-between; }
.earn-item__action { font-weight: var(--font-medium); color: var(--color-text-primary); }
.earn-item__points { font-weight: var(--font-bold); color: var(--color-accent); }

.note { font-size: var(--text-sm); color: var(--color-text-muted); text-align: center; }
</style>

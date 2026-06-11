<template>
  <div :class="['stat-card', { 'stat-card--hoverable': hoverable }]">
    <div class="stat-card__header">
      <span class="stat-card__title">{{ title }}</span>
      <span v-if="icon" class="stat-card__icon">{{ icon }}</span>
    </div>
    <div class="stat-card__value">{{ formattedValue }}</div>
    <div v-if="change !== null" class="stat-card__footer">
      <span :class="['stat-card__change', changeClass]">
        <svg v-if="isPositive" width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M6 9V3M3 5l3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg v-else-if="isNegative" width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M6 3v6M3 7l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        {{ Math.abs(change) }}%
      </span>
      <span v-if="changeLabel" class="stat-card__change-label">{{ changeLabel }}</span>
    </div>
    <div v-if="$slots.footer" class="stat-card__footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], required: true },
  change: { type: Number, default: null },
  changeLabel: { type: String, default: '' },
  icon: { type: String, default: '' },
  hoverable: { type: Boolean, default: false },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
});

const formattedValue = computed(() => {
  if (typeof props.value === 'string') return `${props.prefix}${props.value}${props.suffix}`;
  const formatted = props.value.toLocaleString();
  return `${props.prefix}${formatted}${props.suffix}`;
});

const isPositive = computed(() => props.change > 0);
const isNegative = computed(() => props.change < 0);
const changeClass = computed(() => {
  if (isPositive.value) return 'stat-card__change--positive';
  if (isNegative.value) return 'stat-card__change--negative';
  return 'stat-card__change--neutral';
});
</script>

<style scoped>
.stat-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  padding: var(--space-5) var(--space-6);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
}

.stat-card--hoverable:hover {
  transform: translateY(-3px);
  border-color: rgba(249,115,22,0.2);
  box-shadow: var(--shadow-lg);
}

.stat-card__header { display: flex; align-items: center; justify-content: space-between; }
.stat-card__title { font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--color-text-secondary); }
.stat-card__icon { font-size: var(--text-xl); color: var(--color-text-muted); }
.stat-card__value { font-size: var(--text-3xl); font-weight: 800; color: var(--color-text-primary); line-height: 1.1; }
.stat-card__footer { display: flex; align-items: center; gap: var(--space-2); }
.stat-card__change { display: inline-flex; align-items: center; gap: var(--space-1); font-size: var(--text-sm); font-weight: var(--font-semibold); }
.stat-card__change--positive { color: var(--color-success); }
.stat-card__change--negative { color: var(--color-danger); }
.stat-card__change--neutral { color: var(--color-text-secondary); }
.stat-card__change-label { font-size: var(--text-sm); color: var(--color-text-muted); }
</style>

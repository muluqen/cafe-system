<template>
  <div :class="['base-card', `base-card--${padding}`, { 'base-card--hoverable': hoverable, 'base-card--bordered': bordered }]">
    <div v-if="title || $slots.header" class="base-card__header">
      <slot name="header">
        <div>
          <h3 v-if="title" class="base-card__title">{{ title }}</h3>
          <p v-if="subtitle" class="base-card__subtitle">{{ subtitle }}</p>
        </div>
      </slot>
    </div>
    <div class="base-card__body"><slot /></div>
    <div v-if="$slots.footer" class="base-card__footer"><slot name="footer" /></div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  padding: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg'].includes(v) },
  hoverable: { type: Boolean, default: false },
  bordered: { type: Boolean, default: true },
});
</script>

<style scoped>
.base-card {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: all var(--transition-base);
}
.base-card--bordered { border: 1px solid var(--color-border); }
.base-card--hoverable:hover {
  transform: translateY(-2px);
  border-color: rgba(249,115,22,0.25);
  box-shadow: var(--shadow-md), var(--shadow-warm);
}
.base-card__header { display: flex; justify-content: space-between; align-items: center; gap: var(--space-4); padding: var(--space-5) var(--space-6); border-bottom: 1px solid var(--color-border); }
.base-card__title { margin: 0; font-size: var(--text-lg); font-weight: var(--font-semibold); color: var(--color-text-primary); }
.base-card__subtitle { margin: var(--space-1) 0 0; font-size: var(--text-sm); color: var(--color-text-secondary); }
.base-card--sm .base-card__body { padding: var(--space-4); }
.base-card--md .base-card__body { padding: var(--space-6); }
.base-card--lg .base-card__body { padding: var(--space-8); }
.base-card__footer { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-4) var(--space-6); border-top: 1px solid var(--color-border); background: var(--color-bg-overlay); }
</style>

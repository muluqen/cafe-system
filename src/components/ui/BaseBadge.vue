<template>
  <span :class="['base-badge', `base-badge--${variant}`, `base-badge--${size}`]">
    <span v-if="dot" class="base-badge__dot"></span>
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'neutral', validator: v => ['success', 'danger', 'warning', 'info', 'neutral'].includes(v) },
  size: { type: String, default: 'md', validator: v => ['sm', 'md'].includes(v) },
  dot: { type: Boolean, default: false },
});
</script>

<style scoped>
.base-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  border-radius: var(--radius-full);
  font-family: var(--font-sans);
  font-weight: var(--font-semibold);
  white-space: nowrap;
  line-height: 1;
}

/* Sizes */
.base-badge--sm { padding: 2px 8px; font-size: var(--text-xs); }
.base-badge--md { padding: 4px 12px; font-size: var(--text-sm); }

/* Variants */
.base-badge--success { background: var(--color-success-light); color: var(--color-success); }
.base-badge--danger { background: var(--color-danger-light); color: var(--color-danger); }
.base-badge--warning { background: var(--color-warning-light); color: var(--color-warning); }
.base-badge--info { background: var(--color-info-light); color: var(--color-info); }
.base-badge--neutral { background: var(--color-bg-muted); color: var(--color-text-secondary); }

/* Dot */
.base-badge__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
</style>

<template>
  <button
    :class="['base-btn', `base-btn--${variant}`, `base-btn--${size}`, { 'base-btn--loading': loading }]"
    :disabled="disabled || loading"
    @click="$emit('click', $event)"
  >
    <svg v-if="loading" class="base-btn__spinner" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
        <animate attributeName="stroke-dashoffset" values="32;0" dur="0.8s" repeatCount="indefinite" />
      </circle>
    </svg>
    <span v-if="icon && !loading" class="base-btn__icon">{{ icon }}</span>
    <slot />
  </button>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'primary', validator: v => ['primary', 'secondary', 'ghost', 'danger', 'success', 'accent'].includes(v) },
  size: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg'].includes(v) },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  icon: { type: String, default: '' },
});

defineEmits(['click']);
</script>

<style scoped>
.base-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  font-family: var(--font-sans);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
  white-space: nowrap;
  line-height: 1;
  position: relative;
}

.base-btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

.base-btn--sm { padding: 0.375rem 0.75rem; font-size: var(--text-sm); }
.base-btn--md { padding: 0.625rem 1.25rem; font-size: var(--text-base); }
.base-btn--lg { padding: 0.875rem 1.75rem; font-size: var(--text-lg); }

.base-btn--primary {
  background: var(--color-warm);
  color: white;
  font-weight: 700;
  box-shadow: var(--shadow-warm);
}
.base-btn--primary:hover:not(:disabled) {
  box-shadow: var(--shadow-warm-lg);
  transform: scale(1.02);
}

.base-btn--secondary {
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-text-primary);
}
.base-btn--secondary:hover:not(:disabled) {
  border-color: var(--color-warm);
  color: var(--color-warm);
}

.base-btn--ghost {
  background: transparent;
  color: var(--color-text-secondary);
}
.base-btn--ghost:hover:not(:disabled) {
  color: var(--color-text-primary);
  background: var(--color-bg-subtle);
}

.base-btn--danger {
  background: var(--color-danger);
  color: white;
}
.base-btn--danger:hover:not(:disabled) {
  box-shadow: 0 0 20px rgba(244,63,94,0.4);
}

.base-btn--success {
  background: var(--color-success);
  color: white;
}
.base-btn--success:hover:not(:disabled) {
  box-shadow: 0 0 20px rgba(16,185,129,0.4);
}

.base-btn--accent {
  background: var(--color-cool);
  color: #09090B;
  font-weight: 700;
  box-shadow: var(--shadow-cool);
}
.base-btn--accent:hover:not(:disabled) {
  box-shadow: var(--shadow-cool-lg);
  transform: scale(1.02);
}

.base-btn--loading { opacity: 0.7; }
.base-btn__spinner { width: 1em; height: 1em; animation: spin 0.6s linear infinite; }
.base-btn__icon { font-size: 1.1em; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="base-modal" @click.self="closable && $emit('update:modelValue', false)">
        <div :class="['base-modal__card', `base-modal--${size}`]">
          <div v-if="title || closable" class="base-modal__header">
            <h3 v-if="title" class="base-modal__title">{{ title }}</h3>
            <button v-if="closable" class="base-modal__close" @click="$emit('update:modelValue', false)">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <div class="base-modal__body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="base-modal__footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg', 'xl'].includes(v) },
  closable: { type: Boolean, default: true },
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.base-modal {
  position: fixed;
  inset: 0;
  z-index: var(--z-modal);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-4);
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(8px);
  overflow-y: auto;
}

.base-modal__card {
  width: 100%;
  max-height: calc(100vh - var(--space-8));
  background: var(--color-bg-overlay);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-2xl);
  box-shadow: var(--shadow-xl);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.base-modal--sm { max-width: 400px; }
.base-modal--md { max-width: 560px; }
.base-modal--lg { max-width: 720px; }
.base-modal--xl { max-width: 900px; }

.base-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  padding: var(--space-6);
  border-bottom: 1px solid var(--color-border);
}

.base-modal__title {
  margin: 0;
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.base-modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius-md);
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.base-modal__close:hover {
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
}

.base-modal__body {
  padding: var(--space-6);
  overflow-y: auto;
  flex: 1;
}

.base-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-3);
  padding: var(--space-4) var(--space-6);
  border-top: 1px solid var(--color-border);
  background: var(--color-bg-overlay);
}

/* Transitions */
.modal-enter-active { transition: opacity 200ms cubic-bezier(0.4, 0, 0.2, 1); }
.modal-leave-active { transition: opacity 150ms ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

.modal-enter-active .base-modal__card {
  transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1), opacity 200ms ease;
}
.modal-leave-active .base-modal__card {
  transition: transform 150ms ease, opacity 150ms ease;
}
.modal-enter-from .base-modal__card {
  opacity: 0;
  transform: scale(0.95);
}
.modal-leave-to .base-modal__card {
  opacity: 0;
  transform: scale(0.97);
}
</style>

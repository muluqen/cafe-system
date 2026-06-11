<template>
  <div class="toast-container">
    <TransitionGroup name="toast" tag="div" class="toast-stack">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="['toast', `toast--${t.type}`]"
        :style="{ '--toast-duration': (t.duration || 2500) + 'ms' }"
        @click="remove(t.id)"
      >
        <div class="toast__icon-wrap">
          <template v-if="t.type === 'success'">
            <svg class="toast__icon toast__icon--success" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
              <path class="toast__check" d="M7 12.5l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </template>
          <template v-else-if="t.type === 'error'">
            <svg class="toast__icon toast__icon--error" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
              <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </template>
          <template v-else-if="t.type === 'warning'">
            <svg class="toast__icon toast__icon--warning" viewBox="0 0 24 24" fill="none">
              <path d="M12 2L2 20h20L12 2z" fill="currentColor" opacity="0.15"/>
              <path d="M12 2L2 20h20L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M12 9v5M12 16v.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </template>
          <template v-else>
            <svg class="toast__icon toast__icon--info" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
              <path d="M12 7v1M12 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </template>
        </div>

        <div class="toast__content">
          <span class="toast__message">{{ t.message }}</span>
          <button
            v-if="t.action"
            class="toast__action"
            @click.stop="handleAction(t)"
          >
            {{ t.action.label }}
          </button>
          <div class="toast__progress">
            <div class="toast__progress-bar"></div>
          </div>
        </div>

        <button class="toast__close" @click.stop="remove(t.id)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M10.5 3.5L3.5 10.5M3.5 3.5l7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '../../composables/useToast.js';

const { toasts, remove } = useToast();

function handleAction(toast) {
  if (toast.action?.onClick) {
    toast.action.onClick();
  }
  remove(toast.id);
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  pointer-events: none;
}

.toast-stack {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-width: 400px;
  pointer-events: auto;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #18181B 0%, #1C1C1F 100%);
  border: 1px solid #27272A;
  border-radius: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  position: relative;
}

.toast:hover {
  transform: translateX(-4px);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
}

/* Success glow */
.toast--success {
  border-color: rgba(16, 185, 129, 0.4);
  background: linear-gradient(135deg, #0a1a12 0%, #18181B 50%, #1C1C1F 100%);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(16, 185, 129, 0.15), 0 0 0 1px rgba(16, 185, 129, 0.2) inset;
}

.toast--success::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #10B981, #34D399);
  border-radius: 4px 0 0 4px;
}

/* Error glow */
.toast--error {
  border-color: rgba(244, 63, 94, 0.4);
  background: linear-gradient(135deg, #1a0a0e 0%, #18181B 50%, #1C1C1F 100%);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(244, 63, 94, 0.15), 0 0 0 1px rgba(244, 63, 94, 0.2) inset;
}

.toast--error::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #F43F5E, #FB7185);
  border-radius: 4px 0 0 4px;
}

/* Warning glow */
.toast--warning {
  border-color: rgba(245, 158, 11, 0.4);
  background: linear-gradient(135deg, #1a1508 0%, #18181B 50%, #1C1C1F 100%);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(245, 158, 11, 0.15), 0 0 0 1px rgba(245, 158, 11, 0.2) inset;
}

.toast--warning::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #F59E0B, #FBBF24);
  border-radius: 4px 0 0 4px;
}

/* Info glow */
.toast--info {
  border-color: rgba(6, 182, 212, 0.4);
  background: linear-gradient(135deg, #0a1518 0%, #18181B 50%, #1C1C1F 100%);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(6, 182, 212, 0.15), 0 0 0 1px rgba(6, 182, 212, 0.2) inset;
}

.toast--info::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #06B6D4, #22D3EE);
  border-radius: 4px 0 0 4px;
}

.toast__icon-wrap {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast__icon {
  width: 28px;
  height: 28px;
}

.toast__icon--success {
  color: #10B981;
  filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.5));
}

.toast__icon--error {
  color: #F43F5E;
  filter: drop-shadow(0 0 8px rgba(244, 63, 94, 0.5));
}

.toast__icon--warning {
  color: #F59E0B;
  filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.5));
}

.toast__icon--info {
  color: #06B6D4;
  filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.5));
}

/* Animated checkmark for success */
.toast--success .toast__check {
  stroke-dasharray: 20;
  stroke-dashoffset: 20;
  animation: drawCheck 0.4s ease-out 0.2s forwards;
}

@keyframes drawCheck {
  to {
    stroke-dashoffset: 0;
  }
}

.toast__content {
  flex: 1;
  min-width: 0;
}

.toast__message {
  font-size: 0.875rem;
  font-weight: 600;
  color: #FAFAFA;
  line-height: 1.4;
  letter-spacing: -0.01em;
}

.toast__action {
  margin-top: 0.5rem;
  padding: 0.35rem 0.75rem;
  border: none;
  border-radius: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  color: #FAFAFA;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  display: inline-block;
}

.toast--success .toast__action {
  background: rgba(16, 185, 129, 0.2);
  color: #34D399;
}

.toast--success .toast__action:hover {
  background: rgba(16, 185, 129, 0.35);
}

.toast--info .toast__action {
  background: rgba(6, 182, 212, 0.2);
  color: #22D3EE;
}

.toast--info .toast__action:hover {
  background: rgba(6, 182, 212, 0.35);
}

.toast__progress {
  margin-top: 0.5rem;
  height: 3px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  overflow: hidden;
}

.toast__progress-bar {
  height: 100%;
  border-radius: 9999px;
  animation: progressShrink var(--toast-duration, 2500ms) linear forwards;
}

.toast--success .toast__progress-bar {
  background: linear-gradient(90deg, #10B981, #34D399);
  width: 100%;
}

.toast--error .toast__progress-bar {
  background: linear-gradient(90deg, #F43F5E, #FB7185);
  width: 100%;
}

.toast--warning .toast__progress-bar {
  background: linear-gradient(90deg, #F59E0B, #FBBF24);
  width: 100%;
}

.toast--info .toast__progress-bar {
  background: linear-gradient(90deg, #06B6D4, #22D3EE);
  width: 100%;
}

@keyframes progressShrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.toast__close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  color: #52525B;
  cursor: pointer;
  border-radius: 0.375rem;
  transition: all 0.15s ease;
}

.toast__close:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #A1A1AA;
}

/* Transitions */
.toast-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 1, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.8);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.8);
}

.toast-move {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

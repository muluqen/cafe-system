<template>
  <div class="skeleton">
    <template v-if="type === 'text'">
      <div v-for="i in lines" :key="i" class="skeleton__line" :style="{ width: i === lines ? '60%' : '100%' }"></div>
    </template>
    <template v-else-if="type === 'card'">
      <div class="skeleton__card">
        <div class="skeleton__card-image"></div>
        <div class="skeleton__card-body">
          <div class="skeleton__line" style="width: 70%"></div>
          <div class="skeleton__line" style="width: 50%"></div>
        </div>
      </div>
    </template>
    <template v-else-if="type === 'table'">
      <div v-for="i in lines" :key="i" class="skeleton__table-row">
        <div class="skeleton__table-cell"></div>
        <div class="skeleton__table-cell"></div>
        <div class="skeleton__table-cell"></div>
      </div>
    </template>
    <template v-else-if="type === 'avatar'">
      <div class="skeleton__avatar"></div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  type: { type: String, default: 'text', validator: v => ['text', 'card', 'table', 'avatar'].includes(v) },
  lines: { type: Number, default: 3 },
});
</script>

<style scoped>
.skeleton {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.skeleton__line {
  height: 14px;
  border-radius: var(--radius-sm);
  background: linear-gradient(90deg, var(--color-bg-subtle) 25%, var(--color-bg-muted) 50%, var(--color-bg-subtle) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton--card { gap: 0; }

.skeleton__card {
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.skeleton__card-image {
  height: 140px;
  background: linear-gradient(90deg, var(--color-bg-subtle) 25%, var(--color-bg-muted) 50%, var(--color-bg-subtle) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton__card-body {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-4);
  background: var(--color-bg-elevated);
}

.skeleton__table-row {
  display: flex;
  gap: var(--space-4);
  padding: var(--space-3) 0;
  border-bottom: 1px solid var(--color-border-subtle);
}

.skeleton__table-cell {
  flex: 1;
  height: 14px;
  border-radius: var(--radius-sm);
  background: linear-gradient(90deg, var(--color-bg-subtle) 25%, var(--color-bg-muted) 50%, var(--color-bg-subtle) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton__avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(90deg, var(--color-bg-subtle) 25%, var(--color-bg-muted) 50%, var(--color-bg-subtle) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>

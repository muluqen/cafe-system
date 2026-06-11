<template>
  <div class="page-header">
    <div class="page-header__left">
      <div v-if="$slots.breadcrumbs" class="page-header__breadcrumbs">
        <slot name="breadcrumbs" />
      </div>
      <h1 class="page-header__title">{{ title }}</h1>
      <p v-if="subtitle" class="page-header__subtitle">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.right" class="page-header__right">
      <slot name="right" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  padding-bottom: var(--space-6);
  margin-bottom: var(--space-6);
  border-bottom: 1px solid var(--color-border);
}

.page-header__left {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.page-header__breadcrumbs {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.page-header__title {
  font-size: var(--text-3xl);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
  line-height: 1.2;
}

.page-header__subtitle {
  font-size: var(--text-base);
  color: var(--color-text-secondary);
  margin: 0;
}

.page-header__right {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}
</style>

<template>
  <div class="base-table-wrapper">
    <table class="base-table">
      <thead>
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            :class="{ 'base-table__th--sortable': col.sortable }"
            @click="col.sortable && $emit('sort', col.key)"
          >
            <span class="base-table__th-content">
              {{ col.label }}
              <span v-if="col.sortable" class="base-table__sort-icon">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <path d="M7 3v8M4 6l3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </span>
          </th>
        </tr>
      </thead>
      <tbody v-if="loading">
        <tr v-for="i in 5" :key="`skeleton-${i}`" class="base-table__skeleton-row">
          <td v-for="col in columns" :key="col.key">
            <div class="base-table__skeleton-cell"></div>
          </td>
        </tr>
      </tbody>
      <tbody v-else-if="rows.length === 0">
        <tr>
          <td :colspan="columns.length" class="base-table__empty">
            <div class="base-table__empty-content">
              <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <circle cx="24" cy="24" r="22" stroke="var(--color-bg-muted)" stroke-width="2"/>
                <path d="M16 20h16M16 28h10" stroke="var(--color-bg-muted)" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <p>{{ emptyMessage }}</p>
            </div>
          </td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr v-for="(row, idx) in rows" :key="row.id ?? idx" :class="{ 'base-table__row--striped': idx % 2 === 1 }">
          <slot name="row" :row="row">
            <td v-for="col in columns" :key="col.key">{{ row[col.key] }}</td>
          </slot>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  emptyMessage: { type: String, default: 'No data found' },
});

defineEmits(['sort']);
</script>

<style scoped>
.base-table-wrapper {
  width: 100%;
  overflow-x: auto;
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}

.base-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--text-sm);
}

.base-table thead {
  background: var(--color-bg-overlay);
  border-bottom: 1px solid var(--color-border);
}

.base-table th {
  padding: var(--space-3) var(--space-4);
  text-align: left;
  font-weight: var(--font-semibold);
  color: var(--color-text-muted);
  font-size: var(--text-xs);
  letter-spacing: 0.06em;
  text-transform: uppercase;
  white-space: nowrap;
}

.base-table__th--sortable { cursor: pointer; user-select: none; }
.base-table__th--sortable:hover { color: var(--color-cool); }

.base-table__th-content {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
}

.base-table__sort-icon { opacity: 0.4; }
.base-table__th--sortable:hover .base-table__sort-icon { opacity: 1; color: var(--color-cool); }

.base-table td {
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--color-border-subtle);
  color: var(--color-text-primary);
}

.base-table tbody tr:hover { background: var(--color-bg-subtle); }
.base-table__row--striped { background: var(--color-bg-elevated); }

.base-table__empty {
  text-align: center;
  padding: var(--space-12) var(--space-4) !important;
}

.base-table__empty-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  color: var(--color-text-muted);
}

.base-table__empty-content p { margin: 0; font-size: var(--text-sm); }

/* Skeleton loading */
.base-table__skeleton-cell {
  height: 16px;
  border-radius: var(--radius-sm);
  background: linear-gradient(90deg, var(--color-bg-subtle) 25%, var(--color-bg-muted) 50%, var(--color-bg-subtle) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>

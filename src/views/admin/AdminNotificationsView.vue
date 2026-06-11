<template>
  <div class="admin-notifications">
    <PageHeader title="Notifications">
      <template #right>
        <BaseButton variant="ghost" size="sm" @click="markAllRead">
          Mark all read
        </BaseButton>
      </template>
    </PageHeader>

    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="text" :lines="5" />
    </div>

    <div v-else-if="notifications.length === 0" class="empty-state">
      <p>No notifications yet.</p>
    </div>

    <div v-else class="notification-list">
      <div
        v-for="n in notifications"
        :key="n.id"
        :class="['notification-item', { 'notification-item--unread': !n.read_at }]"
        @click="handleRead(n)"
      >
        <span :class="['notification-item__dot', `notification-item__dot--${n.type}`]" />
        <div class="notification-item__content">
          <div class="notification-item__title">{{ n.title }}</div>
          <div class="notification-item__body">{{ n.body }}</div>
        </div>
        <span class="notification-item__time">{{ timeAgo(n.created_at) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import adminService from "../../services/adminService";
import { useToast } from "../../composables/useToast";
import PageHeader from "../../components/ui/PageHeader.vue";
import BaseButton from "../../components/ui/BaseButton.vue";
import SkeletonLoader from "../../components/ui/SkeletonLoader.vue";

const toast = useToast();
const notifications = ref([]);
const loading = ref(true);

function timeAgo(date) {
  if (!date) return "";
  const seconds = Math.floor((new Date() - new Date(date)) / 1000);
  if (seconds < 60) return "just now";
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

async function fetchNotifications() {
  loading.value = true;
  try {
    const { data } = await adminService.getNotifications();
    const body = data?.data || data;
    notifications.value = Array.isArray(body) ? body : body?.data || [];
  } catch (e) {
    toast.error("Failed to load notifications");
  } finally {
    loading.value = false;
  }
}

async function handleRead(n) {
  if (n.read_at) return;
  try {
    await adminService.markNotificationRead(n.id);
    n.read_at = new Date().toISOString();
  } catch {}
}

async function markAllRead() {
  try {
    const unread = notifications.value.filter(n => !n.read_at);
    await Promise.all(unread.map(n => adminService.markNotificationRead(n.id)));
    unread.forEach(n => { n.read_at = new Date().toISOString(); });
    toast.success("All notifications marked as read");
  } catch {
    toast.error("Failed to mark all as read");
  }
}

onMounted(fetchNotifications);
</script>

<style scoped>
.notification-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: background var(--transition-fast);
}

.notification-item:hover {
  background: var(--color-bg-subtle);
}

.notification-item--unread {
  background: var(--color-bg-overlay);
  border-color: var(--color-primary-glow);
}

.notification-item__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-top: 5px;
  flex-shrink: 0;
  background: var(--color-text-muted);
}

.notification-item__dot--restaurant_approved { background: var(--color-success); }
.notification-item__dot--restaurant_rejected { background: var(--color-danger); }
.notification-item__dot--new_registration { background: var(--color-warning); }
.notification-item__dot--restaurant_suspended { background: var(--color-danger); }
.notification-item__dot--restaurant_reactivated { background: var(--color-success); }

.notification-item__content { flex: 1; }

.notification-item__title {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
  margin-bottom: var(--space-1);
}

.notification-item__body {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.notification-item__time {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  white-space: nowrap;
}

.empty-state {
  text-align: center;
  padding: var(--space-12);
  color: var(--color-text-muted);
}

.loading-state {
  padding: var(--space-8);
}
</style>

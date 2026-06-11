<template>
  <div class="admin-dashboard">
    <PageHeader title="Platform Overview" :subtitle="todayDate" />

    <!-- Stats Grid -->
    <div class="stats-grid stagger-children">
      <StatCard
        title="Total Restaurants"
        :value="stats.total_restaurants || 0"
        :icon="'🏪'"
        hoverable
        class="animate-on-scroll"
      />
      <StatCard
        title="Total Users"
        :value="stats.total_users || 0"
        :icon="'👥'"
        hoverable
        class="animate-on-scroll"
      />
      <StatCard
        title="Orders Today"
        :value="stats.total_orders_today || 0"
        :icon="'📦'"
        hoverable
        class="animate-on-scroll"
      />
      <StatCard
        title="Pending Approvals"
        :value="stats.pending_restaurants || 0"
        :icon="'⏳'"
        hoverable
        class="animate-on-scroll"
        @click="$router.push('/admin/restaurants/pending')"
      />
    </div>

    <!-- Pending Restaurants -->
    <div v-if="pendingRestaurants.length" class="section pending-section">
      <div class="section__header">
        <h2 class="section__title">
          Awaiting Approval
          <BaseBadge variant="warning">{{ pendingRestaurants.length }}</BaseBadge>
        </h2>
      </div>

      <div class="pending-grid">
        <BaseCard v-for="r in pendingRestaurants" :key="r.id" padding="md">
          <div class="pending-card">
            <div class="pending-card__info">
              <h3 class="pending-card__name">{{ r.name }}</h3>
              <p class="pending-card__owner">
                Owner: {{ r.users?.[0]?.name || 'Unknown' }}
                <span v-if="r.users?.[0]?.email">({{ r.users[0].email }})</span>
              </p>
              <p class="pending-card__date">Registered {{ timeAgo(r.created_at) }}</p>
            </div>
            <div class="pending-card__actions">
              <BaseButton variant="success" size="sm" @click="handleApprove(r)">
                ✓ Approve
              </BaseButton>
              <BaseButton variant="danger" size="sm" @click="openRejectModal(r)">
                ✗ Reject
              </BaseButton>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Recent Notifications -->
    <div class="section">
      <div class="section__header">
        <h2 class="section__title">Recent Notifications</h2>
        <RouterLink to="/admin/notifications" class="section__link">View All</RouterLink>
      </div>

      <div v-if="notifications.length" class="notification-list">
        <div
          v-for="n in notifications.slice(0, 5)"
          :key="n.id"
          class="notification-item"
        >
          <span :class="['notification-item__dot', `notification-item__dot--${n.type}`]" />
          <div class="notification-item__content">
            <div class="notification-item__title">{{ n.title }}</div>
            <div class="notification-item__body">{{ n.body }}</div>
          </div>
          <span class="notification-item__time">{{ timeAgo(n.created_at) }}</span>
        </div>
      </div>
      <p v-else class="empty-text">No notifications yet.</p>
    </div>

    <!-- Reject Modal -->
    <BaseModal v-model="rejectModalVisible" title="Reject Restaurant" size="sm">
      <div class="reject-modal">
        <p class="reject-modal__text">
          Are you sure you want to reject <strong>{{ rejectTarget?.name }}</strong>?
        </p>
        <BaseInput
          v-model="rejectReason"
          label="Reason"
          placeholder="Provide a reason for rejection..."
          type="textarea"
        />
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="rejectModalVisible = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="actionLoading" @click="handleReject">
          Reject
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import adminService from "../../services/adminService";
import { useToast } from "../../composables/useToast";
import PageHeader from "../../components/ui/PageHeader.vue";
import StatCard from "../../components/ui/StatCard.vue";
import BaseCard from "../../components/ui/BaseCard.vue";
import BaseButton from "../../components/ui/BaseButton.vue";
import BaseBadge from "../../components/ui/BaseBadge.vue";
import BaseModal from "../../components/ui/BaseModal.vue";
import BaseInput from "../../components/ui/BaseInput.vue";

const toast = useToast();
const stats = ref({});
const pendingRestaurants = ref([]);
const notifications = ref([]);
const rejectModalVisible = ref(false);
const rejectTarget = ref(null);
const rejectReason = ref("");
const actionLoading = ref(false);

const todayDate = new Date().toLocaleDateString("en-US", {
  weekday: "long",
  year: "numeric",
  month: "long",
  day: "numeric",
});

function timeAgo(date) {
  if (!date) return "";
  const seconds = Math.floor((new Date() - new Date(date)) / 1000);
  if (seconds < 60) return "just now";
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

async function handleApprove(restaurant) {
  try {
    await adminService.approveRestaurant(restaurant.id);
    pendingRestaurants.value = pendingRestaurants.value.filter(r => r.id !== restaurant.id);
    stats.value.pending_restaurants = Math.max(0, (stats.value.pending_restaurants || 1) - 1);
    toast.success(`${restaurant.name} has been approved`);
  } catch (e) {
    toast.error("Failed to approve restaurant");
  }
}

function openRejectModal(restaurant) {
  rejectTarget.value = restaurant;
  rejectReason.value = "";
  rejectModalVisible.value = true;
}

async function handleReject() {
  if (!rejectReason.value.trim()) {
    toast.warning("Please provide a reason");
    return;
  }
  actionLoading.value = true;
  try {
    await adminService.rejectRestaurant(rejectTarget.value.id, rejectReason.value.trim());
    pendingRestaurants.value = pendingRestaurants.value.filter(r => r.id !== rejectTarget.value.id);
    stats.value.pending_restaurants = Math.max(0, (stats.value.pending_restaurants || 1) - 1);
    rejectModalVisible.value = false;
    toast.success(`${rejectTarget.value.name} has been rejected`);
  } catch (e) {
    toast.error("Failed to reject restaurant");
  } finally {
    actionLoading.value = false;
  }
}

onMounted(async () => {
  try {
    const [statsRes, pendingRes, notifRes] = await Promise.all([
      adminService.getStats(),
      adminService.getPendingRestaurants(),
      adminService.getNotifications(),
    ]);
    function unwrap(res, fallback) { const raw = res.data?.data; if (fallback !== undefined && typeof fallback === 'object' && !Array.isArray(fallback)) return raw || fallback; return Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : (fallback || []) }
    stats.value = unwrap(statsRes, {});
    pendingRestaurants.value = unwrap(pendingRes);
    notifications.value = unwrap(notifRes);
  } catch (e) {
    console.error("Failed to load dashboard", e);
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el)
  })
});
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--space-4);
  margin-bottom: var(--space-8);
}

.section {
  margin-bottom: var(--space-8);
}

.section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}

.section__title {
  margin: 0;
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.section__link {
  font-size: var(--text-sm);
  color: var(--color-accent);
  text-decoration: none;
  font-weight: var(--font-medium);
}

.section__link:hover {
  color: var(--color-accent-light);
}

.pending-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: var(--space-4);
}

.pending-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.pending-card__name {
  margin: 0;
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.pending-card__owner {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.pending-card__date {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.pending-card__actions {
  display: flex;
  gap: var(--space-2);
}

.notification-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.notification-item__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-top: 6px;
  flex-shrink: 0;
  background: var(--color-text-muted);
}

.notification-item__dot--restaurant_approved { background: var(--color-success); }
.notification-item__dot--restaurant_rejected { background: var(--color-danger); }
.notification-item__dot--new_registration { background: var(--color-warning); }
.notification-item__dot--restaurant_suspended { background: var(--color-danger); }

.notification-item__content { flex: 1; }

.notification-item__title {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
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

.empty-text {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  text-align: center;
  padding: var(--space-8);
}

.pending-section {
  border-left: 3px solid var(--color-energy);
  background: rgba(249,194,46,0.04);
  border-radius: var(--radius-xl);
  padding: var(--space-6);
}

.reject-modal__text {
  margin: 0 0 var(--space-4);
  color: var(--color-text-secondary);
}
</style>

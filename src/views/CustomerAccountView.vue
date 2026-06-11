<template>
  <div class="customer-account">
    <PageHeader title="My Account" />

    <!-- Profile Card -->
    <div class="profile-card">
      <div class="profile-card__avatar">{{ userInitials }}</div>
      <div class="profile-card__info">
        <h2 class="profile-card__name">{{ auth.user?.name }}</h2>
        <p class="profile-card__email">{{ auth.user?.email }}</p>
        <p class="profile-card__joined">Member since {{ joinDate }}</p>
      </div>
      <BaseButton variant="ghost" size="sm">Edit Profile</BaseButton>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-item">
        <span class="stat-item__value">{{ orderCount }}</span>
        <span class="stat-item__label">Total Orders</span>
      </div>
      <div class="stat-item">
        <span class="stat-item__value">${{ totalSpent.toFixed(2) }}</span>
        <span class="stat-item__label">Total Spent</span>
      </div>
      <div class="stat-item">
        <span class="stat-item__value">{{ orderCount * 10 }}</span>
        <span class="stat-item__label">Points Earned</span>
      </div>
    </div>

    <!-- Recent Orders -->
    <div v-if="recentOrders.length" class="section">
      <h3 class="section__title">Recent Orders</h3>
      <div class="recent-orders">
        <RouterLink
          v-for="order in recentOrders"
          :key="order.id"
          :to="'/customer/orders'"
          class="recent-order"
        >
          <div class="recent-order__info">
            <span class="recent-order__number">#{{ order.order_number }}</span>
            <span class="recent-order__restaurant">{{ order.restaurant?.name || 'Restaurant' }}</span>
          </div>
          <div class="recent-order__meta">
            <BaseBadge :variant="statusVariant(order.status)">{{ order.status }}</BaseBadge>
            <span class="recent-order__total">${{ Number(order.total || 0).toFixed(2) }}</span>
          </div>
        </RouterLink>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="danger-zone">
      <BaseButton variant="danger" @click="handleLogout">Sign Out</BaseButton>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";
import customerService from "../services/customerService";
import PageHeader from "../components/ui/PageHeader.vue";
import BaseButton from "../components/ui/BaseButton.vue";
import BaseBadge from "../components/ui/BaseBadge.vue";

const auth = useAuthStore();
const router = useRouter();
const orderCount = ref(0);
const totalSpent = ref(0);
const recentOrders = ref([]);

const userInitials = computed(() => {
  const name = auth.user?.name || "U";
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
});

const joinDate = computed(() => {
  const date = auth.user?.created_at || auth.user?.joined_at;
  if (date) {
    return new Date(date).toLocaleDateString("en-US", { year: "numeric", month: "long" });
  }
  return new Date().toLocaleDateString("en-US", { year: "numeric", month: "long" });
});

function statusVariant(status) {
  return { pending: "warning", preparing: "info", ready: "success", completed: "neutral", cancelled: "danger" }[status] || "neutral";
}

async function handleLogout() {
  await auth.logout();
  router.push("/discover");
}

onMounted(async () => {
  try {
    const res = await customerService.getMyOrders();
    const body = res.data;
    const raw = body?.data;
    const orders = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
    orderCount.value = orders.length;
    totalSpent.value = orders.reduce((sum, o) => sum + Number(o.total || 0), 0);
    recentOrders.value = orders.slice(0, 5);
  } catch {}
});
</script>

<style scoped>
.profile-card {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding: var(--space-6);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  margin-bottom: var(--space-6);
}

.profile-card__avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--color-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-xl);
  font-weight: var(--font-bold);
  flex-shrink: 0;
}

.profile-card__info { flex: 1; }
.profile-card__name { margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--color-text-primary); }
.profile-card__email { margin: var(--space-1) 0; font-size: var(--text-sm); color: var(--color-text-secondary); }
.profile-card__joined { margin: 0; font-size: var(--text-xs); color: var(--color-text-muted); }

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-4);
  margin-bottom: var(--space-8);
}

.stat-item {
  text-align: center;
  padding: var(--space-5);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
}

.stat-item__value { display: block; font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--color-text-primary); }
.stat-item__label { font-size: var(--text-sm); color: var(--color-text-secondary); }

.danger-zone {
  padding-top: var(--space-6);
  border-top: 1px solid var(--color-border);
}

.section { margin-bottom: var(--space-6); }
.section__title { margin: 0 0 var(--space-4); font-size: var(--text-lg); font-weight: var(--font-semibold); color: var(--color-text-primary); }

.recent-orders { display: flex; flex-direction: column; gap: var(--space-3); }

.recent-order {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  text-decoration: none;
  color: inherit;
  transition: border-color var(--transition-fast);
}

.recent-order:hover { border-color: var(--color-primary); }

.recent-order__info { display: flex; flex-direction: column; gap: var(--space-1); }
.recent-order__number { font-weight: var(--font-bold); color: var(--color-text-primary); font-size: var(--text-sm); }
.recent-order__restaurant { font-size: var(--text-xs); color: var(--color-text-secondary); }
.recent-order__meta { display: flex; align-items: center; gap: var(--space-3); }
.recent-order__total { font-weight: var(--font-bold); color: var(--color-text-primary); }
</style>

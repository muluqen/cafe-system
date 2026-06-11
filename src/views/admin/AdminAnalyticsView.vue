<template>
  <div class="admin-analytics">
    <PageHeader title="Analytics">
      <template #right>
        <div class="period-tabs">
          <button
            :class="['period-tab', { 'period-tab--active': period === 'week' }]"
            @click="period = 'week'; fetchAnalytics()"
          >
            This Week
          </button>
          <button
            :class="['period-tab', { 'period-tab--active': period === 'month' }]"
            @click="period = 'month'; fetchAnalytics()"
          >
            This Month
          </button>
        </div>
      </template>
    </PageHeader>

    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="card" :lines="3" />
    </div>

    <div v-else class="analytics-content">
      <div class="stats-grid">
        <StatCard title="Total Orders" :value="totalOrders" :icon="'📦'" />
        <StatCard title="Total Revenue" :value="totalRevenue" prefix="$" :icon="'💰'" />
        <StatCard title="New Restaurants" :value="newRestaurants" :icon="'🏪'" />
      </div>

      <div class="section">
        <h3 class="section__title">Top Restaurants by Orders</h3>
        <div v-if="topRestaurants.length" class="top-list">
          <div v-for="(r, i) in topRestaurants" :key="r.id" class="top-item">
            <span class="top-item__rank">#{{ i + 1 }}</span>
            <span class="top-item__name">{{ r.name }}</span>
            <span class="top-item__count">{{ r.orders_count }} orders</span>
          </div>
        </div>
        <p v-else class="empty-text">No data available.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import adminService from "../../services/adminService";
import { useToast } from "../../composables/useToast";
import PageHeader from "../../components/ui/PageHeader.vue";
import StatCard from "../../components/ui/StatCard.vue";
import SkeletonLoader from "../../components/ui/SkeletonLoader.vue";

const toast = useToast();
const loading = ref(true);
const period = ref("week");
const analytics = ref({});

const totalOrders = computed(() => {
  return analytics.value.orders_per_day?.reduce((sum, d) => sum + d.count, 0) || 0;
});

const totalRevenue = computed(() => {
  return analytics.value.revenue_per_day?.reduce((sum, d) => sum + (d.revenue || 0), 0) || 0;
});

const newRestaurants = computed(() => {
  return analytics.value.restaurants_per_day?.reduce((sum, d) => sum + d.count, 0) || 0;
});

const topRestaurants = computed(() => analytics.value.top_restaurants || []);

async function fetchAnalytics() {
  loading.value = true;
  try {
    const { data } = await adminService.getPlatformAnalytics(period.value);
    analytics.value = data?.data || data || {};
  } catch (e) {
    toast.error("Failed to load analytics");
  } finally {
    loading.value = false;
  }
}

onMounted(fetchAnalytics);
</script>

<style scoped>
.period-tabs {
  display: flex;
  gap: var(--space-1);
  background: var(--color-bg-subtle);
  padding: var(--space-1);
  border-radius: var(--radius-md);
}

.period-tab {
  padding: var(--space-2) var(--space-3);
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.period-tab--active {
  background: var(--color-primary);
  color: white;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--space-4);
  margin-bottom: var(--space-8);
}

.section {
  margin-bottom: var(--space-8);
}

.section__title {
  margin: 0 0 var(--space-4);
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.top-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.top-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.top-item__rank {
  font-weight: var(--font-bold);
  color: var(--color-primary);
  min-width: 30px;
}

.top-item__name {
  flex: 1;
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
}

.top-item__count {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.empty-text {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  text-align: center;
  padding: var(--space-8);
}

.loading-state {
  padding: var(--space-8);
}
</style>

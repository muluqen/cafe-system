<template>
  <section class="customer-stack">
    <section class="panel rewards-hero">
      <div>
        <span class="eyebrow">Guest Journey</span>
        <h1 class="panel-title">Your progress with {{ selectedRestaurantName }}</h1>
        <p class="muted">
          See how often you visit, how much you have explored, and what unlocks next when you keep ordering here.
        </p>
      </div>
      <div class="rewards-tier">
        <span class="selector-label">Current tier</span>
        <strong>{{ loyalty.tier }}</strong>
        <p class="muted">{{ loyalty.tierMessage }}</p>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header panel-header-rich">
        <div>
          <span class="eyebrow">Snapshot</span>
          <h2 class="panel-title">Your restaurant relationship</h2>
        </div>
      </header>

      <div class="content-pad rewards-grid">
        <article class="metric-card">
          <span class="selector-label">Visits</span>
          <strong>{{ loyalty.visitCount }}</strong>
          <p class="muted">Completed orders with this restaurant.</p>
        </article>
        <article class="metric-card">
          <span class="selector-label">Spent</span>
          <strong>${{ loyalty.totalSpent.toFixed(2) }}</strong>
          <p class="muted">Total spend across your orders here.</p>
        </article>
        <article class="metric-card">
          <span class="selector-label">Favorites</span>
          <strong>{{ loyalty.favoriteCount }}</strong>
          <p class="muted">Different menu items you keep coming back to.</p>
        </article>
        <article class="metric-card">
          <span class="selector-label">Progress</span>
          <strong>{{ loyalty.points }} pts</strong>
          <p class="muted">{{ loyalty.pointsToNext }} points to {{ loyalty.nextTier }}.</p>
        </article>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header">
        <div>
          <span class="eyebrow">Unlocks</span>
          <h2 class="panel-title">What this loyalty can open</h2>
        </div>
      </header>

      <div class="content-pad unlock-list">
        <article v-for="unlock in unlocks" :key="unlock.name" class="unlock-card" :class="{ reached: unlock.reached }">
          <div class="order-head">
            <strong>{{ unlock.name }}</strong>
            <span class="badge">{{ unlock.threshold }} pts</span>
          </div>
          <p class="muted">{{ unlock.description }}</p>
          <div class="progress-track">
            <span class="progress-bar" :style="{ width: `${unlock.progress}%` }" />
          </div>
          <p class="muted">
            {{ unlock.reached ? "Unlocked" : `${unlock.remaining} more points to go` }}
          </p>
        </article>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header">
        <div>
          <span class="eyebrow">What You Love</span>
          <h2 class="panel-title">Most ordered dishes</h2>
        </div>
      </header>

      <div class="content-pad">
        <div v-if="favoriteItems.length" class="feedback-history">
          <article v-for="item in favoriteItems" :key="item.name" class="feedback-card">
            <div class="order-head">
              <strong>{{ item.name }}</strong>
              <span class="badge">{{ item.count }} orders</span>
            </div>
            <p class="muted">{{ item.quantity }} total plates ordered</p>
          </article>
        </div>
        <div v-else class="empty">
          Once you place a few orders here, your favorites and unlock progress will show up.
        </div>
      </div>
    </section>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const orders = ref([]);

const selectedRestaurantName = computed(() => {
  const restaurant = auth.publicRestaurants.find(
    (row) => String(row.id) === String(auth.selectedRestaurantId || "")
  );
  return restaurant?.name || "this restaurant";
});

const restaurantOrders = computed(() =>
  orders.value.filter(
    (order) =>
      !auth.selectedRestaurantId ||
      String(order.restaurant_id || order.restaurant?.id || "") === String(auth.selectedRestaurantId)
  )
);

const favoriteItems = computed(() => {
  const tally = new Map();

  restaurantOrders.value.forEach((order) => {
    (order.items || []).forEach((item) => {
      const current = tally.get(item.item_name) || { name: item.item_name, count: 0, quantity: 0 };
      current.count += 1;
      current.quantity += Number(item.quantity || 0);
      tally.set(item.item_name, current);
    });
  });

  return [...tally.values()].sort((a, b) => b.quantity - a.quantity).slice(0, 5);
});

const loyalty = computed(() => {
  const visitCount = restaurantOrders.value.length;
  const totalSpent = restaurantOrders.value.reduce((sum, order) => sum + Number(order.total || 0), 0);
  const favoriteCount = favoriteItems.value.length;
  const points = Math.floor(totalSpent) + visitCount * 8 + favoriteCount * 12;

  const tiers = [
    { name: "New Regular", min: 0, message: "You have started building a relationship with this place." },
    { name: "House Favorite", min: 80, message: "You are becoming a familiar face with recognizable taste." },
    { name: "Inner Circle", min: 180, message: "You order often enough to deserve first-class treatment." },
    { name: "Chef's Table", min: 320, message: "You have serious loyalty status with this restaurant." }
  ];

  const currentTier =
    [...tiers].reverse().find((tier) => points >= tier.min) || tiers[0];
  const nextTier = tiers.find((tier) => tier.min > points);

  return {
    visitCount,
    totalSpent,
    favoriteCount,
    points,
    tier: currentTier.name,
    tierMessage: currentTier.message,
    nextTier: nextTier?.name || "top tier",
    pointsToNext: Math.max(0, (nextTier?.min || points) - points)
  };
});

const unlocks = computed(() => {
  const targets = [
    { name: "Priority Table Ping", threshold: 60, description: "Early heads-up when tables open around your usual times." },
    { name: "Chef Surprise", threshold: 140, description: "A rotating off-menu extra or tasting recommendation." },
    { name: "Fast Reorder Lane", threshold: 220, description: "One-tap favorite ordering with a reserved prep note." },
    { name: "Platrick Gold Guest", threshold: 340, description: "Top-tier recognition for your most-loved restaurant." }
  ];

  return targets.map((unlock) => ({
    ...unlock,
    reached: loyalty.value.points >= unlock.threshold,
    remaining: Math.max(0, unlock.threshold - loyalty.value.points),
    progress: Math.min(100, Math.round((loyalty.value.points / unlock.threshold) * 100))
  }));
});

async function loadOrders() {
  const { data } = await api.get("/orders", { params: { per_page: 100 } });
  orders.value = data?.data || [];
}

onMounted(async () => {
  if (!auth.publicRestaurants.length) {
    await auth.loadPublicRestaurants();
  }
  await loadOrders();
});
</script>

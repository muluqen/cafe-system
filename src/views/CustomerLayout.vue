<template>
  <div class="customer-layout">
    <!-- Desktop Top Navbar -->
    <header class="cust-navbar">
      <div class="cust-navbar__inner">
        <RouterLink to="/discover" class="cust-navbar__brand">🍽 Tavliq</RouterLink>
        <nav class="cust-navbar__links">
          <RouterLink to="/discover" class="cust-navbar__link">Discover</RouterLink>
          <RouterLink to="/customer/orders" class="cust-navbar__link cust-navbar__link--orders">
            My Orders
            <span v-if="activeOrderCount > 0" class="order-badge">{{ activeOrderCount }}</span>
          </RouterLink>
          <RouterLink to="/customer/rewards" class="cust-navbar__link">Rewards</RouterLink>
          <RouterLink to="/customer/feedback" class="cust-navbar__link">Feedback</RouterLink>
        </nav>
        <div class="cust-navbar__right">
          <!-- If logged in show avatar + logout -->
          <div v-if="auth.isAuthenticated" class="cust-navbar__user">
            <RouterLink to="/customer/account" class="cust-navbar__avatar">
              {{ userInitials }}
            </RouterLink>
            <button class="cust-navbar__logout" @click="handleLogout">
              Sign Out
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="cust-main">
      <RouterView />
    </main>

    <!-- Mobile Bottom Tab Bar -->
    <nav class="cust-tabs">
      <RouterLink to="/discover" class="cust-tab" active-class="cust-tab--active">
        <span class="cust-tab__icon">🍽</span>
        <span class="cust-tab__label">Discover</span>
      </RouterLink>
      <RouterLink to="/customer/orders" class="cust-tab cust-tab--orders" active-class="cust-tab--active">
        <span class="cust-tab__icon-wrap">
          <span class="cust-tab__icon">📦</span>
          <span v-if="activeOrderCount > 0" class="order-badge order-badge--mobile">{{ activeOrderCount }}</span>
        </span>
        <span class="cust-tab__label">Orders</span>
      </RouterLink>
      <RouterLink to="/customer/rewards" class="cust-tab" active-class="cust-tab--active">
        <span class="cust-tab__icon">⭐</span>
        <span class="cust-tab__label">Rewards</span>
      </RouterLink>
      <RouterLink to="/customer/account" class="cust-tab" active-class="cust-tab--active">
        <span class="cust-tab__icon">👤</span>
        <span class="cust-tab__label">Account</span>
      </RouterLink>
    </nav>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from "vue";
import { RouterLink, RouterView, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";
import { useActiveOrders } from "../composables/useActiveOrders";

const auth = useAuthStore();
const router = useRouter();
const { activeOrderCount, startPolling, stopPolling } = useActiveOrders();

const userInitials = computed(() => {
  const name = auth.user?.name || "U";
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
});

function handleLogout() {
  auth.logout();
  router.push("/discover");
}

onMounted(() => {
  startPolling(15000);
});

onUnmounted(() => {
  stopPolling();
});
</script>

<style scoped>
.customer-layout {
  min-height: 100vh;
  background: var(--color-bg);
  padding-bottom: 72px;
}

/* ── Desktop Navbar ── */
.cust-navbar {
  position: sticky;
  top: 0;
  z-index: 200;
  background: rgba(9,9,11,0.85);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  backdrop-filter: blur(20px);
}

.cust-navbar__inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--space-6);
  height: 60px;
}

.cust-navbar__brand {
  font-size: var(--text-xl);
  font-weight: var(--font-extrabold);
  color: var(--color-text-primary);
  text-decoration: none;
}

.cust-navbar__links {
  display: flex;
  gap: var(--space-1);
}

.cust-navbar__link {
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: all var(--transition-fast);
  position: relative;
}

.cust-navbar__link:hover {
  color: var(--color-text-primary);
  background: var(--color-bg-subtle);
}

.cust-navbar__link.router-link-exact-active {
  color: var(--color-warm);
  border-bottom: 2px solid var(--color-warm);
  background: transparent;
}

.cust-navbar__link--orders {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.cust-navbar__right {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.cust-navbar__user {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.cust-navbar__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--color-warm);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
  text-decoration: none;
}

.cust-navbar__logout {
  padding: 0.375rem 0.875rem;
  background: rgba(244,63,94,0.08);
  border: 1px solid rgba(244,63,94,0.20);
  border-radius: var(--radius-md);
  color: #F43F5E;
  font-size: var(--text-sm);
  font-weight: 600;
  cursor: pointer;
  transition: all 150ms ease;
  font-family: 'Inter', sans-serif;
}

.cust-navbar__logout:hover {
  background: rgba(244,63,94,0.15);
  border-color: #F43F5E;
}

/* ── Active Order Badge ── */
.order-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 9999px;
  background: #10B981;
  color: white;
  font-size: 10px;
  font-weight: 800;
  line-height: 1;
  animation: badge-pulse 2s ease-in-out infinite;
}

@keyframes badge-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
}

/* ── Main ── */
.cust-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--space-6);
}

/* ── Mobile Tab Bar ── */
.cust-tabs {
  display: none;
}

.cust-tab__icon-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.order-badge--mobile {
  position: absolute;
  top: -6px;
  right: -10px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  font-size: 9px;
}

@media (max-width: 768px) {
  .cust-navbar__links { display: none; }

  .cust-tabs {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 64px;
    background: var(--color-bg-elevated);
    border-top: 1px solid var(--color-border);
    z-index: 200;
  }

  .cust-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    color: var(--color-text-muted);
    text-decoration: none;
    font-size: var(--text-xs);
    transition: color var(--transition-fast);
  }

  .cust-tab--active {
    color: var(--color-warm);
  }

  .cust-tab__icon {
    font-size: 1.2rem;
  }
}
</style>

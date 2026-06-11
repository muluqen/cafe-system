<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-sidebar__head">
        <span class="admin-sidebar__logo">⚡</span>
        <div>
          <div class="admin-sidebar__brand">Tavliq Admin</div>
          <div class="admin-sidebar__role">Super Admin Panel</div>
        </div>
      </div>

      <nav class="admin-sidebar__nav">
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="admin-nav-link"
        >
          <span class="admin-nav-link__icon">{{ link.icon }}</span>
          <span class="admin-nav-link__label">{{ link.label }}</span>
          <span v-if="link.badge" class="admin-nav-link__badge">{{ link.badge }}</span>
        </RouterLink>
      </nav>

      <div class="admin-sidebar__bottom">
        <div class="admin-sidebar__user">
          <div class="admin-sidebar__avatar">{{ userInitials }}</div>
          <div class="admin-sidebar__user-info">
            <div class="admin-sidebar__user-name">{{ auth.user?.name }}</div>
            <div class="admin-sidebar__user-role">Super Admin</div>
          </div>
        </div>
        <button class="admin-sidebar__logout" @click="handleLogout">Logout</button>
      </div>
    </aside>

    <!-- Topbar -->
    <header class="admin-topbar">
      <h2 class="admin-topbar__title">{{ pageTitle }}</h2>
      <div class="admin-topbar__right">
        <button class="admin-topbar__bell" @click="$router.push('/admin/notifications')">
          🔔
          <span v-if="unreadCount > 0" class="admin-topbar__badge">{{ unreadCount }}</span>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
      <Transition name="page" mode="out-in">
        <RouterView :key="$route.fullPath" />
      </Transition>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, RouterView, useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/authStore";
import adminService from "../../services/adminService";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const unreadCount = ref(0);

const userInitials = computed(() => {
  const name = auth.user?.name || "A";
  return name.split(" ").map(n => n[0]).join("").toUpperCase().slice(0, 2);
});

const pageTitle = computed(() => {
  const titles = {
    "admin-dashboard": "Dashboard",
    "admin-restaurants": "Restaurants",
    "admin-pending": "Pending Approvals",
    "admin-users": "Users",
    "admin-analytics": "Analytics",
    "admin-notifications": "Notifications",
  };
  return titles[route.name] || "Admin";
});

const navLinks = computed(() => [
  { to: "/admin/dashboard", icon: "📊", label: "Dashboard" },
  { to: "/admin/restaurants", icon: "🏪", label: "Restaurants" },
  { to: "/admin/restaurants/pending", icon: "⏳", label: "Pending", badge: null },
  { to: "/admin/users", icon: "👥", label: "Users" },
  { to: "/admin/analytics", icon: "📈", label: "Analytics" },
  { to: "/admin/notifications", icon: "🔔", label: "Notifications", badge: unreadCount.value > 0 ? unreadCount.value : null },
]);

async function handleLogout() {
  await auth.logout();
  router.push("/admin/login");
}

onMounted(async () => {
  try {
    const { data } = await adminService.getNotifications();
    const body = data?.data || data;
    const notifications = Array.isArray(body) ? body : body?.data || [];
    unreadCount.value = notifications.filter(n => !n.read_at).length;
  } catch {}
});
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

/* ── Sidebar ── */
.admin-sidebar {
  width: 260px;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  background: var(--color-bg-elevated);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  z-index: 200;
}

.admin-sidebar__head {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-6) var(--space-4);
  border-bottom: 1px solid var(--color-border);
}

.admin-sidebar__logo {
  font-size: 1.5rem;
}

.admin-sidebar__brand {
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
  font-size: var(--text-base);
}

.admin-sidebar__role {
  font-size: var(--text-xs);
  color: var(--color-accent);
  font-weight: var(--font-semibold);
}

.admin-sidebar__nav {
  flex: 1;
  padding: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.admin-nav-link {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  text-decoration: none;
  transition: all var(--transition-fast);
  border-left: 3px solid transparent;
}

.admin-nav-link:hover {
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  transform: translateX(3px);
}

.admin-nav-link.router-link-exact-active {
  background: var(--color-cool-subtle);
  color: var(--color-cool);
  border-left: 2px solid var(--color-cool);
}

.admin-nav-link__icon {
  font-size: 1.1rem;
  width: 24px;
  text-align: center;
}

.admin-nav-link__badge {
  margin-left: auto;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  background: var(--color-warning);
  color: var(--color-text-inverse);
  font-size: var(--text-xs);
  font-weight: var(--font-bold);
  min-width: 20px;
  text-align: center;
}

.admin-sidebar__bottom {
  padding: var(--space-4);
  border-top: 1px solid var(--color-border);
}

.admin-sidebar__user {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin-bottom: var(--space-3);
}

.admin-sidebar__avatar {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-full);
  background: var(--color-cool);
  color: #09090B;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
}

.admin-sidebar__user-name {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
}

.admin-sidebar__user-role {
  font-size: var(--text-xs);
  color: var(--color-accent);
}

.admin-sidebar__logout {
  width: 100%;
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: transparent;
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.admin-sidebar__logout:hover {
  background: var(--color-bg-subtle);
  color: var(--color-danger);
  border-color: var(--color-danger);
}

/* ── Topbar ── */
.admin-topbar {
  position: fixed;
  top: 0;
  left: 260px;
  right: 0;
  height: 64px;
  background: rgba(9,9,11,0.85);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--space-8);
  z-index: 150;
  backdrop-filter: blur(20px);
}

.admin-topbar__title {
  margin: 0;
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.admin-topbar__right {
  display: flex;
  align-items: center;
  gap: var(--space-4);
}

.admin-topbar__bell {
  position: relative;
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  padding: var(--space-2);
  border-radius: var(--radius-md);
  transition: background var(--transition-fast);
}

.admin-topbar__bell:hover {
  background: var(--color-bg-subtle);
}

.admin-topbar__badge {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--color-danger);
  color: white;
  font-size: 10px;
  font-weight: var(--font-bold);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ── Main ── */
.admin-main {
  margin-left: 260px;
  margin-top: 64px;
  padding: var(--space-8);
  background: var(--color-bg);
  min-height: calc(100vh - 64px);
  flex: 1;
}

@media (max-width: 768px) {
  .admin-sidebar { display: none; }
  .admin-topbar { left: 0; }
  .admin-main { margin-left: 0; }
}
</style>

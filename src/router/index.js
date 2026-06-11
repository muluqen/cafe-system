import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/authStore";

// ── Public Views ─────────────────────────────────────────
import LandingView from "../views/LandingView.vue";
import RestaurantRegisterView from "../views/RestaurantRegisterView.vue";
import LoginView from "../views/LoginView.vue";
import StaffLoginView from "../views/StaffLoginView.vue";
import RestaurantDetailView from "../views/RestaurantDetailView.vue";
import CustomerDiscoverView from "../views/CustomerDiscoverView.vue";

// ── Admin Views ──────────────────────────────────────────
import AdminLoginView from "../views/admin/AdminLoginView.vue";
import AdminLayout from "../views/admin/AdminLayout.vue";
import AdminDashboardView from "../views/admin/AdminDashboardView.vue";
import AdminRestaurantsView from "../views/admin/AdminRestaurantsView.vue";
import AdminPendingView from "../views/admin/AdminPendingView.vue";
import AdminUsersView from "../views/admin/AdminUsersView.vue";
import AdminAnalyticsView from "../views/admin/AdminAnalyticsView.vue";
import AdminNotificationsView from "../views/admin/AdminNotificationsView.vue";

// ── Staff Views ──────────────────────────────────────────
import AppLayout from "../views/AppLayout.vue";
import DashboardView from "../views/DashboardView.vue";
import PosView from "../views/PosView.vue";
import KitchenDisplayView from "../views/KitchenDisplayView.vue";
import AnalyticsView from "../views/AnalyticsView.vue";
import RestaurantBuilderView from "../views/RestaurantBuilderView.vue";
import RestaurantPulseView from "../views/RestaurantPulseView.vue";
import EntityTableView from "../views/EntityTableView.vue";
import ManageLayout from "../views/manage/ManageLayout.vue";

// ── Customer Views ───────────────────────────────────────
import CustomerLayout from "../views/CustomerLayout.vue";
import CustomerOrdersView from "../views/CustomerOrdersView.vue";
import CustomerRewardsView from "../views/CustomerRewardsView.vue";
import CustomerFeedbackView from "../views/CustomerFeedbackView.vue";
import CustomerAccountView from "../views/CustomerAccountView.vue";
import QROrderView from "../views/QROrderView.vue";

function defaultStaffRoute(auth) {
  const role = auth.staffRole;
  if (role === "manager" || role === "floor_manager") return { name: "staff-dashboard" };
  if (["cashier", "server", "host"].includes(role)) return { name: "pos" };
  if (["kitchen", "barista"].includes(role)) return { name: "kds" };
  if (role === "inventory") return { path: "/app/manage/ingredients" };
  return { name: "staff-dashboard" };
}

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior() {
    return { top: 0 }
  },
  routes: [
    // ── PUBLIC ─────────────────────────────────────────────
    { path: "/", name: "landing", component: LandingView },
    { path: "/register", name: "register", component: RestaurantRegisterView },
    { path: "/login", name: "login", component: LoginView },
    { path: "/staff/login", name: "staff-login", component: StaffLoginView },
    { path: "/admin/login", name: "admin-login", component: AdminLoginView },
    { path: "/discover", name: "discover", component: CustomerDiscoverView },
    { path: "/restaurant/:id", name: "restaurant-detail", component: RestaurantDetailView },
    { path: "/order/:restaurantId/:tableId", name: "qr-order", component: QROrderView },

    // ── SUPER ADMIN ────────────────────────────────────────
    {
      path: "/admin",
      component: AdminLayout,
      meta: { requiresAuth: true, requiresSuperAdmin: true },
      children: [
        { path: "", redirect: "/admin/dashboard" },
        { path: "dashboard", name: "admin-dashboard", component: AdminDashboardView },
        { path: "restaurants/pending", name: "admin-pending", component: AdminPendingView },
        { path: "restaurants", name: "admin-restaurants", component: AdminRestaurantsView },
        { path: "users", name: "admin-users", component: AdminUsersView },
        { path: "analytics", name: "admin-analytics", component: AdminAnalyticsView },
        { path: "notifications", name: "admin-notifications", component: AdminNotificationsView },
      ],
    },

    // ── CUSTOMER ───────────────────────────────────────────
    {
      path: "/customer",
      component: CustomerLayout,
      meta: { requiresAuth: true, requiresCustomer: true },
      children: [
        { path: "orders", name: "customer-orders", component: CustomerOrdersView },
        { path: "rewards", name: "customer-rewards", component: CustomerRewardsView },
        { path: "feedback", name: "customer-feedback", component: CustomerFeedbackView },
        { path: "account", name: "customer-account", component: CustomerAccountView },
      ],
    },

    // ── STAFF ──────────────────────────────────────────────
    {
      path: "/app",
      component: AppLayout,
      meta: { requiresAuth: true, requiresStaff: true },
      children: [
        { path: "", redirect: "/app/dashboard" },
        { path: "dashboard", name: "staff-dashboard", component: DashboardView },
        { path: "pos", name: "pos", component: PosView },
        { path: "kitchen", name: "kds", component: KitchenDisplayView },
        { path: "analytics", name: "analytics", component: AnalyticsView },
        { path: "restaurant-builder", name: "restaurant-builder", component: RestaurantBuilderView },
        { path: "pulse", name: "restaurant-pulse", component: RestaurantPulseView },
        {
          path: "manage/:entityKey",
          name: "entity-manage",
          component: ManageLayout,
        },
      ],
    },
  ],
});

// ── Navigation Guard ─────────────────────────────────────
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  // Always allow public routes
  const publicPaths = [
    '/',
    '/login',
    '/admin/login',
    '/staff/login',
    '/register',
    '/discover',
  ]
  const isPublic = publicPaths.includes(to.path) ||
                   to.path.startsWith('/restaurant/') ||
                   to.path.startsWith('/order/')
  
  if (isPublic) return next()

  // Check token from localStorage directly (most reliable)
  const token = localStorage.getItem('tavliq_token')
  if (!token) {
    if (to.path.startsWith('/admin')) return next('/admin/login')
    if (to.path.startsWith('/app')) return next('/staff/login')
    return next('/login')
  }

  // Load user only if not already loaded
  if (!auth.user) {
    try {
      await auth.fetchMe()
    } catch(e) {
      localStorage.removeItem('tavliq_token')
      if (to.path.startsWith('/admin')) return next('/admin/login')
      if (to.path.startsWith('/app')) return next('/staff/login')
      return next('/login')
    }
  }

  // Route protection
  if (to.path.startsWith('/admin')) {
    if (!auth.user?.is_super_admin) return next('/admin/login')
    return next()
  }

  if (to.path.startsWith('/app')) {
    const validStaffRoles = [
      'manager', 'floor_manager', 'cashier',
      'server', 'kitchen', 'barista', 'host', 'inventory'
    ]
    if (!validStaffRoles.includes(auth.user?.staff_role)) return next('/staff/login')
    if (to.path.startsWith('/app/manage/')) {
      const entityKey = to.path.split('/app/manage/')[1]?.split('?')[0]
      if (entityKey && !auth.canRead(entityKey)) {
        return next('/app/dashboard')
      }
    }
    if ((to.path === '/app/pos' && !auth.canRead('orders')) || (to.path === '/app/kitchen' && !auth.canRead('kds_kitchen'))) {
      return next('/app/dashboard')
    }
    return next()
  }

  if (to.path.startsWith('/customer')) {
    if (!auth.user) return next('/login')
    return next()
  }

  return next()
})

export default router;

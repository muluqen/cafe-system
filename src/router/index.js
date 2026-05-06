import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/authStore";
import AppLayout from "../views/AppLayout.vue";
import DashboardView from "../views/DashboardView.vue";
import EntityTableView from "../views/EntityTableView.vue";
import CustomerAccountView from "../views/CustomerAccountView.vue";
import CustomerDiscoverView from "../views/CustomerDiscoverView.vue";
import CustomerFeedbackView from "../views/CustomerFeedbackView.vue";
import CustomerOrdersView from "../views/CustomerOrdersView.vue";
import CustomerRewardsView from "../views/CustomerRewardsView.vue";
import LoginView from "../views/LoginView.vue";
import RestaurantBuilderView from "../views/RestaurantBuilderView.vue";
import RestaurantPulseView from "../views/RestaurantPulseView.vue";
import StaffLoginView from "../views/StaffLoginView.vue";
import { entities } from "../config/entities";
import { getStaffRoleMeta } from "../utils/staffRoles";

function defaultRestaurantRoute(auth) {
  if (!auth.isRestaurant) {
    return { name: "dashboard" };
  }

  if (auth.staffRole === "manager") {
    return { name: "restaurant-builder" };
  }
  return getStaffRoleMeta(auth.staffRole).defaultRoute || { name: "dashboard" };
}

const entityRoutes = entities.map((entity) => ({
  path: `/${entity.key}`,
  name: entity.key,
  component: EntityTableView,
  props: { entity },
  meta: { roles: entity.roles, staffRoles: entity.staffRoles || [] }
}));

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: "/login",
      name: "login",
      component: LoginView
    },
    {
      path: "/staff/login",
      name: "staff-login",
      component: StaffLoginView
    },
    {
      path: "/",
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        { path: "", name: "dashboard", component: DashboardView },
        {
          path: "discover",
          name: "customer-discover",
          component: CustomerDiscoverView,
          meta: { roles: ["customer"] }
        },
        {
          path: "my-orders",
          name: "customer-orders",
          component: CustomerOrdersView,
          meta: { roles: ["customer"] }
        },
        {
          path: "feedback",
          name: "customer-feedback",
          component: CustomerFeedbackView,
          meta: { roles: ["customer"] }
        },
        {
          path: "rewards",
          name: "customer-rewards",
          component: CustomerRewardsView,
          meta: { roles: ["customer"] }
        },
        {
          path: "restaurant-builder",
          name: "restaurant-builder",
          component: RestaurantBuilderView,
          meta: { roles: ["restaurant"], staffRoles: ["manager"] }
        },
        {
          path: "restaurant-pulse",
          name: "restaurant-pulse",
          component: RestaurantPulseView,
          meta: { roles: ["restaurant"], staffRoles: ["manager"] }
        },
        {
          path: "account",
          name: "customer-account",
          component: CustomerAccountView,
          meta: { roles: ["customer"] }
        },
        ...entityRoutes
      ]
    }
  ]
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  if (auth.token && !auth.user) {
    await auth.fetchMe();
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: "login" };
  }

  if (to.meta.roles && auth.user && !to.meta.roles.includes(auth.user.role)) {
    return { name: "dashboard" };
  }

  if (
    to.meta.staffRoles &&
    to.meta.staffRoles.length &&
    auth.isRestaurant &&
    !to.meta.staffRoles.includes(auth.staffRole)
  ) {
    return { name: "dashboard" };
  }

  if ((to.name === "login" || to.name === "staff-login") && auth.isAuthenticated) {
    return auth.isCustomer ? { name: "customer-discover" } : defaultRestaurantRoute(auth);
  }

  if (to.name === "dashboard" && auth.isCustomer) {
    return { name: "customer-discover" };
  }

  if (to.name === "dashboard" && auth.isRestaurant) {
    return defaultRestaurantRoute(auth);
  }

  return true;
});

export default router;

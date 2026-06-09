<template>
  <div class="app-shell" :class="{ 'customer-mode': auth.isCustomer }">
    <WelcomeOverlay
      v-if="auth.isCustomer"
      :visible="welcomeVisible"
      :headline="welcomeHeadline"
      :message="welcomeMessage"
      :restaurant-name="selectedRestaurantLabel"
      @enter="dismissWelcome"
    />

    <!-- Mobile FAB -->
    <button
      class="nav-fab mobile-trigger"
      :class="{ open: mobileMenuOpen }"
      @click="toggleMobileMenu"
      aria-label="Toggle mobile menu"
    >
      <span class="nav-fab-grid" aria-hidden="true">
        <span /><span /><span /><span />
      </span>
      <span class="nav-fab-label">{{ mobileMenuOpen ? "Close" : "Menu" }}</span>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'is-open': mobileMenuOpen }">
      <div class="sidebar-head">
        <div>
          <div class="brand">{{ auth.isCustomer ? "dinedirect" : restaurantBrand }}</div>
          <p v-if="auth.isRestaurant" class="sidebar-brand-meta">
            {{ staffRoleLabel }}
          </p>
        </div>
      </div>

      <div v-if="auth.isCustomer" class="selector-wrap">
        <label class="selector-label">Selected Restaurant</label>
        <select
          v-model="selectedRestaurant"
          class="input selector-input"
          @change="auth.setSelectedRestaurant(selectedRestaurant)"
        >
          <option value="">Choose one</option>
          <option v-for="row in restaurantOptions" :key="row.id" :value="row.id">
            {{ row.name }}
          </option>
        </select>
      </div>

      <div class="sidebar-intro">
        <span class="eyebrow sidebar-eyebrow">
          {{ auth.isCustomer ? "Customer Space" : "Service Map" }}
        </span>
        <p class="sidebar-copy">
          {{
            auth.isCustomer
              ? "Browse menus, customize your order, and keep reorders one tap away."
              : "Private restaurant workspace for operations, staff, and order flow."
          }}
        </p>
      </div>

      <nav class="nav">
        <RouterLink
          v-for="link in navLinks"
          :key="link.name"
          class="nav-link"
          :to="{ name: link.name }"
          :title="link.label"
          @click="handleNavClick"
        >
          {{ link.label }}
        </RouterLink>

        <RouterLink
          v-for="entity in visibleEntities"
          :key="entity.key"
          class="nav-link"
          :to="`/${entity.key}`"
          :title="entity.label"
          @click="handleNavClick"
        >
          {{ entity.label }}
        </RouterLink>
      </nav>

      <button class="logout-btn" @click="logout">Logout</button>
    </aside>

    <!-- Backdrop for mobile -->
    <div v-if="mobileMenuOpen" class="sidebar-backdrop" @click="mobileMenuOpen = false" />

    <!-- Main content -->
    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, RouterView, useRoute, useRouter } from "vue-router";
import WelcomeOverlay from "../components/customer/WelcomeOverlay.vue";
import { entities } from "../config/entities";
import { useAuthStore } from "../stores/authStore";
import { getStaffRoleMeta } from "../utils/staffRoles";

const mobileMenuOpen = ref(false);
const welcomeVisible = ref(false);
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const selectedRestaurant = ref(auth.selectedRestaurantId || "");

const navLinks = computed(() =>
  auth.isCustomer
    ? [
        { name: "customer-discover", label: "Discover" },
        { name: "customer-orders", label: "My Orders" },
        { name: "customer-feedback", label: "Feedback" },
        { name: "customer-rewards", label: "Perks" },
        { name: "customer-account", label: "Account" }
      ]
    : [
        ...(auth.staffRole === "manager" ? [{ name: "analytics", label: "Analytics & Security" }] : []),
        ...(auth.staffRole === "manager" ? [{ name: "restaurant-builder", label: "Site Builder" }] : []),
        ...(auth.staffRole === "manager" ? [{ name: "restaurant-pulse", label: "Customer Voice" }] : []),
        ...(["manager", "floor_manager", "server", "cashier"].includes(auth.staffRole) ? [{ name: "pos", label: "Point of Sale" }] : []),
        ...(["manager", "kitchen", "barista"].includes(auth.staffRole) ? [{ name: "kds", label: "Kitchen Display" }] : [])
      ]
);

const visibleEntities = computed(() =>
  entities.filter((entity) => {
    if (!entity.roles.includes(auth.role)) return false;
    
    if (auth.isRestaurant) {
      if (auth.staffRole === "manager") return true;

      // Check dynamic RBAC permissions first
      const dynamicPerm = auth.rolePermissions.find(p => p.entity_key === entity.key && p.staff_role === auth.staffRole);
      if (dynamicPerm) return !!dynamicPerm.can_read;

      // Fallback to static config
      return !entity.staffRoles || entity.staffRoles.includes(auth.staffRole);
    }
    return true;
  })
);

const restaurantOptions = computed(() => auth.publicRestaurants);
const selectedRestaurantLabel = computed(() => {
  const selected = auth.publicRestaurants.find(
    (row) => String(row.id) === String(auth.selectedRestaurantId || "")
  );
  return selected?.name || "your next stop";
});
const restaurantBrand = computed(() => {
  const match = auth.publicRestaurants.find(
    (row) => String(row.id) === String(auth.user?.restaurant_id || "")
  );
  return match?.name || "dinedirect";
});
const staffRoleLabel = computed(() => {
  return getStaffRoleMeta(auth.staffRole).workspaceLabel;
});
const welcomeHeadline = computed(() =>
  selectedRestaurantLabel.value === "your next stop"
    ? `Welcome, ${auth.user?.name || "guest"}.`
    : `Welcome, ${auth.user?.name || "guest"}. ${selectedRestaurantLabel.value} connected.`
);
const welcomeMessage = computed(() =>
  selectedRestaurantLabel.value === "your next stop"
    ? "Select a location to initiate your seamless ordering experience."
    : "Your portal is ready. Order quickly, track instantly, and enjoy."
);

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value;
}

function handleNavClick() {
  if (window.innerWidth <= 760) {
    mobileMenuOpen.value = false;
  }
}

async function loadRestaurantsForCustomer() {
  await auth.loadPublicRestaurants();
}

function triggerCustomerWelcome() {
  if (!auth.isCustomer || !auth.user?.id || !auth.justLoggedIn || route.name !== "customer-discover") {
    return;
  }
  welcomeVisible.value = true;
  auth.consumeLoginState();
}

function dismissWelcome() {
  welcomeVisible.value = false;
}

async function logout() {
  await auth.logout();
  router.push("/login");
}

onMounted(() => {
  triggerCustomerWelcome();
  loadRestaurantsForCustomer();
});

watch(() => route.fullPath, () => {
  if (window.innerWidth <= 760) {
    mobileMenuOpen.value = false;
  }
});

watch(() => auth.selectedRestaurantId, (value) => {
  selectedRestaurant.value = value || "";
});

watch(
  () => [auth.user?.id, auth.justLoggedIn, route.name],
  () => {
    triggerCustomerWelcome();
  }
);
</script>

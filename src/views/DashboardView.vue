<template>
  <section class="dashboard-stack">
    <section class="hero-panel panel owner-hero">
      <div class="hero-copy">
        <span class="eyebrow">Your Restaurant Space</span>
        <h1 class="hero-title">
          {{ restaurantName }} runs on Platrick like it owns the place.
        </h1>
        <p class="hero-text">
          This is your operational home. Add, remove, and shape the exact parts of the restaurant
          your team uses every day, with access tailored to {{ roleLabel }} work.
        </p>

        <div class="owner-actions">
          <RouterLink
            v-for="action in primaryActions"
            :key="action.label"
            class="button"
            :to="action.to"
          >
            {{ action.label }}
          </RouterLink>
        </div>
      </div>

      <div class="hero-aside">
        <div class="hero-badge owner-badge">
          <span class="hero-badge-label">Signed in as</span>
          <strong>{{ roleLabel }}</strong>
          <p class="muted owner-badge-copy">{{ roleCopy }}</p>
        </div>
        <div class="hero-notes">
          <div class="hero-note">
            <span class="hero-note-kicker">Private Site</span>
            <strong>{{ restaurantName }}</strong>
            <p class="muted">Only this restaurant's data, menu, staff, and dining flow live here.</p>
          </div>
          <div class="hero-note">
            <span class="hero-note-kicker">Default Landing</span>
            <strong>{{ landingLabel }}</strong>
            <p class="muted">Each staff role lands where they should start work first.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header panel-header-rich">
        <div>
          <span class="eyebrow">Control Rooms</span>
          <h2 class="panel-title">Run the parts of your restaurant that matter</h2>
        </div>
        <span class="badge badge-strong">{{ visibleEntities.length }} tools available</span>
      </header>

      <div class="content-pad owner-zones">
        <article v-for="zone in zones" :key="zone.title" class="owner-zone">
          <div class="owner-zone-head">
            <div>
              <span class="eyebrow">{{ zone.kicker }}</span>
              <h3 class="panel-title">{{ zone.title }}</h3>
            </div>
            <p class="muted">{{ zone.copy }}</p>
          </div>

          <div class="owner-link-grid">
            <RouterLink
              v-for="item in zone.items"
              :key="item.key"
              class="owner-link-card"
              :to="{ name: item.key }"
            >
              <strong>{{ item.label }}</strong>
              <span class="muted">{{ actionCopy(item) }}</span>
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <section class="story-grid role-grid">
      <article class="panel story-card story-card-dark role-card">
        <div class="content-pad">
          <span class="eyebrow">Role Route</span>
          <h3 class="panel-title">{{ roleLabel }} focus</h3>
          <p class="muted">{{ roleCopy }}</p>
        </div>
      </article>
      <article class="panel story-card role-card">
        <div class="content-pad">
          <span class="eyebrow">How It Feels</span>
          <h3 class="panel-title">Simple commands, real ownership</h3>
          <p class="muted">
            Open a section, add what the restaurant needs, remove what it does not, and keep each
            staff role inside the part of the portal they are responsible for.
          </p>
        </div>
      </article>
    </section>
  </section>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { entities } from "../config/entities";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();

const roleMeta = {
  manager: {
    label: "Owner / Manager",
    copy: "You oversee the whole restaurant: setup, staff, menu, floor, stock, and payments.",
    landingLabel: "Owner home"
  },
  cashier: {
    label: "Cashier",
    copy: "You focus on live service flow: orders, table movement, and payment follow-through.",
    landingLabel: "Orders"
  },
  barista: {
    label: "Barista / Kitchen",
    copy: "You focus on what gets prepared: menu readiness, order details, and service handoff.",
    landingLabel: "Menu Items"
  }
};

const zoneDefinitions = [
  {
    title: "Brand & Setup",
    kicker: "Identity",
    copy: "Shape the restaurant profile customers experience.",
    keys: ["restaurants", "menu_categories", "menu_items"]
  },
  {
    title: "Floor & Service",
    kicker: "Operations",
    copy: "Control orders, tables, sessions, and what the team sees in service.",
    keys: ["orders", "order_items", "order_status_history", "tables", "table_sessions", "payment_events"]
  },
  {
    title: "Team & Stock",
    kicker: "Internal",
    copy: "Assign people, shifts, ingredients, and back-of-house control.",
    keys: ["users", "shifts", "staff_shift_assignments", "ingredients", "inventory_transactions", "preferences"]
  }
];

const visibleEntities = computed(() =>
  entities.filter((entity) => {
    if (!entity.roles.includes(auth.role)) {
      return false;
    }

    return !entity.staffRoles || entity.staffRoles.includes(auth.staffRole);
  })
);

const restaurantName = computed(() => {
  const match = auth.publicRestaurants.find(
    (restaurant) => String(restaurant.id) === String(auth.user?.restaurant_id || "")
  );
  return match?.name || "Your restaurant";
});

const roleInfo = computed(() => roleMeta[auth.staffRole] || roleMeta.manager);
const roleLabel = computed(() => roleInfo.value.label);
const roleCopy = computed(() => roleInfo.value.copy);
const landingLabel = computed(() => roleInfo.value.landingLabel);

const primaryActions = computed(() => {
  if (auth.staffRole === "cashier") {
    return [
      { label: "Open Orders", to: { name: "orders" } },
      { label: "Manage Tables", to: { name: "tables" } },
      { label: "Track Payments", to: { name: "payment_events" } }
    ];
  }

  if (auth.staffRole === "barista") {
    return [
      { label: "Edit Menu", to: { name: "menu_items" } },
      { label: "View Orders", to: { name: "orders" } },
      { label: "Check Categories", to: { name: "menu_categories" } }
    ];
  }

  return [
    { label: "Edit Site", to: { name: "restaurant-builder" } },
    { label: "Add Menu Item", to: { name: "menu_items" } },
    { label: "Manage Team", to: { name: "users" } },
    { label: "Customer Voice", to: { name: "restaurant-pulse" } }
  ];
});

const zones = computed(() =>
  zoneDefinitions
    .map((zone) => ({
      ...zone,
      items: zone.keys
        .map((key) => visibleEntities.value.find((entity) => entity.key === key))
        .filter(Boolean)
    }))
    .filter((zone) => zone.items.length)
);

function actionCopy(item) {
  if (auth.staffRole === "manager") {
    return `Add, edit, or remove ${item.label.toLowerCase()} for ${restaurantName.value}.`;
  }

  if (auth.staffRole === "cashier") {
    return `Handle ${item.label.toLowerCase()} that support front-of-house service.`;
  }

  return `Work on ${item.label.toLowerCase()} that affect preparation and service flow.`;
}

onMounted(async () => {
  if (!auth.publicRestaurants.length) {
    await auth.loadPublicRestaurants();
  }
});
</script>

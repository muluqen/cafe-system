<template>
  <section class="customer-live-page" :style="themeVars">
    <section class="customer-live-hero">
      <div class="customer-live-brand">
        <div v-if="form.logoUrl" class="customer-live-logo-wrap">
          <img :src="form.logoUrl" :alt="`${form.name} logo`" class="customer-live-logo" />
        </div>
        <div v-else class="customer-live-logo-fallback">{{ logoInitials }}</div>
        <div>
          <span class="eyebrow customer-live-eyebrow">{{ form.name || "Your Restaurant" }} on Platrick</span>
          <h3>{{ form.headline || "Pick your spot, customize your meal, send it to the kitchen." }}</h3>
          <p class="muted">
            {{
              form.customerMessage ||
              "Everything here is for this restaurant only. Customers can customize ingredients and reorder quickly."
            }}
          </p>
        </div>
      </div>

      <div class="customer-live-hero-tools">
        <div class="restaurant-pill">
          <span class="restaurant-pill-label">Restaurant</span>
          <strong>{{ form.name || "Your Restaurant" }}</strong>
        </div>
        <label>
          Table (optional)
          <select class="input" disabled>
            <option value="">
              {{ tableOptions.length ? `${tableOptions[0].name} (${tableOptions[0].capacity} seats)` : "No table selected" }}
            </option>
          </select>
        </label>
      </div>
    </section>

    <section class="customer-live-layout">
      <section class="customer-live-panel">
        <header class="customer-live-header">
          <div>
            <span class="eyebrow">Menu</span>
            <h4>Available right now</h4>
          </div>
        </header>

        <div class="category-pills">
          <button v-for="category in categories" :key="category" class="pill-btn" type="button">
            {{ category }}
          </button>
        </div>

        <div class="customer-live-menu-grid">
          <article v-for="item in previewItems" :key="item.id" class="menu-card">
            <div>
              <strong>{{ item.name }}</strong>
              <p class="muted">{{ item.description || "Chef-crafted and made fresh." }}</p>
            </div>
            <div class="menu-card-foot">
              <strong>${{ Number(item.price || 0).toFixed(2) }}</strong>
              <div class="menu-actions">
                <button class="button button-soft" type="button">Order</button>
                <button class="button" type="button">Customize</button>
              </div>
            </div>
          </article>
          <div v-if="!previewItems.length" class="empty">No menu items yet. Add items in Step 3.</div>
        </div>
      </section>

      <section class="customer-live-panel customer-live-order">
        <header class="customer-live-header">
          <div>
            <span class="eyebrow">Your Order</span>
            <h4>What you're sending</h4>
          </div>
        </header>

        <div v-if="orderPreview.length" class="cart-list">
          <article v-for="entry in orderPreview" :key="entry.id" class="cart-item">
            <div>
              <strong>{{ entry.name }}</strong>
              <p class="muted">Standard preparation</p>
            </div>
            <div class="cart-qty">
              <button class="qty-btn" type="button">-</button>
              <span>{{ entry.quantity }}</span>
              <button class="qty-btn" type="button">+</button>
            </div>
          </article>
        </div>
        <div v-else class="empty">Nothing in your order yet.</div>

        <label>
          Kitchen note
          <textarea
            class="input kitchen-note"
            rows="4"
            placeholder="Share any serving or preparation note for the kitchen..."
            disabled
          />
        </label>

        <div class="totals">
          <span>Subtotal</span>
          <strong>${{ subtotal.toFixed(2) }}</strong>
          <span>Tax</span>
          <strong>${{ tax.toFixed(2) }}</strong>
          <span>Total</span>
          <strong>${{ total.toFixed(2) }}</strong>
        </div>

        <button class="button order-btn" type="button">Place order</button>
      </section>
    </section>
  </section>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  form: { type: Object, required: true },
  menuItems: { type: Array, required: true },
  tables: { type: Array, required: true }
});

const logoInitials = computed(() =>
  String(props.form.name || "R")
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase() || "R"
);

const tableOptions = computed(() => props.tables.filter((table) => table.is_active !== false).slice(0, 4));

const previewItems = computed(() => props.menuItems.filter((item) => item.is_available !== false).slice(0, 6));

const orderPreview = computed(() =>
  previewItems.value.slice(0, 2).map((item, index) => ({
    id: item.id || index,
    name: item.name,
    quantity: 1 + index,
    price: Number(item.price || 0)
  }))
);

const categories = computed(() => {
  const names = previewItems.value
    .map((item) => item.menu_category?.name || item.menuCategory?.name)
    .filter(Boolean);
  return [...new Set(names)].slice(0, 5);
});

const subtotal = computed(() => orderPreview.value.reduce((sum, row) => sum + row.price * row.quantity, 0));
const tax = computed(() => subtotal.value * 0.1);
const total = computed(() => subtotal.value + tax.value);

const themeVars = computed(() => ({
  "--restaurant-brand": props.form.brandColor,
  "--restaurant-brand-soft": `${props.form.brandColor}22`,
  "--restaurant-brand-gradient": `linear-gradient(135deg, ${(props.form.brandColors || [props.form.brandColor]).join(", ")})`
}));
</script>

<template>
  <div class="detail">
    <!-- Back Link -->
    <div class="detail__top-bar">
      <RouterLink to="/discover" class="detail__back">← Back to restaurants</RouterLink>
    </div>

    <div v-if="loading" class="detail__loading">
      <SkeletonLoader type="card" :lines="5" />
    </div>

    <template v-else-if="restaurant">
      <!-- Restaurant Header -->
      <div class="detail-header">
        <div class="detail-header__gradient" />
        <div class="detail-header__content">
          <img v-if="settings?.logo_path" :src="settings.logo_path" alt="Logo" class="detail-header__logo" />
          <h1 class="detail-header__name">{{ restaurant.name }}</h1>
          <span v-if="settings?.motto || restaurant.cuisine_type" class="detail-header__cuisine">
            {{ settings?.motto || restaurant.cuisine_type }}
          </span>
          <p v-if="restaurant.description" class="detail-header__desc">{{ restaurant.description }}</p>
          <p v-if="settings?.banner_message" class="detail-header__banner">{{ settings.banner_message }}</p>
        </div>
      </div>

      <!-- Today's Special Banner -->
      <div v-if="todaySpecial" class="special-banner">
        <span class="special-banner__label">Today's Special</span>
        <span class="special-banner__name">{{ todaySpecial.name }}</span>
        <span v-if="todaySpecial.price" class="special-banner__price">${{ Number(todaySpecial.price).toFixed(2) }}</span>
      </div>

      <!-- Active Order Banner (logged in only) -->
      <div v-if="isLoggedIn && currentActiveOrder" class="active-order-banner">
        <div class="active-order-banner__pulse"></div>
        <div class="active-order-banner__info">
          <span class="active-order-banner__label">Active Order</span>
          <span class="active-order-banner__number">#{{ currentActiveOrder.order_number }}</span>
          <span class="active-order-banner__status">{{ currentActiveOrder.status }}</span>
        </div>
        <button class="active-order-banner__btn" @click="router.push('/customer/orders')">
          Track Order →
        </button>
      </div>

      <!-- Two Panel Layout -->
      <div class="detail-panels">
        <!-- LEFT: Menu -->
        <div class="detail-menu">
          <!-- Build Your Own -->
          <div v-if="allIngredients.length" class="build-your-own">
            <div class="build-your-own__header">
              <div>
                <h2 class="build-your-own__title">Build Your Own</h2>
                <p class="build-your-own__desc">Create a custom dish from {{ allIngredients.length }} fresh ingredients</p>
              </div>
              <BaseButton variant="primary" size="sm" @click="showBuildOwn = true">Start</BaseButton>
            </div>
          </div>

          <!-- Category Tabs (sticky) -->
          <div :class="['category-tabs', { 'tabs--with-banner': hasActiveOrder }]">
            <button
              v-for="cat in categories"
              :key="cat.id"
              :class="['category-tab', { 'category-tab--active': activeCategory === cat.id }]"
              @click="scrollToCategory(cat.id)"
            >
              {{ cat.name }}
            </button>
          </div>

          <!-- Menu Sections -->
          <div v-for="cat in categories" :key="cat.id" :id="`cat-${cat.id}`" class="menu-section">
            <h2 class="menu-section__title">{{ cat.name }}</h2>
            <div class="menu-items">
              <div
                v-for="item in getItemsByCategory(cat.id)"
                :key="item.id"
                :class="['menu-item', { 'menu-item--unavailable': !item.is_available }]"
              >
                <div class="menu-item__info">
                  <h3 class="menu-item__name">{{ item.name }}</h3>
                  <p v-if="item.description" class="menu-item__desc">{{ item.description }}</p>
                  <span class="menu-item__price">${{ Number(item.price).toFixed(2) }}</span>
                </div>
                <div class="menu-item__action">
                  <template v-if="item.is_available !== false">
                    <div class="menu-item__actions">
                      <template v-if="cartStore.itemCount(item.id) === 0">
                        <BaseButton variant="primary" size="sm" @click="addToCart(item)">+ Add</BaseButton>
                      </template>
                      <template v-else>
                        <div class="qty-controls">
                          <button class="qty-btn" @click="cartStore.decrementItem(item.id)">−</button>
                          <span class="qty-value">{{ cartStore.itemCount(item.id) }}</span>
                          <button class="qty-btn" @click="cartStore.incrementItem(item.id)">+</button>
                        </div>
                      </template>
                      <BaseButton v-if="hasIngredients(item)" variant="ghost" size="sm" @click="openCustomizer(item)">
                        Customize
                      </BaseButton>
                    </div>
                  </template>
                  <span v-else class="unavailable-badge">Unavailable</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Cart (Desktop) -->
        <div class="detail-cart">
          <div class="cart-panel">
            <h2 class="cart-panel__title">Your Order</h2>

            <!-- Order Type Toggle -->
            <div class="order-type-toggle">
              <button
                :class="['toggle-btn', { 'toggle-btn--active': cartStore.order_type === 'dine_in' }]"
                @click="cartStore.setOrderType('dine_in')"
              >
                Dine In
              </button>
              <button
                :class="['toggle-btn', { 'toggle-btn--active': cartStore.order_type === 'takeaway' }]"
                @click="cartStore.setOrderType('takeaway')"
              >
                Takeaway
              </button>
            </div>

            <!-- Table Input (dine in) -->
            <BaseInput
              v-if="cartStore.order_type === 'dine_in'"
              v-model="tableInput"
              label="Table Number"
              placeholder="Optional"
              type="number"
            />

            <!-- Cart Items -->
            <div v-if="cartStore.isEmpty" class="cart-empty">
              <div class="cart-empty__icon">🛒</div>
              <p class="cart-empty__text">Add items to get started</p>
            </div>

            <div v-else class="cart-items">
              <div v-for="item in cartStore.items" :key="item.menu_item_id" class="cart-item">
                <div class="cart-item__info">
                  <div>
                    <span class="cart-item__name">{{ item.name }}</span>
                    <span v-if="item.calories_per_item" class="cart-item__cal">{{ Math.round(item.calories_per_item) }} kcal</span>
                    <div v-if="item.customized_ingredients?.length" class="cart-item__custom">
                      <span v-for="ci in item.customized_ingredients.slice(0, 3)" :key="ci.ingredient_id" class="custom-tag">
                        {{ ci.quantity_required }}{{ ci.unit }} {{ ci.name }}
                      </span>
                      <span v-if="item.customized_ingredients.length > 3" class="custom-tag custom-tag--more">
                        +{{ item.customized_ingredients.length - 3 }} more
                      </span>
                    </div>
                  </div>
                  <span class="cart-item__price">${{ getItemTotal(item).toFixed(2) }}</span>
                </div>
                <div class="cart-item__controls">
                  <button class="qty-btn" @click="cartStore.decrementItem(item.menu_item_id)">−</button>
                  <span class="qty-value">{{ item.quantity }}</span>
                  <button class="qty-btn" @click="cartStore.incrementItem(item.menu_item_id)">+</button>
                  <button class="cart-item__remove" @click="cartStore.removeItem(item.menu_item_id)">×</button>
                </div>
              </div>
            </div>

            <!-- Totals -->
            <div v-if="!cartStore.isEmpty" class="cart-totals">
              <div v-if="cartStore.totalCalories > 0" class="cart-total-row cart-total-row--cal">
                <span>Total Calories</span>
                <span>{{ cartStore.totalCalories.toFixed(0) }} kcal</span>
              </div>
              <div class="cart-total-row">
                <span>Subtotal</span>
                <span>${{ cartStore.totalPrice.toFixed(2) }}</span>
              </div>
              <div class="cart-total-row">
                <span>Tax (10%)</span>
                <span>${{ cartStore.taxAmount.toFixed(2) }}</span>
              </div>
              <div class="cart-total-row cart-total-row--final">
                <span>Total</span>
                <span>${{ cartStore.grandTotal.toFixed(2) }}</span>
              </div>
              <BaseButton
                variant="primary"
                :loading="ordering"
                :disabled="cartStore.isEmpty || ordering"
                style="width: 100%"
                @click="handlePlaceOrder"
              >
                Place Order
              </BaseButton>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact & Hours Info -->
      <div v-if="settings?.phone || settings?.address || settings?.operating_hours" class="detail-info">
        <div v-if="settings?.phone || settings?.address" class="detail-info__contact">
          <h3>Contact</h3>
          <p v-if="settings?.phone">{{ settings.phone }}</p>
          <p v-if="settings?.address">{{ settings.address }}</p>
        </div>
        <div v-if="settings?.operating_hours" class="detail-info__hours">
          <h3>Hours</h3>
          <div v-for="day in ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']" :key="day" class="hours-row">
            <span class="hours-row__day">{{ day.charAt(0).toUpperCase() + day.slice(1) }}</span>
            <span class="hours-row__time">{{ formatHours(day) }}</span>
          </div>
        </div>
      </div>

      <!-- Mobile Cart Bar -->
      <div v-if="!cartStore.isEmpty" class="mobile-cart-bar">
        <div class="mobile-cart-bar__info">
          <span class="mobile-cart-bar__count">{{ cartStore.totalItems }} items</span>
          <span class="mobile-cart-bar__total">${{ cartStore.totalPrice.toFixed(2) }}</span>
        </div>
        <BaseButton variant="primary" @click="handlePlaceOrder">View Order</BaseButton>
      </div>
    </template>

    <!-- Ingredient Customizer Modal -->
    <BaseModal v-model="showCustomizer" :title="customizerItem ? `Customize ${customizerItem.name}` : 'Customize'" size="md">
      <IngredientCustomizer
        v-if="customizerItem"
        :menu-item="customizerItem"
        :recipe-ingredients="customizerItem.recipe_ingredients || customizerItem.recipeIngredients || []"
        :restaurant-id="route.params.id"
        :restaurant-name="restaurant?.name || ''"
        @close="showCustomizer = false; customizerItem = null"
        @added="handleCustomizerAdded"
      />
    </BaseModal>

    <!-- Build Your Own Modal -->
    <BaseModal v-model="showBuildOwn" title="Build Your Own Dish" size="md">
      <div class="build-own-modal">
        <p class="build-own-modal__desc">Select ingredients and set quantities to create your custom dish</p>

        <!-- Search -->
        <div class="build-own-modal__search">
          <input v-model="buildOwnSearch" type="text" class="build-own-search-input" placeholder="Search ingredients..." />
        </div>

        <!-- Ingredients (scrollable) -->
        <div class="build-own-modal__ingredients">
          <div v-if="filteredIngredients.length === 0" class="build-own-modal__empty">
            No ingredients match "{{ buildOwnSearch }}"
          </div>
          <div v-for="ing in filteredIngredients" :key="ing.id" class="build-own-row">
            <div class="build-own-row__info">
              <span class="build-own-row__name">{{ ing.name }}</span>
              <span class="build-own-row__unit">{{ ing.unit }}</span>
              <span v-if="ing.calories_per_unit" class="build-own-row__cal">{{ ing.calories_per_unit }} kcal/{{ ing.unit }}</span>
            </div>
            <div class="build-own-row__controls">
              <button class="qty-btn" @click="buildOwnDecrement(ing)">−</button>
              <input
                type="number"
                class="build-own-input"
                :value="buildOwnQty[ing.id] || 0"
                @input="buildOwnQty[ing.id] = parseFloat($event.target.value) || 0"
                min="0"
                :step="isWeight(ing.unit) ? 10 : 1"
              />
              <span class="build-own-row__unit-label">{{ ing.unit }}</span>
              <button class="qty-btn" @click="buildOwnIncrement(ing)">+</button>
            </div>
          </div>
        </div>

        <!-- Sticky Summary + Add Button -->
        <div class="build-own-modal__bottom">
          <div class="build-own-modal__summary-sticky">
            <div class="build-own-summary-item">
              <span class="build-own-summary-item__value">{{ buildOwnCalories.toFixed(0) }}</span>
              <span class="build-own-summary-item__label">kcal</span>
            </div>
            <div class="build-own-summary-item">
              <span class="build-own-summary-item__value">${{ buildOwnPrice.toFixed(2) }}</span>
              <span class="build-own-summary-item__label">Price</span>
            </div>
            <div class="build-own-summary-item">
              <span class="build-own-summary-item__value">{{ buildOwnSelectedCount }}</span>
              <span class="build-own-summary-item__label">Items</span>
            </div>
          </div>
          <button class="build-own-modal__add" :disabled="buildOwnSelectedCount === 0" @click="addBuildOwnToCart">
            Add Custom Dish · ${{ buildOwnPrice.toFixed(2) }}
          </button>
        </div>
      </div>
    </BaseModal>

    <!-- Unavailable Ingredients Modal -->
    <BaseModal v-model="showUnavailableModal" title="Some Ingredients Unavailable" size="md">
      <div class="unavailable-modal">
        <p class="unavailable-modal__notice">Some ingredients are out of stock. Uncheck the ones you don't want, then place your order:</p>
        <div v-for="item in unavailableItems" :key="item.item_name" class="unavailable-modal__item">
          <div class="unavailable-modal__item-header">
            <span class="unavailable-modal__item-name">{{ item.item_name }}</span>
          </div>
          <div v-for="(ing, idx) in item.missing_ingredients" :key="idx" class="unavailable-modal__ingredient">
            <label class="unavailable-modal__checkbox">
              <input type="checkbox" :checked="!ing.unchecked" @change="ing.unchecked = !ing.unchecked">
              <span class="unavailable-modal__ing-name">{{ ing.ingredient }}</span>
            </label>
            <span class="unavailable-modal__stock">Need: {{ ing.required }} | Available: {{ ing.available }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="showUnavailableModal = false">Cancel</BaseButton>
        <BaseButton variant="primary" @click="retryOrderWithoutIngredients">Place Order Without These</BaseButton>
      </template>
    </BaseModal>

    <!-- Order Success Modal -->
    <BaseModal v-model="showSuccessModal" title="Order Placed!" size="sm">
      <div class="success-modal">
        <div class="success-modal__icon">✅</div>
        <p v-if="lastOrder" class="success-modal__number">Order #{{ lastOrder.order_number }}</p>
        <p class="success-modal__text">Your order is being prepared</p>
        <p class="success-modal__eta">Estimated time: 15-30 minutes</p>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="showSuccessModal = false">Order More</BaseButton>
        <BaseButton variant="primary" @click="goToOrders">
          📍 Track Your Order
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useCartStore } from "../stores/cartStore";
import { useAuthStore } from "../stores/authStore";
import { useActiveOrders } from "../composables/useActiveOrders";
import customerService from "../services/customerService";
import api from "../services/api";
import { useToast } from "../composables/useToast";
import BaseButton from "../components/ui/BaseButton.vue";
import BaseInput from "../components/ui/BaseInput.vue";
import BaseModal from "../components/ui/BaseModal.vue";
import SkeletonLoader from "../components/ui/SkeletonLoader.vue";
import IngredientCustomizer from "./IngredientCustomizer.vue";

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();
const toast = useToast();
const { activeOrders, count: activeOrderCount, fetchActiveOrders, startPolling, stopPolling, hasActiveForRestaurant, getActiveForRestaurant } = useActiveOrders();

const restaurant = ref(null);
const settings = ref(null);
const menuItems = ref([]);
const categories = ref([]);
const allIngredients = ref([]);
const loading = ref(true);
const ordering = ref(false);
const activeCategory = ref(null);
const tableInput = ref("");

const showSuccessModal = ref(false);
const showUnavailableModal = ref(false);
const unavailableItems = ref([]);
const lastOrder = ref(null);
const customizerItem = ref(null);
const showCustomizer = ref(false);
const showBuildOwn = ref(false);
const buildOwnSearch = ref("");



const todaySpecial = computed(() => settings.value?.today_special || settings.value?.todaySpecial || null);

const isLoggedIn = computed(() => {
  return !!authStore.token && !!authStore.user
})

const restaurantActiveOrders = computed(() => {
  if (!restaurant.value) return []
  return getActiveForRestaurant(restaurant.value.id)
})

const currentActiveOrder = computed(() => {
  if (restaurantActiveOrders.value.length === 0) return null
  return restaurantActiveOrders.value[0]
})

const hasActiveOrder = computed(() => currentActiveOrder.value !== null)

const brandColors = computed(() => {
  const bc = settings.value?.brand_colors;
  if (!bc) return {};
  if (Array.isArray(bc)) {
    return {
      primary: bc[0] || null,
      secondary: bc[1] || null,
      accent: bc[2] || null,
      success: bc[3] || null,
      danger: bc[4] || null,
    };
  }
  return bc;
});

function hexToRgb(hex) {
  const h = hex.replace("#", "");
  return {
    r: parseInt(h.substring(0, 2), 16),
    g: parseInt(h.substring(2, 4), 16),
    b: parseInt(h.substring(4, 6), 16),
  };
}

function rgbToHex(r, g, b) {
  return "#" + [r, g, b].map((v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, "0")).join("");
}

function lighten(hex, amount = 0.25) {
  const { r, g, b } = hexToRgb(hex);
  return rgbToHex(r + (255 - r) * amount, g + (255 - g) * amount, b + (255 - b) * amount);
}

function darken(hex, amount = 0.2) {
  const { r, g, b } = hexToRgb(hex);
  return rgbToHex(r * (1 - amount), g * (1 - amount), b * (1 - amount));
}

function rgba(hex, alpha) {
  const { r, g, b } = hexToRgb(hex);
  return `rgba(${r},${g},${b},${alpha})`;
}

function applyBrandColors() {
  const colors = brandColors.value;
  if (!colors || typeof colors !== "object") return;
  const root = document.documentElement;

  if (colors.primary) {
    root.style.setProperty("--color-primary", colors.primary);
    root.style.setProperty("--color-primary-light", lighten(colors.primary, 0.25));
    root.style.setProperty("--color-primary-dark", darken(colors.primary, 0.2));
    root.style.setProperty("--color-primary-glow", rgba(colors.primary, 0.22));
    root.style.setProperty("--color-warm", colors.primary);
    root.style.setProperty("--color-warm-light", lighten(colors.primary, 0.25));
    root.style.setProperty("--color-warm-dark", darken(colors.primary, 0.2));
    root.style.setProperty("--color-warm-glow", rgba(colors.primary, 0.22));
    root.style.setProperty("--color-border-focus", colors.primary);
  }
  if (colors.accent) {
    root.style.setProperty("--color-accent", colors.accent);
    root.style.setProperty("--color-accent-light", lighten(colors.accent, 0.25));
    root.style.setProperty("--color-accent-dark", darken(colors.accent, 0.2));
    root.style.setProperty("--color-accent-glow", rgba(colors.accent, 0.22));
    root.style.setProperty("--color-cool", colors.accent);
    root.style.setProperty("--color-cool-light", lighten(colors.accent, 0.25));
    root.style.setProperty("--color-cool-dark", darken(colors.accent, 0.2));
    root.style.setProperty("--color-cool-glow", rgba(colors.accent, 0.22));
    root.style.setProperty("--color-info", colors.accent);
    root.style.setProperty("--color-info-light", rgba(colors.accent, 0.12));
  }
  if (colors.success) {
    root.style.setProperty("--color-success", colors.success);
    root.style.setProperty("--color-success-light", rgba(colors.success, 0.12));
  }
  if (colors.danger) {
    root.style.setProperty("--color-danger", colors.danger);
    root.style.setProperty("--color-danger-light", rgba(colors.danger, 0.12));
    root.style.setProperty("--color-warning", colors.danger);
    root.style.setProperty("--color-warning-light", rgba(colors.danger, 0.12));
  }
}

function formatHours(day) {
  const h = settings.value?.operating_hours?.[day];
  if (!h || !h.open || !h.close) return "Closed";
  return `${h.open} – ${h.close}`;
}

function getItemsByCategory(catId) {
  return menuItems.value.filter((item) => item.menu_category_id === catId);
}

function scrollToCategory(catId) {
  activeCategory.value = catId;
  document.getElementById(`cat-${catId}`)?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function addToCart(item) {
  cartStore.addItem(item, parseInt(route.params.id), restaurant.value?.name);
}

function hasIngredients(item) {
  return item.recipe_ingredients?.length > 0 || item.recipeIngredients?.length > 0;
}

function openCustomizer(item) {
  customizerItem.value = item;
  showCustomizer.value = true;
}

function handleCustomizerAdded() {
  toast.success("Customized item added to cart");
}

function getIngredientCount(item) {
  const ri = item.recipe_ingredients || item.recipeIngredients || [];
  return ri.length;
}

import {
  buildOwnPrice as calcBuildOwnPrice,
  buildOwnSelectedCount as calcBuildOwnSelectedCount,
  buildOwnSelectedIngredients,
  mealPrice,
} from "../services/pricingService";
import {
  buildOwnCalories as calcBuildOwnCalories,
} from "../services/calorieService";

function getItemTotal(item) {
  return (Number(item.price) || 0) * item.quantity;
}

// Build Your Own
const buildOwnQty = reactive({});

const filteredIngredients = computed(() => {
  const q = buildOwnSearch.value.toLowerCase().trim();
  if (!q) return allIngredients.value;
  return allIngredients.value.filter(ing => ing.name.toLowerCase().includes(q));
});

function isWeight(unit) {
  return ["g", "kg", "ml", "l", "liter"].includes((unit || "").toLowerCase());
}

function buildOwnIncrement(ing) {
  const step = isWeight(ing.unit) ? 10 : 1;
  buildOwnQty[ing.id] = (buildOwnQty[ing.id] || 0) + step;
}

function buildOwnDecrement(ing) {
  const step = isWeight(ing.unit) ? 10 : 1;
  const cur = buildOwnQty[ing.id] || 0;
  if (cur > 0) buildOwnQty[ing.id] = Math.max(0, cur - step);
}

const buildOwnSelectedCount = computed(() =>
  calcBuildOwnSelectedCount(allIngredients.value, buildOwnQty)
);

const buildOwnCalories = computed(() =>
  calcBuildOwnCalories(allIngredients.value, buildOwnQty)
);

const buildOwnPrice = computed(() =>
  calcBuildOwnPrice(allIngredients.value, buildOwnQty)
);

function addBuildOwnToCart() {
  if (buildOwnSelectedCount.value === 0) return;

  const selected = buildOwnSelectedIngredients(allIngredients.value, buildOwnQty);

  const virtualItem = {
    id: null,
    name: "Build Your Own",
    price: 0,
  };

  cartStore.addItem(virtualItem, parseInt(route.params.id), restaurant.value?.name, {
    customized_ingredients: selected,
    calories_per_item: Math.round(buildOwnCalories.value),
    unit_price: buildOwnPrice.value,
  });

  showBuildOwn.value = false;
  toast.success("Custom dish added to cart");
}

async function placeOrder() {
  if (cartStore.isEmpty) {
    toast.warning('Your cart is empty')
    return
  }
  if (!restaurant.value?.id) {
    toast.error('Restaurant not found')
    return
  }

  ordering.value = true;
  try {
    const payload = {
      restaurant_id: restaurant.value?.id,
      order_type: cartStore.order_type || "dine_in",
      cart: cartStore.items.map((item) => {
        const cartItem = {
          menu_item_id: item.menu_item_id,
          name: item.name,
          price: item.price,
          quantity: item.quantity,
          is_custom: !item.menu_item_id || String(item.menu_item_id).startsWith("buildown_"),
        };
        if (item.customized_ingredients?.length) {
          cartItem.customized_ingredients = item.customized_ingredients.map((ci) => ({
            ingredient_id: ci.ingredient_id,
            name: ci.name,
            quantity_required: ci.quantity_required,
            default_quantity: ci.default_quantity,
            unit: ci.unit,
            cost_per_unit: ci.cost_per_unit,
            calories_per_unit: ci.calories_per_unit,
          }));
        }
        return cartItem;
      }),
      subtotal: cartStore.totalPrice,
      tax: cartStore.taxAmount,
      total: cartStore.grandTotal,
    };

    if (cartStore.order_type === "dine_in" && tableInput.value) {
      payload.table_id = parseInt(tableInput.value);
    }

    const { data } = await customerService.placeOrder(payload);
    lastOrder.value = data?.data || data;
    showSuccessModal.value = true;
    cartStore.clearCart();
    fetchActiveOrders();
    toast.success("Order placed successfully!", 5000, {
      label: "📍 View Order",
      onClick: () => {
        showSuccessModal.value = false;
        router.push("/customer/orders");
      },
    });
  } catch (e) {
    const data = e?.response?.data;
    if (e?.response?.status === 422 && data?.errors?.items) {
      unavailableItems.value = data.errors.items.map(item => ({
        ...item,
        missing_ingredients: item.missing_ingredients.map(ing => ({
          ...ing,
          unchecked: false,
        })),
      }));
      showUnavailableModal.value = true;
    } else {
      toast.error(data?.message || "Failed to place order");
    }
  } finally {
    ordering.value = false;
  }
}

async function handlePlaceOrder() {
  const token = localStorage.getItem('tavliq_token')
  if (!token) {
    router.push('/login')
    return
  }

  if (!authStore.user) {
    try {
      await authStore.fetchMe()
    } catch(e) {
      router.push('/login')
      return
    }
  }

  await placeOrder()
}

function goToOrders() {
  showSuccessModal.value = false
  cartStore.clearCart()
  router.push('/customer/orders')
}

async function retryOrderWithoutIngredients() {
  showUnavailableModal.value = false

  const removedMap = {}
  for (const item of unavailableItems.value) {
    removedMap[item.item_name] = item.missing_ingredients
      .filter(i => i.unchecked)
      .map(i => i.ingredient)
  }

  unavailableItems.value = []

  if (!restaurant.value?.id) return
  ordering.value = true

  try {
    const payload = {
      restaurant_id: restaurant.value?.id,
      order_type: cartStore.order_type || "dine_in",
      cart: cartStore.items.map((item) => {
        const cartItem = {
          menu_item_id: item.menu_item_id,
          name: item.name,
          price: item.price,
          quantity: item.quantity,
          is_custom: !item.menu_item_id || String(item.menu_item_id).startsWith("buildown_"),
          removed_ingredients: removedMap[item.name] || [],
        };
        if (item.customized_ingredients?.length) {
          cartItem.customized_ingredients = item.customized_ingredients.map((ci) => ({
            ingredient_id: ci.ingredient_id,
            name: ci.name,
            quantity_required: ci.quantity_required,
            default_quantity: ci.default_quantity,
            unit: ci.unit,
            cost_per_unit: ci.cost_per_unit,
            calories_per_unit: ci.calories_per_unit,
          }));
        }
        return cartItem;
      }),
      subtotal: cartStore.totalPrice,
      tax: cartStore.taxAmount,
      total: cartStore.grandTotal,
    };

    if (cartStore.order_type === "dine_in" && tableInput.value) {
      payload.table_id = parseInt(tableInput.value);
    }

    const { data } = await customerService.placeOrder(payload);
    lastOrder.value = data?.data || data;
    showSuccessModal.value = true;
    cartStore.clearCart();
    fetchActiveOrders();
    toast.success("Order placed successfully!", 5000, {
      label: "📍 View Order",
      onClick: () => {
        showSuccessModal.value = false;
        router.push("/customer/orders");
      },
    });
  } catch (e) {
    const data = e?.response?.data;
    if (e?.response?.status === 422 && data?.errors?.items) {
      unavailableItems.value = data.errors.items.map(item => ({
        ...item,
        missing_ingredients: item.missing_ingredients.map(ing => ({
          ...ing,
          unchecked: false,
        })),
      }));
      showUnavailableModal.value = true;
    } else {
      toast.error(data?.message || "Failed to place order");
    }
  } finally {
    ordering.value = false;
  }
}

function handleDebugLogout() {
  authStore.logoutLocal()
  window.location.reload()
}

onMounted(async () => {
  try {
    const [restRes, menuRes, catRes, ingRes, settingsRes] = await Promise.all([
      customerService.getRestaurant(route.params.id),
      customerService.getMenuItems(route.params.id),
      customerService.getMenuCategories(route.params.id),
      customerService.getIngredients(route.params.id),
      customerService.getRestaurantSettings(route.params.id).catch(() => null),
    ]);
    restaurant.value = restRes.data?.data || restRes.data;
    const menuRaw = menuRes.data?.data;
    menuItems.value = Array.isArray(menuRaw) ? menuRaw : Array.isArray(menuRaw?.data) ? menuRaw.data : [];
    const catRaw = catRes.data?.data;
    categories.value = Array.isArray(catRaw) ? catRaw : Array.isArray(catRaw?.data) ? catRaw.data : [];
    const ingRaw = ingRes.data?.data;
    allIngredients.value = Array.isArray(ingRaw) ? ingRaw : Array.isArray(ingRaw?.data) ? ingRaw.data : [];
    if (settingsRes) {
      const sData = settingsRes.data?.data || settingsRes.data;
      settings.value = sData?.settings || sData || null;
      applyBrandColors();
    }
    if (categories.value.length) activeCategory.value = categories.value[0].id;
  } catch (e) {
    console.error("Failed to load restaurant", e);
  } finally {
    loading.value = false;
  }

  await nextTick();
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el)
  })

  if (authStore.token && authStore.user) {
    startPolling(15000);
  }
});

onUnmounted(() => {
  stopPolling();
});
</script>

<style scoped>
.detail {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--space-6);
}

.detail__top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}

.detail__back {
  color: var(--color-accent);
  text-decoration: none;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
}

.detail__logout {
  padding: 0.375rem 0.875rem;
  background: rgba(244,63,94,0.08);
  border: 1px solid rgba(244,63,94,0.20);
  border-radius: var(--radius-md);
  color: #F43F5E;
  font-size: var(--text-sm);
  font-weight: 600;
  cursor: pointer;
  transition: all 150ms ease;
}

.detail__logout:hover {
  background: rgba(244,63,94,0.15);
  border-color: #F43F5E;
}

.detail__loading {
  padding: var(--space-8);
}

/* ── Header ── */
.detail-header {
  position: relative;
  border-radius: var(--radius-2xl);
  overflow: hidden;
  margin-bottom: var(--space-6);
  padding: var(--space-12) var(--space-8);
}

.detail-header__gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
}

.detail-header__content {
  position: relative;
  color: white;
}

.detail-header__name {
  margin: 0 0 var(--space-2);
  font-size: var(--text-3xl);
  font-weight: var(--font-extrabold);
}

.detail-header__cuisine {
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  background: rgba(255, 255, 255, 0.2);
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  margin-bottom: var(--space-3);
}

.detail-header__desc {
  margin: 0;
  opacity: 0.9;
  line-height: 1.5;
}

.detail-header__logo {
  width: 72px;
  height: 72px;
  border-radius: var(--radius-xl);
  object-fit: cover;
  border: 3px solid rgba(255, 255, 255, 0.3);
  margin-bottom: var(--space-3);
}

.detail-header__banner {
  margin: var(--space-3) 0 0;
  padding: var(--space-2) var(--space-4);
  background: rgba(255, 255, 255, 0.15);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  display: inline-block;
}

/* ── Today's Special Banner ── */
.special-banner {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-5);
  background: linear-gradient(135deg, rgba(255, 60, 172, 0.1), rgba(6, 182, 212, 0.08));
  border: 1px solid rgba(255, 60, 172, 0.25);
  border-radius: var(--radius-xl);
  margin-bottom: var(--space-6);
}

.special-banner__label {
  font-size: var(--text-xs);
  font-weight: var(--font-bold);
  text-transform: uppercase;
  color: var(--color-accent);
  letter-spacing: 0.05em;
}

.special-banner__name {
  font-size: var(--text-base);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
  flex: 1;
}

.special-banner__price {
  font-size: var(--text-base);
  font-weight: var(--font-bold);
  color: var(--color-accent);
}

/* ── Active Order Banner ── */
.active-order-banner {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding: var(--space-3) var(--space-5);
  background: #111113;
  border-bottom: 1px solid rgba(16,185,129,0.2);
  position: sticky;
  top: 64px;
  z-index: 150;
}

.active-order-banner__pulse {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #10B981;
  flex-shrink: 0;
  animation: banner-pulse 1.5s ease-in-out infinite;
}

@keyframes banner-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
  50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
}

.active-order-banner__info {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex: 1;
}

.active-order-banner__label {
  font-size: var(--text-xs);
  font-weight: var(--font-bold);
  text-transform: uppercase;
  color: #10B981;
  letter-spacing: 0.05em;
}

.active-order-banner__number {
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.active-order-banner__status {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  padding: 2px 8px;
  background: var(--color-bg-subtle);
  border-radius: var(--radius-sm);
  text-transform: capitalize;
}

.active-order-banner__btn {
  padding: var(--space-2) var(--space-4);
  border: none;
  border-radius: var(--radius-md);
  background: rgba(16, 185, 129, 0.2);
  color: #10B981;
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
  cursor: pointer;
  transition: all 150ms ease;
  white-space: nowrap;
}

.active-order-banner__btn:hover {
  background: rgba(16, 185, 129, 0.35);
  transform: translateY(-1px);
}

/* ── Contact & Hours ── */
.detail-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-6);
  margin-top: var(--space-8);
  padding: var(--space-6);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
}

.detail-info h3 {
  margin: 0 0 var(--space-3);
  font-size: var(--text-base);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.detail-info__contact p {
  margin: 0 0 var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.hours-row {
  display: flex;
  justify-content: space-between;
  padding: var(--space-1) 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.hours-row__day {
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
  text-transform: capitalize;
}

/* ── Two Panels ── */
.detail-panels {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: var(--space-6);
  align-items: start;
}

/* ── Menu Panel ── */
.category-tabs {
  display: flex;
  gap: var(--space-2);
  overflow-x: auto;
  padding: var(--space-3) 0;
  margin-bottom: var(--space-6);
  border-bottom: 1px solid #27272A;
  position: sticky;
  top: 64px;
  z-index: 100;
  background: #09090B;
}

.category-tabs.tabs--with-banner {
  top: 112px;
}

.category-tab {
  padding: var(--space-2) var(--space-4);
  border: none;
  border-radius: var(--radius-md);
  background: transparent;
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  white-space: nowrap;
  cursor: pointer;
  transition: all var(--transition-fast);
  border-bottom: 2px solid transparent;
}

.category-tab--active {
  color: var(--color-accent);
  border-bottom-color: var(--color-accent);
}

.menu-section {
  margin-bottom: var(--space-8);
}

.menu-section__title {
  margin: 0 0 var(--space-4);
  font-size: var(--text-xl);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.menu-items {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.menu-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  transition: border-color var(--transition-fast);
}

.menu-item:hover {
  border-color: rgba(249,194,46,0.3);
  box-shadow: 0 4px 20px rgba(249,194,46,0.10);
}

.menu-item--unavailable {
  opacity: 0.5;
  pointer-events: none;
}

.menu-item__info {
  flex: 1;
  min-width: 0;
}

.menu-item__name {
  margin: 0 0 var(--space-1);
  font-size: var(--text-base);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.menu-item__desc {
  margin: 0 0 var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.menu-item__price {
  font-size: var(--text-base);
  font-weight: var(--font-bold);
  color: var(--color-accent);
}

.menu-item__action {
  margin-left: var(--space-4);
}

.menu-item__actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--space-2);
}

.qty-controls {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.qty-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-primary);
  font-size: var(--text-lg);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
}

.qty-btn:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-glow);
}

.qty-value {
  min-width: 24px;
  text-align: center;
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.unavailable-badge {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

/* ── Cart Panel ── */
.cart-panel {
  position: sticky;
  top: 64px;
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-5);
}

.cart-panel__title {
  margin: 0 0 var(--space-4);
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.order-type-toggle {
  display: flex;
  background: var(--color-bg-subtle);
  border-radius: var(--radius-md);
  padding: 3px;
  margin-bottom: var(--space-4);
}

.toggle-btn {
  flex: 1;
  padding: var(--space-2);
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.toggle-btn--active {
  background: var(--color-primary);
  color: white;
}

.cart-empty {
  text-align: center;
  padding: var(--space-8) 0;
}

.cart-empty__icon {
  font-size: 2rem;
  margin-bottom: var(--space-2);
}

.cart-empty__text {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  margin-bottom: var(--space-4);
}

.cart-item__info {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--space-1);
}

.cart-item__name {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
}

.cart-item__cal {
  display: block;
  font-size: var(--text-xs);
  color: var(--color-accent);
  font-weight: var(--font-semibold);
}

.cart-item__custom {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}

.custom-tag {
  padding: 1px 6px;
  border-radius: var(--radius-sm);
  background: var(--color-bg-subtle);
  font-size: 10px;
  color: var(--color-text-muted);
}

.custom-tag--more {
  color: var(--color-accent);
}

.cart-item__price {
  font-size: var(--text-sm);
  color: var(--color-accent);
  font-weight: var(--font-semibold);
}

.cart-item__controls {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.cart-item__remove {
  margin-left: var(--space-2);
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: var(--text-lg);
}

.cart-item__remove:hover {
  color: var(--color-danger);
}

.cart-totals {
  border-top: 1px solid var(--color-border);
  padding-top: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.cart-total-row {
  display: flex;
  justify-content: space-between;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.cart-total-row--final {
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.cart-total-row--cal {
  color: var(--color-accent);
  font-weight: var(--font-semibold);
}

/* ── Build Your Own ── */
.build-your-own {
  padding: var(--space-4);
  margin-bottom: var(--space-6);
  background: linear-gradient(135deg, rgba(255, 60, 172, 0.08), rgba(6, 182, 212, 0.06));
  border: 1px dashed rgba(255, 60, 172, 0.3);
  border-radius: var(--radius-xl);
}

.build-your-own__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.build-your-own__title {
  margin: 0;
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.build-your-own__desc {
  margin: var(--space-1) 0 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

/* ── Build Own Modal ── */
.build-own-modal {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  max-height: 60vh;
}

.build-own-modal__desc {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.build-own-modal__search {
  position: relative;
  flex-shrink: 0;
}

.build-own-modal__desc {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.build-own-modal__summary-sticky {
  display: flex;
  gap: var(--space-4);
  padding: var(--space-3) var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-3);
}

.build-own-modal__search {
  position: relative;
}

.build-own-search-input {
  width: 100%;
  padding: var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-size: var(--text-sm);
}

.build-own-search-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.build-own-modal__empty {
  text-align: center;
  padding: var(--space-8) var(--space-4);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}

.build-own-modal__bottom {
  flex-shrink: 0;
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
}

.build-own-modal__ingredients {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.build-own-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-3);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.build-own-row__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.build-own-row__name {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
}

.build-own-row__unit {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.build-own-row__cal {
  font-size: var(--text-xs);
  color: var(--color-accent);
}

.build-own-row__controls {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.build-own-input {
  width: 50px;
  padding: 4px;
  text-align: center;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-size: var(--text-sm);
}

.build-own-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.build-own-row__unit-label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  min-width: 24px;
}

.build-own-summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.build-own-summary-item__value {
  font-size: var(--text-xl);
  font-weight: var(--font-extrabold);
  color: var(--color-text-primary);
}

.build-own-summary-item__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  text-transform: uppercase;
}

.build-own-modal__add {
  width: 100%;
  padding: var(--space-4);
  border: none;
  border-radius: var(--radius-lg);
  background: var(--color-primary);
  color: var(--color-text-inverse);
  font-size: var(--text-base);
  font-weight: var(--font-bold);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.build-own-modal__add:hover:not(:disabled) {
  background: var(--color-primary-light);
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(255, 60, 172, 0.3);
}

.build-own-modal__add:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Mobile Cart Bar ── */
.mobile-cart-bar {
  display: none;
}

/* ── Login Modal ── */
.login-modal {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.auth-tabs {
  display: flex;
  gap: var(--space-1);
  background: var(--color-bg-subtle);
  border-radius: var(--radius-md);
  padding: 3px;
  margin-bottom: var(--space-2);
}

.auth-tab {
  flex: 1;
  padding: var(--space-2);
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.auth-tab--active {
  background: var(--color-primary);
  color: white;
}

.login-error {
  margin: 0;
  padding: var(--space-2);
  background: var(--color-danger-light);
  color: var(--color-danger);
  font-size: var(--text-sm);
  text-align: center;
  border-radius: var(--radius-md);
}

.login-modal__register {
  margin: 0;
  text-align: center;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.login-modal__register a {
  color: var(--color-primary-light);
  font-weight: var(--font-semibold);
  text-decoration: none;
}

/* ── Unavailable Modal ── */
.unavailable-modal__notice {
  margin: 0 0 var(--space-4);
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
}

.unavailable-modal__item {
  padding: var(--space-3);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-3);
}

.unavailable-modal__item-header {
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
  margin-bottom: var(--space-2);
}

.unavailable-modal__ingredient {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-2) 0;
}

.unavailable-modal__checkbox {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
}

.unavailable-modal__ing-name {
  color: var(--color-text-primary);
  font-size: var(--text-sm);
}

.unavailable-modal__stock {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

/* ── Success Modal ── */
.success-modal {
  text-align: center;
  padding: var(--space-4) 0;
}

.success-modal__icon {
  font-size: 3rem;
  margin-bottom: var(--space-3);
}

.success-modal__text {
  margin: 0 0 var(--space-2);
  color: var(--color-text-secondary);
}

.success-modal__number {
  margin: 0 0 var(--space-2);
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.success-modal__eta {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-accent);
}

@media (max-width: 1024px) {
  .detail-panels { grid-template-columns: 1fr; }
  .cart-panel { position: static; }
  .category-tabs { top: 0; }
  .detail-info { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .mobile-cart-bar {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: var(--space-3) var(--space-4);
    background: var(--color-bg-elevated);
    border-top: 1px solid var(--color-border);
    justify-content: space-between;
    align-items: center;
    z-index: 50;
  }

  .mobile-cart-bar__info {
    display: flex;
    flex-direction: column;
  }

  .mobile-cart-bar__count {
    font-size: var(--text-sm);
    color: var(--color-text-secondary);
  }

  .mobile-cart-bar__total {
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
  }
}
</style>
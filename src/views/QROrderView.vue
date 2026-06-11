<template>
  <div class="qr-order">
    <!-- Table Badge -->
    <div class="qr-banner">
      <span class="qr-banner__table">Table {{ tableId }}</span>
      <span v-if="restaurant" class="qr-banner__restaurant">at {{ restaurant.name }}</span>
    </div>

    <!-- Back Link -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 var(--space-6);">
      <RouterLink to="/discover" class="detail__back">← Browse other restaurants</RouterLink>
    </div>

    <div v-if="loading" class="qr-order__loading">
      <SkeletonLoader type="card" :lines="5" />
    </div>

    <template v-else-if="restaurant">
      <!-- Restaurant Header -->
      <div class="detail-header" style="max-width: 1200px; margin: 0 auto var(--space-6); padding: 0 var(--space-6);">
        <div class="detail-header__gradient" />
        <div class="detail-header__content">
          <h1 class="detail-header__name">{{ restaurant.name }}</h1>
          <span v-if="restaurant.cuisine_type" class="detail-header__cuisine">{{ restaurant.cuisine_type }}</span>
          <p v-if="restaurant.description" class="detail-header__desc">{{ restaurant.description }}</p>
        </div>
      </div>

      <!-- Two Panel Layout (same as RestaurantDetailView) -->
      <div class="detail-panels" style="max-width: 1200px; margin: 0 auto; padding: 0 var(--space-6);">
        <!-- LEFT: Menu -->
        <div class="detail-menu">
          <div class="category-tabs">
            <button v-for="cat in categories" :key="cat.id"
              :class="['category-tab', { 'category-tab--active': activeCategory === cat.id }]"
              @click="scrollToCategory(cat.id)"
            >
              {{ cat.name }}
            </button>
          </div>

          <div v-for="cat in categories" :key="cat.id" :id="`cat-${cat.id}`" class="menu-section">
            <h2 class="menu-section__title">{{ cat.name }}</h2>
            <div class="menu-items">
              <div v-for="item in getItemsByCategory(cat.id)" :key="item.id"
                :class="['menu-item', { 'menu-item--unavailable': !item.is_available }]"
              >
                <div class="menu-item__info">
                  <h3 class="menu-item__name">{{ item.name }}</h3>
                  <p v-if="item.description" class="menu-item__desc">{{ item.description }}</p>
                  <span class="menu-item__price">${{ Number(item.price).toFixed(2) }}</span>
                </div>
                <div class="menu-item__action">
                  <template v-if="item.is_available !== false">
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
                  </template>
                  <span v-else class="unavailable-badge">Unavailable</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Cart -->
        <div class="detail-cart">
          <div class="cart-panel">
            <h2 class="cart-panel__title">Your Order · Table {{ tableId }}</h2>

            <div v-if="cartStore.isEmpty" class="cart-empty">
              <div class="cart-empty__icon">🛒</div>
              <p class="cart-empty__text">Add items to get started</p>
            </div>

            <div v-else class="cart-items">
              <div v-for="item in cartStore.items" :key="item.menu_item_id" class="cart-item">
                <div class="cart-item__info">
                  <span class="cart-item__name">{{ item.name }}</span>
                  <span class="cart-item__price">${{ (item.price * item.quantity).toFixed(2) }}</span>
                </div>
                <div class="cart-item__controls">
                  <button class="qty-btn" @click="cartStore.decrementItem(item.menu_item_id)">−</button>
                  <span class="qty-value">{{ item.quantity }}</span>
                  <button class="qty-btn" @click="cartStore.incrementItem(item.menu_item_id)">+</button>
                  <button class="cart-item__remove" @click="cartStore.removeItem(item.menu_item_id)">×</button>
                </div>
              </div>
            </div>

            <div v-if="!cartStore.isEmpty" class="cart-totals">
              <div class="cart-total-row cart-total-row--final">
                <span>Total</span>
                <span>${{ cartStore.totalPrice.toFixed(2) }}</span>
              </div>
              <BaseButton variant="primary" :loading="ordering" :disabled="cartStore.isEmpty || ordering" style="width: 100%" @click="handlePlaceOrder">
                Place Order
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Success Modal -->
    <BaseModal v-model="showSuccessModal" title="Order Placed!" size="sm">
      <div class="success-modal">
        <div class="success-modal__icon">✅</div>
        <p class="success-modal__text">Order placed for Table {{ tableId }}!</p>
        <p class="success-modal__eta">Estimated time: 20-30 minutes</p>
      </div>
      <template #footer>
        <BaseButton variant="primary" @click="showSuccessModal = false">Order More</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useCartStore } from "../stores/cartStore";
import { useAuthStore } from "../stores/authStore";
import customerService from "../services/customerService";
import { useToast } from "../composables/useToast";
import BaseButton from "../components/ui/BaseButton.vue";
import BaseInput from "../components/ui/BaseInput.vue";
import BaseModal from "../components/ui/BaseModal.vue";
import SkeletonLoader from "../components/ui/SkeletonLoader.vue";

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();
const toast = useToast();

const restaurantId = computed(() => route.params.restaurantId);
const tableId = computed(() => route.params.tableId);

const restaurant = ref(null);
const menuItems = ref([]);
const categories = ref([]);
const loading = ref(true);
const ordering = ref(false);
const activeCategory = ref(null);
const showSuccessModal = ref(false);

function getItemsByCategory(catId) {
  return menuItems.value.filter((item) => item.menu_category_id === catId);
}

function scrollToCategory(catId) {
  activeCategory.value = catId;
  document.getElementById(`cat-${catId}`)?.scrollIntoView({ behavior: "smooth" });
}

function addToCart(item) {
  cartStore.addItem(item, parseInt(restaurantId.value));
  cartStore.setOrderType("dine_in");
  cartStore.setTable(parseInt(tableId.value));
}

async function handlePlaceOrder() {
  const token = localStorage.getItem('tavliq_token');
  if (!token) {
    router.push('/login');
    return;
  }
  if (cartStore.isEmpty) return;
  ordering.value = true;
  try {
    const subtotal = cartStore.totalPrice;
    const tax = subtotal * 0.10;
    await customerService.placeOrder({
      table_id: parseInt(tableId.value),
      cart: cartStore.items.map((item) => ({
        menu_item_id: item.menu_item_id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
      })),
      subtotal,
      tax,
      total: subtotal + tax,
    });
    showSuccessModal.value = true;
    cartStore.clearCart();
    toast.success("Order placed!");
  } catch (e) {
    toast.error("Failed to place order");
  } finally { ordering.value = false; }
}

onMounted(async () => {
  cartStore.clearCart();
  cartStore.setOrderType("dine_in");
  cartStore.setTable(parseInt(tableId.value));
  try {
    const [restRes, menuRes, catRes] = await Promise.all([
      customerService.getRestaurant(restaurantId.value),
      customerService.getMenuItems(restaurantId.value),
      customerService.getMenuCategories(restaurantId.value),
    ]);
    restaurant.value = restRes.data?.data || restRes.data;
    const menuRaw = menuRes.data?.data;
    menuItems.value = Array.isArray(menuRaw) ? menuRaw : Array.isArray(menuRaw?.data) ? menuRaw.data : [];
    const catRaw = catRes.data?.data;
    categories.value = Array.isArray(catRaw) ? catRaw : Array.isArray(catRaw?.data) ? catRaw.data : [];
    if (categories.value.length) activeCategory.value = categories.value[0].id;
  } catch (e) { console.error(e); }
  finally { loading.value = false; }
});
</script>

<style scoped>
.qr-banner {
  background: var(--color-bg-elevated);
  border-bottom: 1px solid var(--color-border);
  padding: var(--space-3) var(--space-6);
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-bottom: var(--space-4);
}
.qr-banner__table {
  padding: 4px 12px;
  border-radius: var(--radius-full);
  background: var(--color-accent);
  color: var(--color-text-inverse);
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
}
.qr-banner__restaurant { font-size: var(--text-sm); color: var(--color-text-secondary); }

.detail__back { display: inline-block; margin-bottom: var(--space-4); color: var(--color-accent); text-decoration: none; font-size: var(--text-sm); }
.qr-order__loading { padding: var(--space-8); max-width: 1200px; margin: 0 auto; }

.detail-header { position: relative; border-radius: var(--radius-2xl); overflow: hidden; margin-bottom: var(--space-6); padding: var(--space-12) var(--space-8); }
.detail-header__gradient { position: absolute; inset: 0; background: linear-gradient(135deg, var(--color-primary), var(--color-accent)); }
.detail-header__content { position: relative; color: white; }
.detail-header__name { margin: 0 0 var(--space-2); font-size: var(--text-3xl); font-weight: var(--font-extrabold); }
.detail-header__cuisine { display: inline-block; padding: 4px 12px; border-radius: var(--radius-full); background: rgba(255,255,255,0.2); font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-3); }
.detail-header__desc { margin: 0; opacity: 0.9; }

.detail-panels { display: grid; grid-template-columns: 1fr 380px; gap: var(--space-6); align-items: start; }

.category-tabs { display: flex; gap: var(--space-2); overflow-x: auto; padding-bottom: var(--space-3); margin-bottom: var(--space-6); border-bottom: 1px solid var(--color-border); position: sticky; top: 0; background: var(--color-bg); z-index: 10; }
.category-tab { padding: var(--space-2) var(--space-4); border: none; border-radius: var(--radius-md); background: transparent; color: var(--color-text-secondary); font-size: var(--text-sm); font-weight: var(--font-medium); white-space: nowrap; cursor: pointer; border-bottom: 2px solid transparent; }
.category-tab--active { color: var(--color-accent); border-bottom-color: var(--color-accent); }

.menu-section { margin-bottom: var(--space-8); }
.menu-section__title { margin: 0 0 var(--space-4); font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--color-text-primary); }
.menu-items { display: flex; flex-direction: column; gap: var(--space-3); }
.menu-item { display: flex; justify-content: space-between; align-items: center; padding: var(--space-4); background: var(--color-bg-elevated); border: 1px solid var(--color-border); border-radius: var(--radius-lg); }
.menu-item--unavailable { opacity: 0.5; pointer-events: none; }
.menu-item__info { flex: 1; }
.menu-item__name { margin: 0 0 var(--space-1); font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--color-text-primary); }
.menu-item__desc { margin: 0 0 var(--space-2); font-size: var(--text-sm); color: var(--color-text-secondary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.menu-item__price { font-weight: var(--font-bold); color: var(--color-accent); }
.menu-item__action { margin-left: var(--space-4); }

.qty-controls { display: flex; align-items: center; gap: var(--space-2); }
.qty-btn { width: 32px; height: 32px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-bg-elevated); color: var(--color-text-primary); font-size: var(--text-lg); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover { border-color: var(--color-primary); }
.qty-value { min-width: 24px; text-align: center; font-weight: var(--font-semibold); color: var(--color-text-primary); }
.unavailable-badge { font-size: var(--text-sm); color: var(--color-text-muted); }

.cart-panel { position: sticky; top: var(--space-4); background: var(--color-bg-elevated); border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: var(--space-5); }
.cart-panel__title { margin: 0 0 var(--space-4); font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--color-text-primary); }
.cart-empty { text-align: center; padding: var(--space-8) 0; }
.cart-empty__icon { font-size: 2rem; margin-bottom: var(--space-2); }
.cart-empty__text { margin: 0; font-size: var(--text-sm); color: var(--color-text-muted); }
.cart-items { display: flex; flex-direction: column; gap: var(--space-3); margin-bottom: var(--space-4); }
.cart-item__info { display: flex; justify-content: space-between; margin-bottom: var(--space-1); }
.cart-item__name { font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--color-text-primary); }
.cart-item__price { font-size: var(--text-sm); color: var(--color-accent); font-weight: var(--font-semibold); }
.cart-item__controls { display: flex; align-items: center; gap: var(--space-2); }
.cart-item__remove { margin-left: var(--space-2); background: none; border: none; color: var(--color-text-muted); cursor: pointer; font-size: var(--text-lg); }
.cart-item__remove:hover { color: var(--color-danger); }
.cart-totals { border-top: 1px solid var(--color-border); padding-top: var(--space-4); display: flex; flex-direction: column; gap: var(--space-3); }
.cart-total-row--final { display: flex; justify-content: space-between; font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--color-text-primary); }

.login-modal { display: flex; flex-direction: column; gap: var(--space-4); }
.login-error { margin: 0; padding: var(--space-2); background: var(--color-danger-light); color: var(--color-danger); font-size: var(--text-sm); text-align: center; border-radius: var(--radius-md); }
.success-modal { text-align: center; padding: var(--space-4) 0; }
.success-modal__icon { font-size: 3rem; margin-bottom: var(--space-3); }
.success-modal__text { margin: 0 0 var(--space-2); color: var(--color-text-secondary); }
.success-modal__eta { margin: 0; font-size: var(--text-sm); color: var(--color-accent); }

@media (max-width: 1024px) { .detail-panels { grid-template-columns: 1fr; } .cart-panel { position: static; } }
</style>

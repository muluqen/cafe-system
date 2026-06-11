<template>
  <div class="customer-orders">
    <PageHeader title="My Orders" />

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        :class="['filter-tab', { 'filter-tab--active': activeTab === tab.value }]"
        @click="activeTab = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="text" :lines="4" />
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredOrders.length === 0" class="empty-state">
      <div class="empty-state__icon">📦</div>
      <h3 class="empty-state__title">No orders yet</h3>
      <p class="empty-state__text">Start ordering from your favorite restaurants</p>
      <RouterLink to="/discover">
        <BaseButton variant="primary">Browse Restaurants</BaseButton>
      </RouterLink>
    </div>

    <!-- Orders List -->
    <div v-else class="orders-list stagger-children">
      <div v-for="order in filteredOrders" :key="order.id" :class="['order-card', 'animate-on-scroll', { 'order-card--active': isActive(order.status) }, { 'order-card--completed': order.status === 'completed' }]">
        <div class="order-card__header">
          <div>
            <span class="order-card__number">#{{ order.order_number }}</span>
            <span class="order-card__restaurant">{{ order.restaurant?.name || 'Restaurant' }}</span>
          </div>
          <span class="order-card__date">{{ formatDate(order.placed_at || order.created_at) }}</span>
        </div>

        <div class="order-card__items">
          <div v-for="(item, i) in (order.items || [])" :key="item.id" class="order-card__item-row">
            <div class="order-card__item-main">
              <span class="order-card__item-qty">{{ item.quantity }}x</span>
              <span class="order-card__item-name">{{ item.item_name }}</span>
              <span class="order-card__item-price">${{ Number(item.unit_price * item.quantity).toFixed(2) }}</span>
            </div>
            <div v-if="item.customized_ingredients && item.customized_ingredients.length" class="order-card__customizations">
              <span v-for="ci in item.customized_ingredients" :key="ci.ingredient_id || ci.name" class="order-card__custom-tag">
                {{ ci.name || ci.ingredient_name }}: {{ ci.quantity_required }}{{ ci.unit }}
              </span>
            </div>
          </div>
        </div>

        <div class="order-card__footer">
          <BaseBadge :variant="statusVariant(order.status)">{{ order.status }}</BaseBadge>
          <div class="order-card__totals">
            <span class="order-card__subtotal">Sub: ${{ Number(order.subtotal || 0).toFixed(2) }}</span>
            <span class="order-card__tax">Tax: ${{ Number(order.tax || 0).toFixed(2) }}</span>
            <span class="order-card__total">${{ Number(order.total || 0).toFixed(2) }}</span>
          </div>
        </div>

        <div class="order-card__actions">
          <BaseButton v-if="isActive(order.status)" variant="ghost" size="sm" @click="openStatusModal(order)">
            Track Order
          </BaseButton>
          <BaseButton v-if="order.status === 'ready' || order.status === 'completed'" variant="ghost" size="sm" @click="handleReorder(order)">
            Reorder
          </BaseButton>
          <BaseButton v-if="order.status === 'ready' || order.status === 'completed'" variant="ghost" size="sm" @click="openFeedbackModal(order)">
            Leave Feedback
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Status Modal -->
    <BaseModal v-model="statusModalVisible" :title="`Tracking ${selectedOrder?.order_number || ''}`" size="sm">
      <div v-if="selectedOrder" class="status-stepper">
        <div v-for="(step, i) in orderSteps" :key="i" :class="['step', { 'step--active': step.active, 'step--done': step.done }]">
          <div class="step__dot"></div>
          <div class="step__content">
            <span class="step__label">{{ step.label }}</span>
            <span class="step__desc">{{ step.desc }}</span>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Feedback Modal -->
    <BaseModal v-model="feedbackModalVisible" title="Leave Feedback" size="sm">
      <div class="feedback-form">
        <div class="star-rating">
          <button
            v-for="s in 5"
            :key="s"
            :class="['star', { 'star--active': s <= feedbackRating }]"
            @click="feedbackRating = s"
          >
            ★
          </button>
        </div>
        <textarea
          v-model="feedbackComment"
          class="feedback-textarea"
          placeholder="Share your experience..."
          rows="4"
        />
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="feedbackModalVisible = false">Cancel</BaseButton>
        <BaseButton variant="primary" :loading="feedbackLoading" @click="submitFeedback">
          Submit
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useCartStore } from "../stores/cartStore";
import { useAuthStore } from "../stores/authStore";
import customerService from "../services/customerService";
import { useToast } from "../composables/useToast";
import { extractList } from "../utils/responseParser";
import PageHeader from "../components/ui/PageHeader.vue";
import BaseButton from "../components/ui/BaseButton.vue";
import BaseBadge from "../components/ui/BaseBadge.vue";
import BaseModal from "../components/ui/BaseModal.vue";
import SkeletonLoader from "../components/ui/SkeletonLoader.vue";
import api from "../services/api";

const router = useRouter();
const cartStore = useCartStore();
const auth = useAuthStore();
const toast = useToast();

const orders = ref([]);
const loading = ref(true);
const activeTab = ref("all");
const statusModalVisible = ref(false);
const selectedOrder = ref(null);
const feedbackModalVisible = ref(false);
const feedbackTarget = ref(null);
const feedbackRating = ref(5);
const feedbackComment = ref("");
const feedbackLoading = ref(false);
let pollInterval = null;

const tabs = [
  { label: "All", value: "all" },
  { label: "Active", value: "active" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

const filteredOrders = computed(() => {
  if (activeTab.value === "all") return orders.value;
  if (activeTab.value === "active") return orders.value.filter((o) => ["pending", "preparing", "ready"].includes(o.status));
  if (activeTab.value === "completed") return orders.value.filter((o) => o.status === "completed");
  if (activeTab.value === "cancelled") return orders.value.filter((o) => o.status === "cancelled");
  return orders.value;
});

const orderSteps = computed(() => {
  if (!selectedOrder.value) return [];
  const status = selectedOrder.value.status;
  const steps = [
    { status: "pending", label: "Order Placed", desc: "Your order has been received" },
    { status: "preparing", label: "Being Prepared", desc: "Kitchen is working on your order" },
    { status: "ready", label: "Ready", desc: "Your order is ready for pickup" },
    { status: "completed", label: "Delivered", desc: "Enjoy your meal!" },
  ];
  const statusOrder = ["pending", "preparing", "ready", "completed"];
  const currentIndex = statusOrder.indexOf(status);
  return steps.map((s, i) => ({
    ...s,
    done: currentIndex > i,
    active: currentIndex === i,
  }));
});

function statusVariant(status) {
  return { pending: "warning", preparing: "info", ready: "success", completed: "neutral", cancelled: "danger" }[status] || "neutral";
}

function isActive(status) {
  return ["pending", "preparing", "ready"].includes(status);
}

function formatDate(date) {
  if (!date) return "";
  return new Date(date).toLocaleString();
}

function openStatusModal(order) {
  selectedOrder.value = order;
  statusModalVisible.value = true;
}

function openFeedbackModal(order) {
  feedbackTarget.value = order;
  feedbackRating.value = 5;
  feedbackComment.value = "";
  feedbackModalVisible.value = true;
}

async function submitFeedback() {
  feedbackLoading.value = true;
  try {
    await customerService.submitFeedback({
      restaurant_id: feedbackTarget.value.restaurant_id,
      rating: feedbackRating.value,
      comment: feedbackComment.value,
    });
    feedbackModalVisible.value = false;
    toast.success("Feedback submitted!");
  } catch (e) {
    toast.error("Failed to submit feedback");
  } finally {
    feedbackLoading.value = false;
  }
}

function handleReorder(order) {
  if (order.items) {
    order.items.forEach((item) => {
      for (let i = 0; i < item.quantity; i++) {
        cartStore.addItem(
          { id: item.menu_item_id, name: item.item_name, price: item.unit_price },
          order.restaurant_id
        );
      }
    });
    toast.success("Items added to cart");
    router.push(`/restaurant/${order.restaurant_id}`);
  }
}

async function fetchOrders() {
  loading.value = true;
  try {
    const res = await api.get('/orders', {
      params: { per_page: 20 }
    });
    const data = res.data?.data || res.data || [];
    const raw = Array.isArray(data) ? data : data.data || [];
    orders.value = raw.map(o => ({ ...o, items: o.items || o.orderItems || o.order_items || [] }));
  } catch (e) {
    console.error("Failed to load orders", e);
    toast.error("Failed to load orders");
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await fetchOrders();

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.05 });
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el);
  });

  pollInterval = setInterval(async () => {
    const hasActive = orders.value.some(o => ["pending", "preparing", "ready"].includes(o.status));
    if (!hasActive) return;
    try {
      const res = await api.get('/orders', {
        params: { per_page: 20 }
      });
      const data = res.data?.data || res.data || [];
      const raw = Array.isArray(data) ? data : data.data || [];
      orders.value = raw.map(o => ({ ...o, items: o.items || o.orderItems || o.order_items || [] }));
    } catch {}
  }, 10000);

  // WebSocket: listen for order status changes
  import('../services/echo.js').then(({ getEcho }) => {
    const echo = getEcho()
    const userId = auth.user?.id
    if (userId) {
      echo.channel(`customer.${userId}`).listen('.order.status', (e) => {
        fetchOrders()
      })
    }
  })
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);

  // Leave WebSocket channel
  import('../services/echo.js').then(({ getEcho }) => {
    const echo = getEcho()
    const userId = auth.user?.id
    if (userId) {
      echo.leaveChannel(`customer.${userId}`)
    }
  })
});
</script>

<style scoped>
.filter-tabs {
  display: flex;
  gap: var(--space-1);
  background: var(--color-bg-elevated);
  padding: var(--space-1);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-6);
}

.filter-tab {
  padding: var(--space-2) var(--space-4);
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.filter-tab--active {
  background: var(--color-primary);
  color: white;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.order-card {
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-5);
  transition: all var(--transition-base);
}

.order-card--active {
  border-left: 3px solid var(--color-energy);
  box-shadow: -3px 0 15px rgba(249,194,46,0.15);
}

.order-card--completed {
  border-left: 3px solid var(--color-success);
}

.order-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--space-3);
}

.order-card__number {
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
  margin-right: var(--space-2);
}

.order-card__restaurant {
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
}

.order-card__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.order-card__items {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  margin-bottom: var(--space-3);
}

.order-card__item-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.order-card__item-main {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.order-card__item-qty {
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
  min-width: 28px;
}

.order-card__item-name {
  flex: 1;
  color: var(--color-text-secondary);
}

.order-card__item-price {
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
}

.order-card__customizations {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding-left: 36px;
}

.order-card__custom-tag {
  font-size: 11px;
  color: var(--color-text-muted);
  background: var(--color-bg-subtle);
  padding: 1px 6px;
  border-radius: var(--radius-sm);
}

.order-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-3);
}

.order-card__totals {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.order-card__subtotal,
.order-card__tax {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.order-card__total {
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.order-card__actions {
  display: flex;
  gap: var(--space-2);
  border-top: 1px solid var(--color-border);
  padding-top: var(--space-3);
}

.empty-state {
  text-align: center;
  padding: var(--space-12) var(--space-4);
}

.empty-state__icon { font-size: 3rem; margin-bottom: var(--space-3); }
.empty-state__title { margin: 0 0 var(--space-2); font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--color-text-primary); }
.empty-state__text { margin: 0 0 var(--space-4); color: var(--color-text-muted); }

.loading-state { padding: var(--space-8); }

/* Status Stepper */
.status-stepper {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.step {
  display: flex;
  gap: var(--space-4);
  padding: var(--space-3) 0;
  position: relative;
}

.step:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 9px;
  top: 36px;
  bottom: -8px;
  width: 2px;
  background: #27272A;
}

.step--done::after {
  background: #10B981 !important;
}

.step__dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #27272A;
  background: #18181B;
  flex-shrink: 0;
  margin-top: 2px;
  transition: all 200ms ease;
}

.step--done .step__dot {
  background: #10B981;
  border-color: #10B981;
}

.step--active .step__dot {
  background: #FF3CAC;
  border-color: #FF3CAC;
  box-shadow: 0 0 10px rgba(255, 60, 172, 0.4);
  animation: pulse-dot 1.5s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { box-shadow: 0 0 10px rgba(255, 60, 172, 0.4); }
  50% { box-shadow: 0 0 20px rgba(255, 60, 172, 0.6); }
}

.step__label {
  font-weight: 600;
  color: #FAFAFA;
  font-size: var(--text-sm);
}

.step--done .step__label { color: #10B981; }
.step--active .step__label { color: #FF3CAC; }

.step__desc {
  font-size: var(--text-xs);
  color: #71717A;
  margin-top: 2px;
}

/* Feedback */
.feedback-form { display: flex; flex-direction: column; gap: var(--space-4); }

.star-rating { display: flex; gap: var(--space-1); }

.star {
  font-size: 1.5rem;
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: color var(--transition-fast);
}

.star--active { color: var(--color-warning); }

.feedback-textarea {
  width: 100%;
  padding: var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font-family: var(--font-sans);
  font-size: var(--text-sm);
  resize: vertical;
}

.feedback-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
}
</style>

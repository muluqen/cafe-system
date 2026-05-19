<template>
  <section class="pos-layout">
    <div class="pos-main panel">
      <header class="panel-header">
        <h1 class="panel-title">Point of Sale</h1>
      </header>
      <div class="content-pad">
        <div class="pos-grid">
          <div v-for="item in menuItems" :key="item.id" class="pos-item-card" @click="addDirectlyToCart(item)">
            <strong>{{ item.name }}</strong>
            <p class="price-tag">${{ Number(item.price).toFixed(2) }}</p>
          </div>
        </div>
      </div>
    </div>

    <aside class="pos-sidebar panel">
      <header class="panel-header">
        <h2 class="panel-title">Current Ticket</h2>
      </header>
      <div class="content-pad cart-items">
        <div v-if="cart.length === 0" class="empty">Cart is empty.</div>
        <div v-for="(cartItem, idx) in cart" :key="idx" class="cart-item">
          <div class="cart-item-header">
            <strong>{{ cartItem.name }} <span v-if="cartItem.upcharge > 0" class="muted">(+${{ cartItem.upcharge.toFixed(2) }})</span></strong>
            <span>${{ ((cartItem.price + cartItem.upcharge) * cartItem.quantity).toFixed(2) }}</span>
          </div>
          <div v-if="cartItem.notes" class="cart-item-notes muted">📝 {{ cartItem.notes }}</div>
          <div class="cart-item-controls">
            <button class="button button-soft" @click="openItemModal(idx)" title="Add Notes">📝</button>
            <button class="button button-soft" @click="cartItem.quantity = Math.max(1, cartItem.quantity - 1)">-</button>
            <span class="qty-display">{{ cartItem.quantity }}</span>
            <button class="button button-soft" @click="cartItem.quantity++">+</button>
            <button class="button button-soft danger" @click="cart.splice(idx, 1)">x</button>
          </div>
        </div>
      </div>
      <div class="cart-footer">
        <div class="cart-totals">
          <div class="cart-row"><span>Subtotal:</span><span>${{ subtotal.toFixed(2) }}</span></div>
          <div class="cart-row"><span>Tax (10%):</span><span>${{ tax.toFixed(2) }}</span></div>
          <div class="cart-row cart-grand"><span>Total:</span><span>${{ total.toFixed(2) }}</span></div>
        </div>
        <button class="button pos-checkout-btn" :disabled="cart.length === 0 || saving" @click="checkout">
          {{ saving ? 'Sending...' : 'Send Order to Kitchen' }}
        </button>
        <p v-if="error" class="muted error-text">{{ error }}</p>
      </div>
    </aside>

    <div v-if="selectedCartIndex !== null" class="modal-backdrop" @click.self="closeItemModal">
      <div class="modal-card panel">
        <div class="panel-header">
          <h2 class="panel-title">Customize {{ cart[selectedCartIndex].name }}</h2>
          <button class="button button-soft" @click="closeItemModal">Close</button>
        </div>
        <div class="content-pad">
          <form @submit.prevent="saveNotes">
            <label style="display:block; margin-bottom: 1rem;">
              Special Instructions / Modifiers
              <textarea v-model="itemNotes" class="input" rows="3" placeholder="e.g. Extra chicken, no tomatoes..."></textarea>
            </label>
            <label style="display:block; margin-bottom: 1rem;">
              Customization Upcharge ($)
              <input type="number" v-model.number="itemUpcharge" class="input" min="0" step="0.25" placeholder="0.00" />
            </label>
            <button class="button" style="margin-top: 14px;">Save Customizations</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const menuItems = ref([]);
const cart = ref([]);
const saving = ref(false);
const error = ref("");

const selectedCartIndex = ref(null);
const itemNotes = ref("");
const itemUpcharge = ref(0);

const subtotal = computed(() => cart.value.reduce((sum, item) => sum + ((item.price + (item.upcharge || 0)) * item.quantity), 0));
const tax = computed(() => subtotal.value * 0.10);
const total = computed(() => subtotal.value + tax.value);

async function loadMenu() {
  try {
    const res = await api.get("/menu_items", { params: { per_page: 200 } });
    menuItems.value = res.data?.data || [];
  } catch (err) {
    error.value = "Failed to load menu items.";
  }
}

function addDirectlyToCart(item) {
  const existing = cart.value.find(c => c.menu_item_id === item.id && !c.notes);
  if (existing) {
    existing.quantity++;
  } else {
    cart.value.push({
      menu_item_id: item.id,
      name: item.name,
      price: Number(item.price),
      upcharge: 0,
      quantity: 1,
      notes: ""
    });
  }
}

function openItemModal(idx) {
  selectedCartIndex.value = idx;
  itemNotes.value = cart.value[idx].notes || "";
  itemUpcharge.value = cart.value[idx].upcharge || 0;
}

function closeItemModal() {
  selectedCartIndex.value = null;
}

function saveNotes() {
  if (selectedCartIndex.value !== null) {
    cart.value[selectedCartIndex.value].notes = itemNotes.value;
    cart.value[selectedCartIndex.value].upcharge = itemUpcharge.value;
  }
  closeItemModal();
}

async function checkout() {
  if (cart.value.length === 0) return;
  saving.value = true;
  error.value = "";
  try {
    const payload = {
      subtotal: subtotal.value,
      tax: tax.value,
      total: total.value,
      cart: cart.value.map(item => ({
        menu_item_id: item.menu_item_id,
        name: item.name,
        quantity: item.quantity,
        price: item.price + (item.upcharge || 0),
        notes: item.notes || null
      }))
    };

    await api.post("/checkout/process", payload);
    
    cart.value = [];
    alert("Order successfully sent! Inventory has been automatically deducted based on recipes.");
  } catch (err) {
    error.value = "Failed to send order.";
  } finally {
    saving.value = false;
  }
}

onMounted(loadMenu);
</script>

<style scoped>
.pos-layout {
  display: flex;
  gap: 1.5rem;
  height: 100%;
}
.pos-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.pos-sidebar {
  width: 350px;
  display: flex;
  flex-direction: column;
}
.pos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 1rem;
}
.pos-item-card {
  padding: 1.5rem 1rem;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 12px;
  cursor: pointer;
  text-align: center;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
  box-shadow: var(--shadow-sm);
}
.pos-item-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--accent);
}
.price-tag {
  color: var(--accent);
  font-weight: bold;
  font-size: 1.1rem;
  margin-top: 0.5rem;
}
.cart-items {
  flex: 1;
  overflow-y: auto;
}
.cart-item {
  padding: 1rem 0;
  border-bottom: 1px solid var(--line);
}
.cart-item-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-weight: 600;
}
.cart-item-notes {
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  color: var(--accent);
  font-style: italic;
}
.cart-item-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.qty-display {
  font-weight: bold;
  min-width: 1.5rem;
  text-align: center;
}
.cart-footer {
  padding: 1.5rem;
  border-top: 1px solid var(--line);
  background: var(--bg-accent);
}
.cart-totals {
  margin-bottom: 1rem;
}
.cart-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}
.cart-grand {
  font-weight: bold;
  font-size: 1.35rem;
  border-top: 2px solid var(--line);
  padding-top: 0.75rem;
  margin-top: 0.5rem;
  color: var(--accent);
}
.pos-checkout-btn {
  width: 100%;
  padding: 1rem;
  font-size: 1.125rem;
  background-color: var(--accent);
  color: white;
  border-radius: 8px;
}
.pos-checkout-btn:hover:not(:disabled) {
  background-color: var(--accent-strong);
}
.error-text {
  color: red;
  margin-top: 0.5rem;
  text-align: center;
}
</style>

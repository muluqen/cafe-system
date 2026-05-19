<template>
  <div class="kds-layout">
    <header class="kds-header panel">
      <div>
        <h1>{{ isExpeditor ? 'Expeditor Dashboard' : (auth.staffRole === 'barista' ? 'Barista Station' : 'Kitchen Station') }}</h1>
        <p class="muted" style="margin:0;">{{ isExpeditor ? 'Combine drinks and food for service.' : 'Only showing items routed to your station.' }}</p>
      </div>
      <button class="button" @click="loadOrders">Refresh Orders</button>
    </header>
    
    <div class="kds-board">
      <!-- Pending Column -->
      <div class="kds-col panel">
        <h2>{{ isExpeditor ? 'Cooking / Brewing' : 'New Orders' }}</h2>
        <div class="kds-ticket" v-for="order in pendingOrders" :key="order.id">
          <div class="kds-ticket-head">
            <strong>Order #{{ order.order_number }}</strong>
            <span>{{ formatTime(order.placed_at) }}</span>
          </div>
          <ul class="kds-items">
            <li v-for="item in order.items" :key="item.id">
              <span :class="{ 'strike': item.status === 'ready' }">
                {{ item.quantity }}x {{ item.item_name }}
              </span>
              <span v-if="isExpeditor" class="station-badge">{{ item.routing_station }}</span>
              <div v-if="item.notes" class="kds-notes">📝 {{ item.notes }}</div>
            </li>
          </ul>
          <button v-if="!isExpeditor" class="button kds-btn" @click="startPreparing(order)">Start Preparing</button>
          <div v-else class="waiting-badge">Waiting on stations...</div>
        </div>
      </div>

      <!-- Preparing Column -->
      <div class="kds-col panel">
        <h2>{{ isExpeditor ? 'Ready to Serve' : 'Preparing' }}</h2>
        <div class="kds-ticket preparing-ticket" v-for="order in preparingOrders" :key="order.id">
          <div class="kds-ticket-head">
            <strong>Order #{{ order.order_number }}</strong>
            <span>{{ formatTime(order.placed_at) }}</span>
          </div>
          <ul class="kds-items">
            <li v-for="item in order.items" :key="item.id">
               {{ item.quantity }}x {{ item.item_name }}
              <div v-if="item.notes" class="kds-notes">📝 {{ item.notes }}</div>
            </li>
          </ul>
          <button v-if="!isExpeditor" class="button kds-btn kds-ready-btn" @click="markReady(order)">Mark as Ready</button>
          <button v-else class="button kds-btn kds-serve-btn" @click="serveOrder(order)">Serve to Customer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, onUnmounted } from "vue";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const isExpeditor = computed(() => auth.staffRole === 'manager' || auth.staffRole === 'floor_manager');
const myStation = computed(() => auth.staffRole);

const orders = ref([]);
let intervalId = null;

const pendingOrders = computed(() => {
  if (isExpeditor.value) {
    // Expeditor: Orders where at least one item is NOT ready
    return orders.value.filter(o => o.items.some(i => i.status !== 'ready'));
  } else {
    // Station: Orders where at least one of MY items is pending
    return orders.value.filter(o => o.items.some(i => i.status === 'pending' || !i.status));
  }
});

const preparingOrders = computed(() => {
  if (isExpeditor.value) {
    // Expeditor: Orders where ALL items are ready!
    return orders.value.filter(o => o.items.every(i => i.status === 'ready') && o.items.length > 0);
  } else {
    // Station: Orders where MY items are preparing, and NO items are pending
    return orders.value.filter(o => 
      o.items.some(i => i.status === 'preparing') && 
      !o.items.some(i => i.status === 'pending' || !i.status)
    );
  }
});

async function loadOrders() {
  try {
    const res = await api.get("/orders", { params: { per_page: 50, status: 'pending' } });
    let fetched = res.data?.data || [];
    
    // Filter out items not belonging to this station
    fetched = fetched.map(order => {
      if (!isExpeditor.value) {
         order.items = (order.items || []).filter(i => i.routing_station === myStation.value);
      }
      return order;
    }).filter(order => order.items && order.items.length > 0);
    
    orders.value = fetched;
  } catch (err) {
    console.error("Error loading KDS orders", err);
  }
}

async function startPreparing(order) {
  const pendingItems = order.items.filter(i => i.status === 'pending' || !i.status);
  for (const item of pendingItems) {
    await api.put(`/order_items/${item.id}`, { status: 'preparing' });
  }
  await loadOrders();
}

async function markReady(order) {
  const preparingItems = order.items.filter(i => i.status === 'preparing');
  for (const item of preparingItems) {
    await api.put(`/order_items/${item.id}`, { status: 'ready' });
  }
  await loadOrders();
}

async function serveOrder(order) {
  // Expeditor completes the entire order
  await api.put(`/orders/${order.id}`, { status: 'completed' });
  await loadOrders();
}

function formatTime(dateStr) {
  if(!dateStr) return '';
  return new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

onMounted(() => {
  loadOrders();
  intervalId = setInterval(loadOrders, 8000); 
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});
</script>

<style scoped>
.kds-layout { display: flex; flex-direction: column; gap: 1rem; height: calc(100vh - 40px); }
.kds-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; border-radius: 12px; }
.kds-header h1 { margin: 0; font-family: 'Outfit', sans-serif; font-size: 2rem; }
.kds-board { display: flex; gap: 1rem; flex: 1; overflow: hidden; }
.kds-col { flex: 1; display: flex; flex-direction: column; background: var(--bg-accent); padding: 1.5rem; overflow-y: auto; border-radius: 12px; border: 1px solid var(--line); }
.kds-col h2 { font-family: 'Outfit'; margin: 0 0 1.5rem 0; text-align: center; color: var(--text-main); font-size: 1.5rem; }
.kds-ticket { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm); transition: transform 0.2s; }
.kds-ticket:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.preparing-ticket { border-left: 6px solid var(--accent); }
.kds-ticket-head { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.2rem; border-bottom: 2px solid var(--line); padding-bottom: 0.8rem; }
.kds-items { list-style: none; padding: 0; margin: 0 0 1.5rem 0; font-size: 1.2rem; font-weight: 600; color: var(--text-main); }
.kds-items li { margin-bottom: 0.8rem; display: flex; flex-direction: column; }
.strike { text-decoration: line-through; opacity: 0.5; }
.station-badge { font-size: 0.75rem; background: var(--line); padding: 2px 6px; border-radius: 4px; width: fit-content; margin-top: 4px; text-transform: uppercase; font-weight: bold; }
.kds-notes { font-size: 0.95rem; font-weight: 500; color: var(--accent); margin-top: 0.4rem; font-style: italic; }
.kds-btn { width: 100%; font-size: 1.1rem; padding: 1rem; border-radius: 8px; }
.kds-ready-btn { background: var(--olive); color: white; border-color: transparent; }
.kds-ready-btn:hover { background: #27ae60; }
.kds-serve-btn { background: var(--accent); color: white; border-color: transparent; }
.waiting-badge { text-align: center; font-style: italic; color: var(--text-soft); font-weight: bold; padding: 1rem; background: var(--bg-accent); border-radius: 8px; }
</style>

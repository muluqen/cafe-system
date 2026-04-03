<template>
  <section class="customer-stack">
    <section class="panel">
      <header class="panel-header panel-header-rich">
        <div>
          <span class="eyebrow">My Orders</span>
          <h1 class="panel-title">Order history and quick reorders</h1>
        </div>
        <button class="button button-soft" :disabled="loading" @click="loadOrders">
          {{ loading ? "Refreshing..." : "Refresh" }}
        </button>
      </header>

      <div class="content-pad">
        <p v-if="error" class="error-text">{{ error }}</p>

        <div v-if="orders.length" class="order-grid">
          <article v-for="order in orders" :key="order.id" class="order-card">
            <div class="order-head">
              <strong>{{ order.order_number }}</strong>
              <span class="badge">{{ order.status || "pending" }}</span>
            </div>
            <p class="muted">{{ order.restaurant?.name || "Restaurant" }}</p>
            <p class="muted">{{ formatDate(order.placed_at || order.created_at) }}</p>

            <div class="order-items">
              <p v-for="item in order.items || []" :key="item.id" class="order-item-row">
                {{ Number(item.quantity) }}x {{ item.item_name }}
              </p>
            </div>

            <div class="order-foot">
              <strong>${{ Number(order.total || 0).toFixed(2) }}</strong>
              <div class="menu-actions">
                <button class="button button-soft" type="button" @click="openOrder(order)">
                  View order
                </button>
                <button
                  v-if="(order.status || 'pending') === 'pending'"
                  class="button button-soft danger"
                  type="button"
                  :disabled="deletingId === order.id"
                  @click="deleteOrder(order)"
                >
                  {{ deletingId === order.id ? "Removing..." : "Remove" }}
                </button>
                <button class="button" :disabled="reorderingId === order.id" @click="reorder(order)">
                  {{ reorderingId === order.id ? "Reordering..." : "Order again" }}
                </button>
              </div>
            </div>
          </article>
        </div>

        <div v-else class="empty">
          No orders yet. Place your first customized order from Discover.
        </div>
      </div>
    </section>

    <div v-if="selectedOrder" class="modal-backdrop" @click.self="closeOrder">
      <div class="modal-card panel">
        <header class="panel-header">
          <div>
            <span class="eyebrow">View Order</span>
            <h2 class="panel-title">{{ selectedOrder.order_number }}</h2>
          </div>
          <button class="button button-soft" type="button" @click="closeOrder">Close</button>
        </header>

        <div class="content-pad order-detail-stack">
          <div class="order-detail-meta">
            <span class="badge">{{ editOrder.status || "pending" }}</span>
            <span class="muted">{{ selectedOrder.restaurant?.name || "Restaurant" }}</span>
          </div>

          <div v-if="editItems.length" class="order-edit-list">
            <article v-for="item in editItems" :key="item.id" class="order-edit-item">
              <div>
                <strong>{{ item.item_name }}</strong>
                <p class="muted">{{ summarizeItem(item) }}</p>
              </div>
              <div class="order-item-tools">
                <button class="button button-soft" type="button" @click="openItemCustomizer(item)">
                  Review item
                </button>
                <div class="cart-qty">
                  <button class="qty-btn" type="button" @click="changeItemQty(item, -1)">-</button>
                  <span>{{ item.quantity }}</span>
                  <button class="qty-btn" type="button" @click="changeItemQty(item, 1)">+</button>
                  <button class="qty-btn danger" type="button" @click="removeItem(item.id)">x</button>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="empty">This order has no items left.</div>

          <label>
            Order note
            <textarea
              v-model="editOrder.notes"
              class="input kitchen-note"
              rows="4"
              placeholder="Update any note for the kitchen..."
            />
          </label>

          <div class="totals">
            <span>Subtotal</span>
            <strong>${{ editSubtotal.toFixed(2) }}</strong>
            <span>Tax</span>
            <strong>${{ editTax.toFixed(2) }}</strong>
            <span>Total</span>
            <strong>${{ editTotal.toFixed(2) }}</strong>
          </div>

          <p v-if="editError" class="error-text">{{ editError }}</p>

          <div class="order-detail-actions">
            <button
              class="button button-soft danger"
              type="button"
              :disabled="deletingId === editOrder.id || !canEditSelectedOrder"
              @click="deleteOrder(selectedOrder)"
            >
              {{ deletingId === editOrder.id ? "Removing..." : "Remove order" }}
            </button>
            <button class="button button-soft" type="button" @click="closeOrder">Keep as is</button>
            <button
              class="button"
              type="button"
              :disabled="saving || !canEditSelectedOrder"
              @click="saveOrderChanges"
            >
              {{ saving ? "Saving..." : "Save changes" }}
            </button>
          </div>
          <p v-if="!canEditSelectedOrder" class="muted">
            Only pending orders can be edited.
          </p>
        </div>
      </div>
    </div>

    <div v-if="editingItem" class="modal-backdrop" @click.self="closeItemCustomizer">
      <div class="modal-card panel">
        <header class="panel-header">
          <div>
            <span class="eyebrow">Review Item</span>
            <h2 class="panel-title">Fix {{ editingItem.item_name }}</h2>
          </div>
          <button class="button button-soft" type="button" @click="closeItemCustomizer">Close</button>
        </header>

        <div class="content-pad customize-grid">
          <section class="item-review-card">
            <div class="item-review-head">
              <div>
                <strong>{{ editingItem.item_name }}</strong>
                <p class="muted">This is what is currently in your order. Update anything you want before saving.</p>
              </div>
              <span class="badge">{{ itemDraft.quantity }}x</span>
            </div>

            <div class="item-review-grid">
              <div class="item-review-block">
                <span class="selector-label">Currently added</span>
                <div v-if="itemDraft.additions.length" class="quick-grid">
                  <button
                    v-for="ingredient in itemDraft.additions"
                    :key="`added-${ingredient}`"
                    class="quick-chip active"
                    type="button"
                    @click="removeDraftIngredient('additions', ingredient)"
                  >
                    + {{ ingredient }}
                  </button>
                </div>
                <p v-else class="muted">Nothing extra added yet.</p>
              </div>

              <div class="item-review-block">
                <span class="selector-label">Currently removed</span>
                <div v-if="itemDraft.removals.length" class="quick-grid">
                  <button
                    v-for="ingredient in itemDraft.removals"
                    :key="`removed-${ingredient}`"
                    class="quick-chip active remove"
                    type="button"
                    @click="removeDraftIngredient('removals', ingredient)"
                  >
                    - {{ ingredient }}
                  </button>
                </div>
                <p v-else class="muted">Nothing removed yet.</p>
              </div>
            </div>

            <div class="item-review-block">
              <span class="selector-label">Current note</span>
              <p class="item-review-note">
                {{ itemDraft.note || "No special note yet." }}
              </p>
            </div>
          </section>

          <label>
            Quantity
            <input v-model.number="itemDraft.quantity" class="input" type="number" min="1" />
          </label>
          <label>
            Add something extra
            <input
              v-model="itemDraft.addText"
              class="input"
              type="text"
              :placeholder="editAddPlaceholder"
              @keyup.enter.prevent="pushItemCustom('additions', itemDraft.addText)"
            />
          </label>
          <label>
            Leave something out
            <input
              v-model="itemDraft.removeText"
              class="input"
              type="text"
              :placeholder="editRemovePlaceholder"
              @keyup.enter.prevent="pushItemCustom('removals', itemDraft.removeText)"
            />
          </label>
          <label class="custom-free-note">
            Add your own instruction
            <textarea
              v-model="itemDraft.note"
              class="input kitchen-note"
              rows="4"
              :placeholder="editNotePlaceholder"
            />
          </label>

          <div>
            <div class="customize-mode">
              <span class="selector-label">Ingredient choices</span>
              <div class="customize-mode-switch">
                <button
                  class="pill-btn"
                  :class="{ active: itemCustomizeMode === 'additions' }"
                  type="button"
                  @click="itemCustomizeMode = 'additions'"
                >
                  Add
                </button>
                <button
                  class="pill-btn"
                  :class="{ active: itemCustomizeMode === 'removals' }"
                  type="button"
                  @click="itemCustomizeMode = 'removals'"
                >
                  Remove
                </button>
              </div>
            </div>

            <div class="quick-grid">
              <button
                v-for="ingredient in editContextualIngredients"
                :key="ingredient"
                class="quick-chip"
                :class="{
                  active: isItemIngredientSelected(ingredient, itemCustomizeMode),
                  remove: itemCustomizeMode === 'removals'
                }"
                type="button"
                @click="toggleItemIngredientChoice(ingredient)"
              >
                {{ ingredient }}
              </button>
            </div>
            <p v-if="!editContextualIngredients.length" class="muted">
              No matching ingredient suggestions for this item yet. You can still type your own.
            </p>
            <p class="muted">
              Choose an ingredient, then tap it again in the list above if you want to remove it from your changes.
            </p>
          </div>

          <div class="order-detail-actions">
            <button class="button button-soft" type="button" @click="closeItemCustomizer">
              Cancel
            </button>
            <button class="button" type="button" @click="applyItemCustomization">
              Apply changes
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const reorderingId = ref(null);
const deletingId = ref(null);
const error = ref("");
const editError = ref("");
const orders = ref([]);
const selectedOrder = ref(null);
const editItems = ref([]);
const editingItem = ref(null);
const itemCustomizeMode = ref("additions");
const itemDraft = reactive({
  quantity: 1,
  additions: [],
  removals: [],
  addText: "",
  removeText: "",
  note: ""
});
const editOrder = ref({
  id: null,
  notes: "",
  status: "pending"
});

const canEditSelectedOrder = computed(() => editOrder.value.status === "pending");
const editSubtotal = computed(() =>
  editItems.value.reduce(
    (sum, item) => sum + Number(item.unit_price || 0) * Number(item.quantity || 0),
    0
  )
);
const editTax = computed(() => editSubtotal.value * 0.1);
const editTotal = computed(() => editSubtotal.value + editTax.value);
const editContextText = computed(() => {
  if (!editingItem.value) {
    return "";
  }

  return [
    editingItem.value.item_name,
    editingItem.value.menuItem?.description,
    editingItem.value.menuItem?.name
  ]
    .join(" ")
    .toLowerCase();
});
const editContextualIngredients = computed(() => {
  if (!editingItem.value) {
    return [];
  }

  const groups = [
    {
      match: ["latte", "coffee", "espresso", "cappuccino", "mocha", "macchiato", "tea", "drink"],
      picks: ["milk", "oat", "almond", "soy", "vanilla", "caramel", "sugar", "honey", "cream", "cinnamon", "ice", "chocolate"]
    },
    {
      match: ["burger", "sandwich", "wrap", "shawarma"],
      picks: ["lettuce", "tomato", "onion", "cheese", "pickle", "bbq", "sauce", "mayo", "ketchup", "mustard", "bacon"]
    },
    {
      match: ["pizza", "pasta"],
      picks: ["cheese", "olive", "mushroom", "pepper", "onion", "tomato", "sauce", "chili"]
    },
    {
      match: ["salad", "bowl"],
      picks: ["lettuce", "tomato", "onion", "olive", "cheese", "avocado", "cucumber", "dressing"]
    }
  ];

  return groups
    .filter((group) => group.match.some((token) => editContextText.value.includes(token)))
    .flatMap((group) => group.picks)
    .filter((value, index, all) => all.indexOf(value) === index)
    .slice(0, 10);
});
const editAddPlaceholder = computed(() =>
  editContextText.value.match(/latte|coffee|espresso|tea|drink/)
    ? "e.g. extra shot, oat milk, vanilla"
    : editContextText.value.match(/burger|sandwich|wrap/)
      ? "e.g. cheese, bbq sauce, extra pickle"
      : "e.g. extra sauce, cheese, spice"
);
const editRemovePlaceholder = computed(() =>
  editContextText.value.match(/latte|coffee|espresso|tea|drink/)
    ? "e.g. sugar, foam, whipped cream"
    : editContextText.value.match(/burger|sandwich|wrap/)
      ? "e.g. lettuce, onion, tomato"
      : "e.g. onions, sauce, garnish"
);
const editNotePlaceholder = computed(() =>
  editContextText.value.match(/latte|coffee|espresso|tea|drink/)
    ? "Less sweet, extra hot, no foam..."
    : "Any special prep notes..."
);

function formatDate(value) {
  if (!value) {
    return "-";
  }
  return new Date(value).toLocaleString();
}

function parseNoteParts(value, label) {
  const text = String(value || "");
  const match = text.match(new RegExp(`${label}:\\s*([^|]+)`));
  if (!match?.[1]) {
    return [];
  }

  return match[1]
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function parseSingleNote(value) {
  const text = String(value || "");
  const match = text.match(/Note:\s*([^|]+)/);
  return match?.[1]?.trim() || "";
}

function serializeItemNote(item) {
  const lines = [];
  if (item.additions?.length) {
    lines.push(`Add: ${item.additions.join(", ")}`);
  }
  if (item.removals?.length) {
    lines.push(`Remove: ${item.removals.join(", ")}`);
  }
  if (item.note) {
    lines.push(`Note: ${item.note}`);
  }
  return lines.join(" | ");
}

function summarizeItem(item) {
  return serializeItemNote(item) || "Standard preparation";
}

async function loadOrders() {
  loading.value = true;
  error.value = "";
  try {
    const { data } = await api.get("/orders", { params: { per_page: 100 } });
    orders.value = data?.data || [];
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to load your orders.";
  } finally {
    loading.value = false;
  }
}

function openOrder(order) {
  selectedOrder.value = order;
  editError.value = "";
  editOrder.value = {
    id: order.id,
    notes: order.notes || "",
    status: order.status || "pending"
  };
  editItems.value = (order.items || []).map((item) => ({
    id: item.id,
    item_name: item.item_name,
    quantity: Number(item.quantity || 1),
    unit_price: Number(item.unit_price || 0),
    line_total: Number(item.line_total || 0),
    menu_item_id: item.menu_item_id,
    notes: item.notes || "",
    menuItem: item.menu_item || item.menuItem || null,
    additions: parseNoteParts(item.notes, "Add"),
    removals: parseNoteParts(item.notes, "Remove"),
    note: parseSingleNote(item.notes)
  }));
}

function closeOrder() {
  selectedOrder.value = null;
  editItems.value = [];
  editOrder.value = {
    id: null,
    notes: "",
    status: "pending"
  };
  editError.value = "";
  closeItemCustomizer();
}

function changeItemQty(item, delta) {
  if (!canEditSelectedOrder.value) {
    return;
  }

  item.quantity = Math.max(1, Number(item.quantity) + delta);
  item.line_total = Number((item.quantity * item.unit_price).toFixed(2));
}

function removeItem(itemId) {
  if (!canEditSelectedOrder.value) {
    return;
  }

  editItems.value = editItems.value.filter((item) => item.id !== itemId);
}

function openItemCustomizer(item) {
  if (!canEditSelectedOrder.value) {
    return;
  }

  editingItem.value = item;
  itemCustomizeMode.value = "additions";
  itemDraft.quantity = Number(item.quantity || 1);
  itemDraft.additions = [...(item.additions || [])];
  itemDraft.removals = [...(item.removals || [])];
  itemDraft.addText = "";
  itemDraft.removeText = "";
  itemDraft.note = item.note || "";
}

function closeItemCustomizer() {
  editingItem.value = null;
  itemCustomizeMode.value = "additions";
  itemDraft.quantity = 1;
  itemDraft.additions = [];
  itemDraft.removals = [];
  itemDraft.addText = "";
  itemDraft.removeText = "";
  itemDraft.note = "";
}

function pushItemCustom(target, raw) {
  const value = String(raw || "").trim();
  if (!value) {
    return;
  }

  if (!itemDraft[target].includes(value)) {
    itemDraft[target].push(value);
  }

  if (target === "additions") {
    itemDraft.addText = "";
  } else {
    itemDraft.removeText = "";
  }
}

function isItemIngredientSelected(ingredient, target) {
  return itemDraft[target].includes(ingredient);
}

function removeDraftIngredient(target, ingredient) {
  itemDraft[target] = itemDraft[target].filter((name) => name !== ingredient);
}

function toggleItemIngredientChoice(ingredient) {
  const activeBucket = itemDraft[itemCustomizeMode.value];
  const oppositeKey = itemCustomizeMode.value === "additions" ? "removals" : "additions";

  if (activeBucket.includes(ingredient)) {
    itemDraft[itemCustomizeMode.value] = activeBucket.filter((name) => name !== ingredient);
    return;
  }

  itemDraft[oppositeKey] = itemDraft[oppositeKey].filter((name) => name !== ingredient);
  itemDraft[itemCustomizeMode.value].push(ingredient);
}

function applyItemCustomization() {
  if (!editingItem.value) {
    return;
  }

  pushItemCustom("additions", itemDraft.addText);
  pushItemCustom("removals", itemDraft.removeText);

  editingItem.value.quantity = Math.max(1, Number(itemDraft.quantity) || 1);
  editingItem.value.additions = [...itemDraft.additions];
  editingItem.value.removals = [...itemDraft.removals];
  editingItem.value.note = itemDraft.note.trim();
  editingItem.value.notes = serializeItemNote(editingItem.value) || null;
  editingItem.value.line_total = Number(
    (editingItem.value.quantity * Number(editingItem.value.unit_price || 0)).toFixed(2)
  );

  closeItemCustomizer();
}

async function saveOrderChanges() {
  if (!selectedOrder.value || !canEditSelectedOrder.value) {
    return;
  }

  saving.value = true;
  editError.value = "";

  try {
    const originalIds = (selectedOrder.value.items || []).map((item) => item.id);
    const currentIds = editItems.value.map((item) => item.id);
    const deletedIds = originalIds.filter((id) => !currentIds.includes(id));

    await Promise.all(
      editItems.value.map((item) =>
        api.put(`/order_items/${item.id}`, {
          quantity: item.quantity,
          line_total: Number((item.quantity * item.unit_price).toFixed(2)),
          notes: serializeItemNote(item) || null
        })
      )
    );

    await Promise.all(deletedIds.map((id) => api.delete(`/order_items/${id}`)));

    await api.put(`/orders/${selectedOrder.value.id}`, {
      subtotal: Number(editSubtotal.value.toFixed(2)),
      tax: Number(editTax.value.toFixed(2)),
      total: Number(editTotal.value.toFixed(2)),
      notes: editOrder.value.notes || null
    });

    await loadOrders();
    const refreshedOrder = orders.value.find((order) => order.id === selectedOrder.value.id);
    if (refreshedOrder) {
      openOrder(refreshedOrder);
    } else {
      closeOrder();
    }
  } catch (requestError) {
    editError.value = requestError?.response?.data?.message || "Unable to update the order.";
  } finally {
    saving.value = false;
  }
}

async function deleteOrder(order) {
  if (!order?.id || (order.status || "pending") !== "pending") {
    return;
  }

  deletingId.value = order.id;
  error.value = "";
  editError.value = "";

  try {
    await api.delete(`/orders/${order.id}`);
    await loadOrders();

    if (selectedOrder.value?.id === order.id) {
      closeOrder();
    }
  } catch (requestError) {
    const message = requestError?.response?.data?.message || "Unable to remove the order.";
    error.value = message;
    editError.value = message;
  } finally {
    deletingId.value = null;
  }
}

async function reorder(order) {
  if (!order?.items?.length) {
    return;
  }

  reorderingId.value = order.id;
  error.value = "";

  try {
    if (order.restaurant_id) {
      auth.setSelectedRestaurant(order.restaurant_id);
    }

    const orderPayload = {
      table_id: null,
      status: "pending",
      subtotal: Number(order.subtotal || 0),
      tax: Number(order.tax || 0),
      total: Number(order.total || 0),
      notes: `Reorder from ${order.order_number}`,
      placed_at: new Date().toISOString()
    };
    const createdOrder = await api.post("/orders", orderPayload);
    const orderId = createdOrder.data?.id;

    await Promise.all(
      order.items.map((item) =>
        api.post("/order_items", {
          order_id: orderId,
          menu_item_id: item.menu_item_id,
          item_name: item.item_name,
          quantity: item.quantity,
          unit_price: item.unit_price,
          line_total: item.line_total,
          notes: item.notes
        })
      )
    );

    await loadOrders();
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to reorder now.";
  } finally {
    reorderingId.value = null;
  }
}

onMounted(loadOrders);
</script>

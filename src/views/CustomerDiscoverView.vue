<template>
  <section class="customer-stack" :style="heroStyle">
    <section class="customer-hero panel">
      <div class="customer-brand-copy">
        <div class="customer-brand-top">
          <div v-if="restaurantBranding.logoUrl" class="customer-logo-wrap">
            <img :src="restaurantBranding.logoUrl" :alt="`${selectedRestaurantName} logo`" class="customer-logo" />
          </div>
          <div v-else class="customer-logo-fallback">{{ restaurantInitials }}</div>
          <span class="eyebrow" :style="{ color: restaurantBranding.brandColor || undefined }">
            {{ selectedRestaurantName === "Not selected" ? "DineDirect Discover" : `${selectedRestaurantName} on DineDirect` }}
          </span>
        </div>
        <h1 class="customer-title">
          {{ restaurantBranding.headline || "Pick your spot, customize your meal,send it to the kitchen." }}
        </h1>
        <p class="muted">
          {{
            restaurantBranding.customerMessage ||
            "Everything here is for your selected restaurant only. You can adjust ingredients and keep your order history for quick reorders."
          }}
        </p>
      </div>

      <div class="customer-hero-actions">
        <div class="restaurant-pill">
          <span class="restaurant-pill-label">Restaurant</span>
          <strong>{{ selectedRestaurantName }}</strong>
        </div>
        <label>
          Table (optional)
          <select v-model="selectedTableId" class="input">
            <option value="">No table selected</option>
            <option v-for="table in availableTables" :key="table.id" :value="table.id">
              {{ table.name }} ({{ table.capacity }} seats)
            </option>
          </select>
        </label>
      </div>
    </section>

    <section v-if="!auth.selectedRestaurantId" class="panel empty">
      Select a restaurant from the sidebar to start.
    </section>

    <section v-else class="customer-layout">
      <section class="panel menu-panel">
        <header class="panel-header">
          <div>
            <span class="eyebrow">Menu</span>
            <h2 class="panel-title">Available right now</h2>
          </div>
          <button class="button button-soft" :disabled="loading" @click="loadData">
            {{ loading ? "Refreshing..." : "Refresh menu" }}
          </button>
        </header>
        <div class="content-pad">
          <section class="bowl-builder-banner">
            <div>
              <span class="eyebrow">Custom Bowl</span>
              <h3 class="panel-title">Make your own bowl</h3>
              <p class="muted">
                Choose a base, protein, toppings, and sauce, then send your bowl exactly the way you want it.
              </p>
            </div>
            <button class="button" type="button" @click="openBowlBuilder">Build your bowl</button>
          </section>

          <div class="category-pills">
            <button
              v-for="category in categories"
              :key="category.id"
              class="pill-btn"
              :class="{ active: activeCategoryId === category.id }"
              type="button"
              @click="activeCategoryId = category.id"
            >
              {{ category.name }}
            </button>
          </div>

          <div v-if="filteredItems.length" class="menu-grid">
            <article v-for="item in filteredItems" :key="item.id" class="menu-card">
              <div>
                <h3>{{ item.name }}</h3>
                <p class="muted">{{ item.description || "Chef-crafted and made fresh." }}</p>
              </div>
              <div class="menu-card-foot">
                <strong>${{ Number(item.price || 0).toFixed(2) }}</strong>
                <div class="menu-actions">
                  <button class="button button-soft" type="button" @click="orderDefault(item)">Order</button>
                  <button class="button" type="button" @click="openCustomizer(item)">Customize</button>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="empty">No items in this category yet.</div>
        </div>
      </section>

      <section class="panel cart-panel">
        <header class="panel-header">
          <div>
            <span class="eyebrow">Your Order</span>
            <h2 class="panel-title">What you're sending</h2>
          </div>
        </header>
        <div class="content-pad">
          <div v-if="cart.length" class="cart-list">
            <article v-for="entry in cart" :key="entry.uid" class="cart-item">
              <div>
                <strong>{{ entry.item_name }}</strong>
                <p class="muted">{{ summarizeCustomizations(entry) }}</p>
              </div>
              <div class="cart-qty">
                <button class="qty-btn" type="button" @click="changeQty(entry, -1)">-</button>
                <span>{{ entry.quantity }}</span>
                <button class="qty-btn" type="button" @click="changeQty(entry, 1)">+</button>
                <button class="qty-btn danger" type="button" @click="removeCartItem(entry.uid)">x</button>
              </div>
            </article>
          </div>
          <div v-else class="empty">Nothing in your order yet.</div>

          <label>
            Kitchen note
            <textarea
              v-model="orderNote"
              class="input kitchen-note"
              rows="4"
              placeholder="Share any serving or preparation note for the kitchen..."
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

          <p v-if="error" class="error-text">{{ error }}</p>

          <button class="button order-btn" :disabled="submitting || !cart.length" @click="placeOrder">
            {{ submitting ? "Sending to kitchen..." : "Place order" }}
          </button>
        </div>
      </section>
    </section>

    <div v-if="draftItem" class="modal-backdrop" @click.self="draftItem = null">
      <div class="modal-card panel">
        <header class="panel-header">
          <h2 class="panel-title">Customize {{ draftItem.name }}</h2>
          <button class="button button-soft" type="button" @click="draftItem = null">Close</button>
        </header>
        <div class="content-pad customize-grid">
          <label>
            Quantity
            <input v-model.number="draft.quantity" class="input" type="number" min="1" />
          </label>
          <label>
            Add something extra
            <input
              v-model="draft.addText"
              class="input"
              type="text"
              :placeholder="addPlaceholder"
              @keyup.enter.prevent="pushCustom('additions', draft.addText)"
            />
          </label>
          <label>
            Leave something out
            <input
              v-model="draft.removeText"
              class="input"
              type="text"
              :placeholder="removePlaceholder"
              @keyup.enter.prevent="pushCustom('removals', draft.removeText)"
            />
          </label>
          <label>
            Item note
            <input v-model="draft.note" class="input" type="text" :placeholder="notePlaceholder" />
          </label>

          <div>
            <div class="customize-mode">
              <span class="selector-label">Ingredient choices</span>
              <div class="customize-mode-switch">
                <button
                  class="pill-btn"
                  :class="{ active: customizeMode === 'additions' }"
                  type="button"
                  @click="customizeMode = 'additions'"
                >
                  Add
                </button>
                <button
                  class="pill-btn"
                  :class="{ active: customizeMode === 'removals' }"
                  type="button"
                  @click="customizeMode = 'removals'"
                >
                  Remove
                </button>
              </div>
            </div>
            <div class="quick-grid">
              <button
                v-for="ingredient in contextualIngredients"
                :key="ingredient"
                class="quick-chip"
                :class="{
                  active: isIngredientSelected(ingredient, customizeMode),
                  remove: customizeMode === 'removals'
                }"
                type="button"
                @click="toggleIngredientChoice(ingredient)"
              >
                {{ ingredient }}
              </button>
            </div>
            <p v-if="!contextualIngredients.length" class="muted">
              No matching ingredient suggestions for this item yet. You can still type your own.
            </p>
          </div>

          <button class="button" type="button" @click="commitDraft">Add to order</button>
        </div>
      </div>
    </div>

    <div v-if="bowlBuilderOpen" class="modal-backdrop" @click.self="closeBowlBuilder">
      <div class="modal-card panel">
        <header class="panel-header">
          <div>
            <span class="eyebrow">Custom Bowl</span>
            <h2 class="panel-title">Make your own bowl</h2>
          </div>
          <button class="button button-soft" type="button" @click="closeBowlBuilder">Close</button>
        </header>
        <div class="content-pad bowl-builder-grid">
          <label>
            Bowl name
            <input
              v-model="bowlDraft.name"
              class="input"
              type="text"
              placeholder="My bowl"
            />
          </label>

          <label>
            Quantity
            <input v-model.number="bowlDraft.quantity" class="input" type="number" min="1" />
          </label>

          <section class="bowl-section">
            <div class="bowl-section-head">
              <span class="selector-label">Base</span>
              <strong>{{ bowlDraft.bases.length ? `${bowlDraft.bases.length} selected` : "Pick one or more bases" }}</strong>
            </div>
            <div class="quick-grid">
              <button
                v-for="option in bowlBaseOptions"
                :key="option"
                class="quick-chip"
                :class="{ active: bowlDraft.bases.includes(option) }"
                type="button"
                @click="toggleBowlSelection('bases', option)"
              >
                {{ option }}
              </button>
            </div>
          </section>

          <section class="bowl-section">
            <div class="bowl-section-head">
              <span class="selector-label">Protein</span>
              <strong>{{ bowlDraft.proteins.length ? `${bowlDraft.proteins.length} selected` : "Pick one or more proteins" }}</strong>
            </div>
            <div class="quick-grid">
              <button
                v-for="option in bowlProteinOptions"
                :key="option"
                class="quick-chip"
                :class="{ active: bowlDraft.proteins.includes(option) }"
                type="button"
                @click="toggleBowlSelection('proteins', option)"
              >
                {{ option }}
              </button>
            </div>
          </section>

          <section class="bowl-section">
            <div class="bowl-section-head">
              <span class="selector-label">Fresh toppings</span>
              <strong>{{ bowlDraft.toppings.length ? `${bowlDraft.toppings.length} selected` : "Choose as many as you want" }}</strong>
            </div>
            <div class="quick-grid">
              <button
                v-for="option in bowlToppingOptions"
                :key="option"
                class="quick-chip"
                :class="{ active: bowlDraft.toppings.includes(option) }"
                type="button"
                @click="toggleBowlSelection('toppings', option)"
              >
                {{ option }}
              </button>
            </div>
          </section>

          <section class="bowl-section">
            <div class="bowl-section-head">
              <span class="selector-label">Sauce or dressing</span>
              <strong>{{ bowlDraft.sauces.length ? `${bowlDraft.sauces.length} selected` : "Pick one or more" }}</strong>
            </div>
            <div class="quick-grid">
              <button
                v-for="option in bowlSauceOptions"
                :key="option"
                class="quick-chip"
                :class="{ active: bowlDraft.sauces.includes(option) }"
                type="button"
                @click="toggleBowlSelection('sauces', option)"
              >
                {{ option }}
              </button>
            </div>
          </section>

          <section class="bowl-section">
            <div class="bowl-section-head">
              <span class="selector-label">Premium extras</span>
              <strong>{{ bowlDraft.extras.length }} selected</strong>
            </div>
            <div class="quick-grid">
              <button
                v-for="option in bowlExtraOptions"
                :key="option"
                class="quick-chip"
                :class="{ active: bowlDraft.extras.includes(option) }"
                type="button"
                @click="toggleBowlSelection('extras', option)"
              >
                {{ option }}
              </button>
            </div>
          </section>

          <label>
            Bowl note
            <textarea
              v-model="bowlDraft.note"
              class="input kitchen-note"
              rows="4"
              placeholder="Dressing on the side, extra spicy, cut avocado small..."
            />
          </label>

          <div class="bowl-summary">
            <div>
              <span class="selector-label">Your bowl</span>
              <p class="muted">{{ bowlPreviewText }}</p>
            </div>
            <div class="bowl-summary-price">
              <strong>${{ bowlPrice.toFixed(2) }}</strong>
              <span class="muted">for {{ bowlDraft.quantity }} bowl{{ bowlDraft.quantity > 1 ? "s" : "" }}</span>
            </div>
          </div>

          <p v-if="bowlError" class="error-text">{{ bowlError }}</p>

          <div class="order-detail-actions">
            <button class="button button-soft" type="button" @click="closeBowlBuilder">
              Cancel
            </button>
            <button class="button" type="button" @click="commitBowl">
              Add bowl to order
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";
import { getRestaurantBranding } from "../utils/restaurantBranding";

const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const submitting = ref(false);
const error = ref("");
const bowlError = ref("");
const categories = ref([]);
const menuItems = ref([]);
const ingredients = ref([]);
const tables = ref([]);
const activeCategoryId = ref(null);
const selectedTableId = ref("");
const orderNote = ref("");
const cart = ref([]);
const draftItem = ref(null);
const bowlBuilderOpen = ref(false);
const customizeMode = ref("additions");
const draft = reactive({
  quantity: 1,
  additions: [],
  removals: [],
  addText: "",
  removeText: "",
  note: ""
});
const bowlDraft = reactive({
  name: "",
  bases: [],
  proteins: [],
  toppings: [],
  sauces: [],
  extras: [],
  note: "",
  quantity: 1
});

const availableTables = computed(() =>
  tables.value.filter((table) => table.is_active && table.status !== "occupied")
);

const filteredItems = computed(() =>
  menuItems.value.filter(
    (item) =>
      item.is_available &&
      (!activeCategoryId.value || item.menu_category_id === activeCategoryId.value)
  )
);

const selectedRestaurantName = computed(() => {
  const id = Number(auth.selectedRestaurantId || 0);
  const found = auth.publicRestaurants.find((row) => row.id === id);
  return found?.name || "Not selected";
});
const restaurantBranding = computed(() => getRestaurantBranding(auth.selectedRestaurantId));
const restaurantInitials = computed(() =>
  String(selectedRestaurantName.value || "R")
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase() || "R"
);
const heroStyle = computed(() => ({
  "--restaurant-brand": restaurantBranding.value.brandColor || "#1A2A40",
  "--restaurant-brand-soft": `${restaurantBranding.value.brandColor || "#1A2A40"}1f`,
  "--restaurant-brand-gradient": `linear-gradient(135deg, ${(restaurantBranding.value.brandColors || [restaurantBranding.value.brandColor || "#1A2A40"]).join(", ")})`
}));
const bowlBaseOptions = computed(() =>
  ingredientMatches(
    ["brown rice", "white rice", "quinoa", "greens", "lettuce", "spinach", "couscous"],
    ["Brown rice", "White rice", "Quinoa", "Mixed greens", "Romaine", "Spinach"],
    6
  )
);
const bowlProteinOptions = computed(() =>
  ingredientMatches(
    ["chicken", "beef", "steak", "salmon", "tuna", "shrimp", "falafel", "tofu", "egg"],
    ["Grilled chicken", "Spiced beef", "Roasted salmon", "Falafel", "Tofu", "Boiled egg"],
    6
  )
);
const bowlToppingOptions = computed(() =>
  ingredientMatches(
    ["tomato", "onion", "corn", "cucumber", "olive", "avocado", "pepper", "carrot", "bean", "cheese", "chickpea"],
    ["Cherry tomato", "Pickled onion", "Sweet corn", "Cucumber", "Avocado", "Feta", "Chickpeas", "Carrot ribbons"],
    10
  )
);
const bowlSauceOptions = computed(() =>
  ingredientMatches(
    ["tahini", "vinaigrette", "pesto", "bbq", "ranch", "mayo", "sauce", "hummus", "yogurt"],
    ["Lemon tahini", "Herb vinaigrette", "Spicy mayo", "Pesto yogurt", "Smoky bbq"],
    6
  )
);
const bowlExtraOptions = computed(() =>
  ingredientMatches(
    ["egg", "avocado", "cheese", "jalapeno", "nuts", "seed", "pickle"],
    ["Extra avocado", "Soft egg", "Feta crumble", "Crunchy seeds", "Jalapeno"],
    8
  )
);
const bowlPrice = computed(() => {
  const baseCharge = Math.max(0, bowlDraft.bases.length - 1) * 1.25;
  const proteinCharge = Math.max(0, bowlDraft.proteins.length - 1) * 3.25;
  const sauceCharge = Math.max(0, bowlDraft.sauces.length - 1) * 0.75;
  const extrasCharge = bowlDraft.extras.length * 1.5;
  const toppingsCharge = Math.max(0, bowlDraft.toppings.length - 4) * 0.75;
  const singleBowl = 11.5 + baseCharge + proteinCharge + sauceCharge + extrasCharge + toppingsCharge;
  return singleBowl * Math.max(1, Number(bowlDraft.quantity) || 1);
});
const bowlPreviewText = computed(() => {
  const parts = [];
  if (bowlDraft.bases.length) parts.push(`Base: ${bowlDraft.bases.join(", ")}`);
  if (bowlDraft.proteins.length) parts.push(`Protein: ${bowlDraft.proteins.join(", ")}`);
  if (bowlDraft.toppings.length) parts.push(bowlDraft.toppings.join(", "));
  if (bowlDraft.sauces.length) parts.push(`Sauce: ${bowlDraft.sauces.join(", ")}`);
  if (bowlDraft.extras.length) parts.push(`Extras: ${bowlDraft.extras.join(", ")}`);
  if (bowlDraft.note.trim()) parts.push(`Note: ${bowlDraft.note.trim()}`);
  return parts.join(" | ") || "Choose as many bases, proteins, toppings, sauces, and extras as you want to preview your bowl.";
});
const draftCategoryName = computed(() => {
  const category = categories.value.find((row) => row.id === draftItem.value?.menu_category_id);
  return category?.name || "";
});
const contextualIngredients = computed(() => {
  if (!draftItem.value) {
    return [];
  }

  const contextText = [
    draftItem.value.name,
    draftItem.value.description,
    draftCategoryName.value
  ]
    .join(" ")
    .toLowerCase();

  const groups = [
    {
      match: ["latte", "coffee", "espresso", "cappuccino", "mocha", "macchiato", "tea", "drink"],
      picks: ["milk", "oat", "almond", "soy", "vanilla", "caramel", "sugar", "honey", "cream", "cinnamon", "ice", "water", "chocolate"]
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
    },
    {
      match: ["cake", "dessert", "cookie", "ice cream"],
      picks: ["chocolate", "cream", "caramel", "vanilla", "strawberry", "nuts"]
    }
  ];

  const matchedPickTokens = groups
    .filter((group) => group.match.some((token) => contextText.includes(token)))
    .flatMap((group) => group.picks);

  const scored = ingredients.value
    .map((ingredient) => {
      const name = String(ingredient.name || "").toLowerCase();
      let score = 0;

      if (contextText.includes(name)) {
        score += 3;
      }

      if (matchedPickTokens.some((token) => name.includes(token))) {
        score += 2;
      }

      const words = name.split(/[^a-z0-9]+/).filter(Boolean);
      if (words.some((word) => contextText.includes(word))) {
        score += 1;
      }

      return { name: ingredient.name, score };
    })
    .filter((entry) => entry.name && entry.score > 0)
    .sort((left, right) => right.score - left.score)
    .slice(0, 10)
    .map((entry) => entry.name);

  return [...new Set(scored)];
});
const addPlaceholder = computed(() => {
  const text = `${draftItem.value?.name || ""} ${draftCategoryName.value}`.toLowerCase();
  if (text.match(/latte|coffee|espresso|tea|drink/)) {
    return "e.g. extra shot, oat milk, vanilla";
  }
  if (text.match(/burger|sandwich|wrap/)) {
    return "e.g. cheese, bbq sauce, extra pickle";
  }
  if (text.match(/pizza|pasta/)) {
    return "e.g. extra cheese, chili, olives";
  }
  return "e.g. extra sauce, cheese, spice";
});
const removePlaceholder = computed(() => {
  const text = `${draftItem.value?.name || ""} ${draftCategoryName.value}`.toLowerCase();
  if (text.match(/latte|coffee|espresso|tea|drink/)) {
    return "e.g. sugar, foam, whipped cream";
  }
  if (text.match(/burger|sandwich|wrap/)) {
    return "e.g. lettuce, onion, tomato";
  }
  if (text.match(/pizza|pasta/)) {
    return "e.g. olives, onions, cheese";
  }
  return "e.g. onions, sauce, garnish";
});
const notePlaceholder = computed(() => {
  const text = `${draftItem.value?.name || ""} ${draftCategoryName.value}`.toLowerCase();
  if (text.match(/latte|coffee|espresso|tea|drink/)) {
    return "Less sweet, extra hot, no foam...";
  }
  if (text.match(/burger|sandwich|wrap|pizza|pasta/)) {
    return "Well done, light sauce, cut in half...";
  }
  return "Any special prep notes...";
});

const subtotal = computed(() =>
  cart.value.reduce((sum, entry) => sum + Number(entry.unit_price) * Number(entry.quantity), 0)
);
const tax = computed(() => subtotal.value * 0.1);
const total = computed(() => subtotal.value + tax.value);

function makeNote(entry) {
  const lines = [];
  if (entry.additions.length) {
    lines.push(`Add: ${entry.additions.join(", ")}`);
  }
  if (entry.removals.length) {
    lines.push(`Remove: ${entry.removals.join(", ")}`);
  }
  if (entry.note) {
    lines.push(`Note: ${entry.note}`);
  }
  return lines.join(" | ");
}

function summarizeCustomizations(entry) {
  const note = makeNote(entry);
  return note || "Standard preparation";
}

async function loadData() {
  if (!auth.selectedRestaurantId) {
    return;
  }
  loading.value = true;
  error.value = "";
  try {
    const [categoryRes, itemRes, tableRes, ingredientRes] = await Promise.all([
      api.get("/menu_categories", { params: { per_page: 200 } }),
      api.get("/menu_items", { params: { per_page: 300 } }),
      api.get("/tables", { params: { per_page: 200 } }),
      api.get("/ingredients", { params: { per_page: 200 } })
    ]);

    categories.value = categoryRes.data?.data || [];
    menuItems.value = itemRes.data?.data || [];
    tables.value = tableRes.data?.data || [];
    ingredients.value = ingredientRes.data?.data || [];

    if (!activeCategoryId.value && categories.value.length) {
      activeCategoryId.value = categories.value[0].id;
    }
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to load menu right now.";
  } finally {
    loading.value = false;
  }
}

function resetDraft() {
  draft.quantity = 1;
  draft.additions = [];
  draft.removals = [];
  draft.addText = "";
  draft.removeText = "";
  draft.note = "";
  customizeMode.value = "additions";
}

function ingredientMatches(tokens, fallback, limit) {
  const matched = ingredients.value
    .map((ingredient) => String(ingredient.name || "").trim())
    .filter(Boolean)
    .filter((name) => tokens.some((token) => name.toLowerCase().includes(token)))
    .slice(0, limit);

  if (matched.length) {
    return matched;
  }

  if (fallback?.length) {
    return fallback.slice(0, limit);
  }

  return [];
}

function resetBowlDraft() {
  bowlDraft.name = "";
  bowlDraft.bases = [];
  bowlDraft.proteins = [];
  bowlDraft.toppings = [];
  bowlDraft.sauces = [];
  bowlDraft.extras = [];
  bowlDraft.note = "";
  bowlDraft.quantity = 1;
  bowlError.value = "";
}

function openBowlBuilder() {
  resetBowlDraft();
  bowlBuilderOpen.value = true;
}

function closeBowlBuilder() {
  bowlBuilderOpen.value = false;
  bowlError.value = "";
}

function toggleBowlSelection(bucket, value) {
  bowlError.value = "";
  bowlDraft[bucket] = bowlDraft[bucket].includes(value)
    ? bowlDraft[bucket].filter((entry) => entry !== value)
    : [...bowlDraft[bucket], value];
}

function commitBowl() {
  bowlError.value = "";

  if (!bowlDraft.bases.length) {
    bowlError.value = "Pick at least one base for the bowl.";
    return;
  }

  if (!bowlDraft.proteins.length) {
    bowlError.value = "Pick at least one protein for the bowl.";
    return;
  }

  if (!bowlDraft.sauces.length) {
    bowlError.value = "Pick at least one sauce or dressing for the bowl.";
    return;
  }

  const label = bowlDraft.name.trim() || "Make your own bowl";
  const quantity = Math.max(1, Number(bowlDraft.quantity) || 1);
  const singleBowlPrice = Number((bowlPrice.value / quantity).toFixed(2));
  cart.value.push({
    uid: `bowl-${Date.now()}-${Math.random()}`,
    menu_item_id: null,
    item_name: label,
    quantity,
    unit_price: singleBowlPrice,
    additions: [
      `Base: ${bowlDraft.bases.join(", ")}`,
      `Protein: ${bowlDraft.proteins.join(", ")}`,
      bowlDraft.toppings.length ? `Toppings: ${bowlDraft.toppings.join(", ")}` : null,
      `Sauce: ${bowlDraft.sauces.join(", ")}`,
      bowlDraft.extras.length ? `Extras: ${bowlDraft.extras.join(", ")}` : null
    ].filter(Boolean),
    removals: [],
    note: bowlDraft.note.trim() || "Built with the custom bowl feature."
  });
  closeBowlBuilder();
}

function openCustomizer(item) {
  draftItem.value = item;
  resetDraft();
}

function orderDefault(item) {
  cart.value.push({
    uid: `${item.id}-${Date.now()}-${Math.random()}`,
    menu_item_id: item.id,
    item_name: item.name,
    quantity: 1,
    unit_price: Number(item.price || 0),
    additions: [],
    removals: [],
    note: ""
  });
}

function pushCustom(target, raw) {
  const value = String(raw || "").trim();
  if (!value) {
    return;
  }

  const bucket = draft[target];
  if (!bucket.includes(value)) {
    bucket.push(value);
  }

  if (target === "additions") {
    draft.addText = "";
  } else {
    draft.removeText = "";
  }
}

function isIngredientSelected(ingredient, target) {
  return draft[target].includes(ingredient);
}

function toggleIngredientChoice(ingredient) {
  const activeBucket = draft[customizeMode.value];
  const oppositeBucket = customizeMode.value === "additions" ? draft.removals : draft.additions;

  if (activeBucket.includes(ingredient)) {
    draft[customizeMode.value] = activeBucket.filter((name) => name !== ingredient);
    return;
  }

  if (oppositeBucket.includes(ingredient)) {
    if (customizeMode.value === "additions") {
      draft.removals = draft.removals.filter((name) => name !== ingredient);
    } else {
      draft.additions = draft.additions.filter((name) => name !== ingredient);
    }
  }

  draft[customizeMode.value].push(ingredient);
}

function commitDraft() {
  if (!draftItem.value) {
    return;
  }

  pushCustom("additions", draft.addText);
  pushCustom("removals", draft.removeText);

  cart.value.push({
    uid: `${draftItem.value.id}-${Date.now()}-${Math.random()}`,
    menu_item_id: draftItem.value.id,
    item_name: draftItem.value.name,
    quantity: Math.max(1, Number(draft.quantity) || 1),
    unit_price: Number(draftItem.value.price || 0),
    additions: [...draft.additions],
    removals: [...draft.removals],
    note: draft.note.trim()
  });

  draftItem.value = null;
  resetDraft();
}

function changeQty(entry, delta) {
  entry.quantity = Math.max(1, Number(entry.quantity) + delta);
}

function removeCartItem(uid) {
  cart.value = cart.value.filter((entry) => entry.uid !== uid);
}

async function placeOrder() {
  if (!cart.value.length) {
    return;
  }
  submitting.value = true;
  error.value = "";
  try {
    const orderPayload = {
      table_id: selectedTableId.value || null,
      status: "pending",
      subtotal: Number(subtotal.value.toFixed(2)),
      tax: Number(tax.value.toFixed(2)),
      total: Number(total.value.toFixed(2)),
      notes: orderNote.value.trim() || null,
      placed_at: new Date().toISOString()
    };

    const orderRes = await api.post("/orders", orderPayload);
    const orderId = orderRes.data?.id;

    await Promise.all(
      cart.value.map((entry) =>
        api.post("/order_items", {
          order_id: orderId,
          menu_item_id: entry.menu_item_id,
          item_name: entry.item_name,
          quantity: entry.quantity,
          unit_price: Number(entry.unit_price.toFixed(2)),
          line_total: Number((entry.unit_price * entry.quantity).toFixed(2)),
          notes: makeNote(entry) || null
        })
      )
    );

    cart.value = [];
    orderNote.value = "";
    selectedTableId.value = "";
    await router.push({ name: "customer-orders" });
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Order submission failed.";
  } finally {
    submitting.value = false;
  }
}

watch(
  () => auth.selectedRestaurantId,
  () => {
    activeCategoryId.value = null;
    cart.value = [];
    selectedTableId.value = "";
    loadData();
  }
);

onMounted(loadData);
</script>

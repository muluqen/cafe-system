<template>
  <div class="pos">
    <!-- Left: Menu -->
    <div class="pos__menu">
      <!-- Categories -->
      <div class="pos__cats">
        <button v-for="cat in categories" :key="cat" class="pos__cat" :class="{ 'pos__cat--active': activeCat === cat }" @click="activeCat = cat">{{ cat }}</button>
      </div>
      <!-- Grid -->
      <div class="pos__grid">
        <div v-for="item in filteredItems" :key="item.id" class="pos__item" @click="addToCart(item)">
          <div class="pos__item-name">{{ item.name }}</div>
          <div class="pos__item-price">${{ Number(item.price).toFixed(2) }}</div>
          <div class="pos__item-desc">{{ item.description || '' }}</div>
          <button class="pos__item-add" @click.stop="addToCart(item)">+</button>
        </div>
      </div>
    </div>

    <!-- Right: Order -->
    <div class="pos__order">
      <div class="pos__order-head">
        <div class="pos__order-title">Current Order</div>
        <div class="pos__toggle">
          <button class="pos__toggle-btn" :class="{ 'pos__toggle-btn--active': orderType === 'dine_in' }" @click="orderType = 'dine_in'">Dine In</button>
          <button class="pos__toggle-btn" :class="{ 'pos__toggle-btn--active': orderType === 'takeaway' }" @click="orderType = 'takeaway'">Takeaway</button>
        </div>
      </div>

      <!-- Table select -->
      <div v-if="orderType === 'dine_in'" class="pos__table-select">
        <select v-model="selectedTable" class="pos__select">
          <option value="">Select table...</option>
          <option v-for="t in tables" :key="t.id" :value="t.id">{{ t.name || 'Table '+t.id }}</option>
        </select>
      </div>

      <!-- Items -->
      <div class="pos__items">
        <div v-if="cart.length === 0" class="pos__empty">Add items from the menu</div>
        <div v-for="(c, i) in cart" :key="i" class="pos__cart-item">
          <div class="pos__cart-info">
            <div class="pos__cart-name">{{ c.name }}</div>
            <div class="pos__cart-price">${{ (c.price * c.quantity + (c.upcharge || 0) * c.quantity).toFixed(2) }}</div>
          </div>
          <div class="pos__cart-controls">
            <button class="pos__qty-btn" @click="c.quantity = Math.max(1, c.quantity - 1)">−</button>
            <span class="pos__qty">{{ c.quantity }}</span>
            <button class="pos__qty-btn" @click="c.quantity++">+</button>
            <button class="pos__cart-remove" @click="cart.splice(i, 1)">×</button>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="pos__summary">
        <div class="pos__summary-row"><span>Subtotal</span><span>${{ subtotal.toFixed(2) }}</span></div>
        <div class="pos__summary-row"><span>Tax (10%)</span><span>${{ tax.toFixed(2) }}</span></div>
        <div class="pos__summary-row pos__summary-total"><span>Total</span><span>${{ total.toFixed(2) }}</span></div>
        <button class="pos__checkout" :disabled="cart.length === 0 || saving" @click="performCheckout()">
          {{ saving ? 'Sending...' : 'Place Order' }}
        </button>
        <div v-if="error" class="pos__error">{{ error }}</div>
      </div>
    </div>

    <!-- Notes Modal -->
    <div v-if="modalIdx !== null" class="modal-overlay" @click.self="modalIdx = null">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">Customize {{ cart[modalIdx]?.name }}</span>
          <button class="modal-close" @click="modalIdx = null">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Special Instructions</label>
            <textarea v-model="itemNotes" class="form-textarea" rows="3" placeholder="e.g. Extra chicken, no tomatoes..."></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Upcharge ($)</label>
            <input type="number" v-model.number="itemUpcharge" class="form-input" min="0" step="0.25" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="modalIdx = null">Cancel</button>
          <button class="btn btn-primary" @click="saveNotes">Save</button>
        </div>
      </div>
    </div>

    <!-- Unavailable Items Modal -->
    <div v-if="unavailableItems.length > 0" class="modal-overlay" @click.self="unavailableItems = []">
      <div class="modal-card modal-card--unavailable">
        <div class="modal-header">
          <span class="modal-title">Some Ingredients Unavailable</span>
          <button class="modal-close" @click="unavailableItems = []">×</button>
        </div>
        <div class="modal-body">
          <p class="unavailable-notice">Uncheck ingredients you don't want, then place your order:</p>
          <div v-for="item in unavailableItems" :key="item.item_name" class="unavailable-item">
            <div class="unavailable-item-header">
              <span class="unavailable-item-name">{{ item.item_name }}</span>
              <button class="btn-remove" @click="removeUnavailableItem(item.item_name)">Remove Item</button>
            </div>
            <div v-for="(ing, idx) in item.missing_ingredients" :key="idx" class="unavailable-ingredient">
              <label class="ingredient-checkbox">
                <input type="checkbox" :checked="!ing.unchecked" @change="toggleIngredient(item.item_name, ing, $event)">
                <span class="ingredient-name">{{ ing.ingredient }}</span>
              </label>
              <span class="unavailable-stock">Need: {{ ing.required }} | Available: {{ ing.available }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="unavailableItems = []">Cancel</button>
          <button class="btn btn-primary" @click="placeOrderWithoutIngredients">Place Order Without These</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/authStore'
import { ingredientCost, cartSubtotal } from '../services/pricingService'
import { ingredientCalories } from '../services/calorieService'
import { useToast } from '../composables/useToast'

const auth = useAuthStore()
const toast = useToast()
const rid = computed(() => auth.user?.restaurant_id)
const menuItems = ref([])
const cart = ref([])
const tables = ref([])
const selectedTable = ref('')
const orderType = ref('dine_in')
const saving = ref(false)
const error = ref('')
const activeCat = ref('All')
const modalIdx = ref(null)
const itemNotes = ref('')
const itemUpcharge = ref(0)
const unavailableItems = ref([])

const categories = computed(() => {
  const cats = [...new Set(menuItems.value.map(i => i.category || 'Other'))]
  return ['All', ...cats]
})
const filteredItems = computed(() => activeCat.value === 'All' ? menuItems.value : menuItems.value.filter(i => (i.category || 'Other') === activeCat.value))
const subtotal = computed(() => cartSubtotal(cart.value))
const tax = computed(() => subtotal.value * 0.10)
const total = computed(() => subtotal.value + tax.value)

function addToCart(item) {
  const ex = cart.value.find(c => c.menu_item_id === item.id && !c.notes)
  if (ex) { ex.quantity++ } else {
    cart.value.push({ menu_item_id: item.id, name: item.name, price: Number(item.price), upcharge: 0, quantity: 1, notes: '' })
  }
}
function openModal(i) { modalIdx.value = i; itemNotes.value = cart.value[i].notes||''; itemUpcharge.value = cart.value[i].upcharge||0 }
function saveNotes() { if(modalIdx.value!==null){cart.value[modalIdx.value].notes=itemNotes.value;cart.value[modalIdx.value].upcharge=itemUpcharge.value} modalIdx.value=null }

function removeUnavailableItem(itemName) {
  cart.value = cart.value.filter(c => c.name !== itemName)
  unavailableItems.value = unavailableItems.value.filter(i => i.item_name !== itemName)
  if (unavailableItems.value.length === 0) {
    toast.success('Removed unavailable items. You can now place the order.')
  }
}

function toggleIngredient(itemName, ingredient, event) {
  ingredient.unchecked = !event.target.checked
}

function placeOrderWithoutIngredients() {
  const removedIngredients = {}
  for (const item of unavailableItems.value) {
    const itemRemoved = item.missing_ingredients.filter(i => i.unchecked).map(i => i.ingredient)
    if (itemRemoved.length > 0) {
      removedIngredients[item.item_name] = itemRemoved
    }
  }
  unavailableItems.value = []
  performCheckout(removedIngredients)
}

function clearUnavailable() {
  const names = unavailableItems.value.map(i => i.item_name)
  cart.value = cart.value.filter(c => !names.includes(c.name))
  unavailableItems.value = []
}

async function performCheckout(removedIngredients = {}) {
  if (!cart.value.length || !rid.value) return
  saving.value = true; error.value = ''
  try {
    await api.post('/checkout/process', {
      table_id: selectedTable.value || null,
      cart: cart.value.map(c => ({
        menu_item_id: c.menu_item_id,
        name: c.name,
        price: c.price,
        quantity: c.quantity,
        notes: c.notes || null,
        removed_ingredients: removedIngredients[c.name] || [],
      })),
      subtotal: subtotal.value,
      tax: tax.value,
      total: total.value,
    })
    toast.success('Order placed successfully')
    cart.value = []; selectedTable.value = ''
  } catch(e) { 
    console.log('Checkout error:', e.response?.data)
    const data = e.response?.data
    if (e.response?.status === 422 && data?.errors?.items) {
      unavailableItems.value = data.errors.items.map(item => ({
        ...item,
        missing_ingredients: item.missing_ingredients.map(ing => ({
          ...ing,
          unchecked: false
        }))
      }))
      error.value = ''
    } else {
      error.value = data?.message || 'Checkout failed'
      toast.error(error.value)
    }
  }
  saving.value = false
}

onMounted(async () => {
  if (!rid.value) return
  try {
    const [menuRes, tableRes] = await Promise.all([
      api.get('/menu_items', { params: { restaurant_id: rid.value, per_page: 200 } }),
      api.get('/tables', { params: { restaurant_id: rid.value } })
    ])
    const menuRaw = menuRes.data?.data
    menuItems.value = Array.isArray(menuRaw) ? menuRaw : Array.isArray(menuRaw?.data) ? menuRaw.data : []
    const tableRaw = tableRes.data?.data
    tables.value = Array.isArray(tableRaw) ? tableRaw : Array.isArray(tableRaw?.data) ? tableRaw.data : []
  } catch(e) {
    console.error('Failed to load POS data', e)
  }
})
</script>

<style scoped>
.pos{display:flex;height:calc(100vh - 4rem);gap:0;background:#09090B}

.pos__menu{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:1.5rem}

.pos__cats{display:flex;gap:0.5rem;overflow-x:auto;padding-bottom:1rem;margin-bottom:1rem;flex-shrink:0}
.pos__cat{padding:0.5rem 1rem;border-radius:9999px;background:#1C1C1F;color:#A1A1AA;font-size:0.875rem;border:1px solid transparent;cursor:pointer;transition:all 150ms ease;white-space:nowrap;font-weight:500}
.pos__cat:hover{background:#27272A;color:#FAFAFA}
.pos__cat--active{background:rgba(249,115,22,0.12);color:#FB923C;border-color:rgba(249,115,22,0.3)}

.pos__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:0.75rem;overflow-y:auto;flex:1}
.pos__item{background:#111113;border:1px solid #27272A;border-radius:0.875rem;padding:1rem;cursor:pointer;transition:all 150ms ease;position:relative;display:flex;flex-direction:column}
.pos__item:hover{border-color:rgba(249,115,22,0.3);transform:translateY(-2px);box-shadow:0 4px 20px rgba(249,115,22,0.10)}
.pos__item-name{color:#FAFAFA;font-weight:600;font-size:0.9rem}
.pos__item-price{color:#FB923C;font-weight:700;font-size:1rem;margin-top:0.25rem}
.pos__item-desc{color:#71717A;font-size:0.75rem;margin-top:0.25rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1}
.pos__item-add{position:absolute;top:0.75rem;right:0.75rem;width:28px;height:28px;border-radius:50%;background:#F97316;color:white;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;transition:all 150ms ease}
.pos__item-add:hover{background:#FB923C;transform:scale(1.1)}

.pos__order{width:380px;background:#111113;border-left:1px solid #27272A;display:flex;flex-direction:column;flex-shrink:0}
.pos__order-head{padding:1.25rem 1.5rem;border-bottom:1px solid #27272A;display:flex;align-items:center;justify-content:space-between}
.pos__order-title{font-weight:700;color:#FAFAFA;font-size:1rem}

.pos__toggle{display:flex;background:#1C1C1F;border-radius:9999px;padding:3px}
.pos__toggle-btn{padding:0.375rem 0.875rem;border-radius:9999px;font-size:0.8rem;font-weight:600;color:#A1A1AA;transition:all 150ms ease}
.pos__toggle-btn--active{background:#F97316;color:white}

.pos__table-select{padding:0.75rem 1.5rem;border-bottom:1px solid #27272A}
.pos__select{width:100%;padding:0.5rem 0.75rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.5rem;color:#FAFAFA;font-size:0.875rem}

.pos__items{flex:1;overflow-y:auto;padding:0 1.5rem}
.pos__empty{text-align:center;color:#52525B;padding:2rem;font-size:0.9rem}
.pos__cart-item{padding:0.75rem 0;border-bottom:1px solid #1C1C1F}
.pos__cart-info{display:flex;justify-content:space-between;margin-bottom:0.375rem}
.pos__cart-name{color:#FAFAFA;font-size:0.875rem;font-weight:500}
.pos__cart-price{color:#FB923C;font-weight:600;font-size:0.875rem}
.pos__cart-controls{display:flex;align-items:center;gap:0.5rem}
.pos__qty-btn{width:24px;height:24px;border-radius:50%;background:#27272A;color:#FAFAFA;display:flex;align-items:center;justify-content:center;font-size:0.875rem;transition:all 150ms ease}
.pos__qty-btn:hover{background:#F97316;color:white}
.pos__qty{font-weight:700;min-width:1.5rem;text-align:center;color:#FAFAFA;font-size:0.875rem}
.pos__cart-remove{margin-left:auto;color:#52525B;font-size:1rem;transition:color 150ms ease}
.pos__cart-remove:hover{color:#F43F5E}

.pos__summary{padding:1.25rem 1.5rem;border-top:1px solid #27272A}
.pos__summary-row{display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;color:#A1A1AA}
.pos__summary-total{font-weight:800;font-size:1.1rem;color:#FAFAFA;border-top:1px solid #27272A;padding-top:0.75rem;margin-top:0.5rem}
.pos__checkout{width:100%;padding:0.875rem;background:#F97316;color:white;font-weight:800;font-size:1rem;border-radius:0.75rem;margin-top:0.75rem;transition:all 150ms ease;box-shadow:0 0 20px rgba(249,115,22,0.3)}
.pos__checkout:hover:not(:disabled){background:#FB923C;box-shadow:0 0 30px rgba(249,115,22,0.5)}
.pos__checkout:disabled{opacity:0.5;cursor:not-allowed}
.pos__error{color:#F43F5E;font-size:0.8rem;text-align:center;margin-top:0.5rem}

.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem}
.modal-card{background:#18181B;border:1px solid #27272A;border-radius:1.5rem;width:100%;max-width:480px;overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #27272A}
.modal-title{font-weight:700;color:#FAFAFA;font-size:1.1rem}
.modal-close{color:#52525B;font-size:1.25rem;transition:color 150ms ease}
.modal-close:hover{color:#FAFAFA}
.modal-body{padding:1.5rem}
.modal-footer{display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid #27272A}

.form-group{margin-bottom:1rem}
.form-label{display:block;font-size:0.8rem;font-weight:600;color:#A1A1AA;margin-bottom:0.375rem}
.form-input,.form-textarea,.form-select{width:100%;padding:0.625rem 0.875rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.625rem;color:#FAFAFA;font-size:0.875rem;transition:border-color 150ms ease}
.form-input:focus,.form-textarea:focus,.form-select:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}
.form-textarea{resize:vertical;min-height:80px}

.btn{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 150ms ease}
.btn-primary{background:#F97316;color:white}
.btn-primary:hover{background:#FB923C}
.btn-secondary{background:#1C1C1F;color:#A1A1AA;border:1px solid #27272A}
.btn-secondary:hover{background:#27272A;color:#FAFAFA}

.modal-card--unavailable{max-width:520px}
.unavailable-notice{color:#F43F5E;font-size:0.875rem;margin-bottom:1rem;font-weight:500}
.unavailable-item{background:#1C1C1F;border:1px solid #27272A;border-radius:0.75rem;padding:1rem;margin-bottom:0.75rem}
.unavailable-item-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem}
.unavailable-item-name{color:#FAFAFA;font-weight:600;font-size:0.95rem}
.btn-remove{background:#EF4444;color:white;padding:0.375rem 0.75rem;border-radius:0.5rem;font-size:0.75rem;font-weight:600;cursor:pointer;border:none;transition:background 150ms ease}
.btn-remove:hover{background:#DC2626}
.unavailable-ingredient{display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;color:#A1A1AA;font-size:0.8rem;border-top:1px solid #27272A}
.unavailable-stock{color:#F43F5E;font-weight:500;font-size:0.75rem}
.ingredient-checkbox{display:flex;align-items:center;gap:0.5rem;cursor:pointer}
.ingredient-checkbox input[type="checkbox"]{width:16px;height:16px;accent-color:#F97316;cursor:pointer}
.ingredient-name{color:#FAFAFA;font-weight:500}

@media(max-width:768px){.pos{flex-direction:column;height:auto}.pos__order{width:100%;border-left:none;border-top:1px solid #27272A}.pos__grid{grid-template-columns:repeat(2,1fr)}}
</style>

<template>
  <div class="overlay" @click.self="$emit('close')">
    <div class="card">

      <div class="card-head">
        <span>{{ record ? 'Edit' : 'Add' }} Menu Item</span>
        <button @click="$emit('close')">×</button>
      </div>

      <div class="card-body">

        <div class="field">
          <label>Item Name *</label>
          <input v-model="name" type="text" placeholder="e.g. Grilled Chicken Bowl"/>
        </div>

        <div class="field">
          <label>Description</label>
          <textarea v-model="description" placeholder="Describe the dish..."/>
        </div>

        <div class="field">
          <label>Category</label>
          <select v-model="categoryId">
            <option value="">Select category</option>
            <option v-for="c in safeCategories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </div>

        <div class="field">
          <label>Available</label>
          <div class="toggle-row">
            <button 
              class="toggle" 
              :class="{ on: isAvailable }" 
              @click="isAvailable = !isAvailable"
              type="button"
            >
              <span class="knob"/>
            </button>
            <span>{{ isAvailable ? 'Yes' : 'No' }}</span>
          </div>
        </div>

        <div class="divider"/>

        <div class="section-head">
          <span class="section-label">RECIPE & INGREDIENTS</span>
          <button class="add-btn" @click="addRow" type="button">
            + Add Ingredient
          </button>
        </div>

        <div v-if="rows.length === 0" class="empty">
          No ingredients yet. Click "+ Add Ingredient" to start.
        </div>

        <div v-for="(row, i) in rows" :key="i" class="row-card">
          <div class="row-top">
            <select v-model="rows[i].ingredient_id" @change="onSelect(i)">
              <option value="">Select ingredient</option>
              <option 
                v-for="ing in safeIngredients" 
                :key="ing.id" 
                :value="ing.id"
              >
                {{ ing.name }} ({{ ing.unit }})
              </option>
            </select>
            <button class="rm-btn" @click="removeRow(i)" type="button">×</button>
          </div>
          <div class="row-fields">
            <div class="rf">
              <label>Qty</label>
              <input v-model.number="rows[i].qty" type="number" min="0" step="0.01" @input="recalc"/>
            </div>
            <div class="rf">
              <label>Unit</label>
              <input :value="rows[i].unit" readonly/>
            </div>
            <div class="rf">
              <label>Cal/unit</label>
              <input :value="rows[i].calPerUnit" readonly/>
            </div>
            <div class="rf">
              <label>Price/unit</label>
              <input :value="rows[i].pricePerUnit" readonly/>
            </div>
            <div class="rf hi">
              <label>Calories</label>
              <input :value="(rows[i].qty * rows[i].calPerUnit).toFixed(0)" readonly/>
            </div>
            <div class="rf hi">
              <label>Price</label>
              <input :value="(rows[i].qty * rows[i].pricePerUnit).toFixed(2)" readonly/>
            </div>
          </div>
        </div>

        <div v-if="rows.length > 0" class="totals">
          <div class="total-box">
            <span class="tl">Total Calories</span>
            <span class="tv green">{{ totalCal }} kcal</span>
          </div>
          <div class="total-box">
            <span class="tl">Total Price</span>
            <span class="tv orange">${{ totalPrice }}</span>
          </div>
        </div>

        <div v-if="err" class="err">{{ err }}</div>
      </div>

      <div class="card-foot">
        <button class="btn-cancel" @click="$emit('close')">Cancel</button>
        <button class="btn-save" @click="submit" :disabled="saving">
          {{ saving ? 'Saving...' : record ? 'Save Changes' : 'Add Menu Item' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'

const props = defineProps({
  restaurantId: [Number, String],
  record: Object
})
const emit = defineEmits(['saved', 'close'])
const toast = useToast()

// Form fields as separate refs — no nested reactivity issues
const name = ref('')
const description = ref('')
const categoryId = ref('')
const isAvailable = ref(true)
const rows = ref([])
const err = ref('')
const saving = ref(false)

// Data
const categories = ref([])
const ingredients = ref([])

// Safe computed arrays — never null
const safeCategories = computed(() => 
  (categories.value || []).filter(c => c && c.id)
)
const safeIngredients = computed(() => 
  (ingredients.value || []).filter(i => i && i.id)
)

const totalCal = computed(() =>
  rows.value.reduce((s, r) => s + (Number(r.qty) || 0) * (Number(r.calPerUnit) || 0), 0).toFixed(0)
)
const totalPrice = computed(() =>
  rows.value.reduce((s, r) => s + (Number(r.qty) || 0) * (Number(r.pricePerUnit) || 0), 0).toFixed(2)
)

onMounted(async () => {
  try {
    const [cr, ir] = await Promise.all([
      api.get('/menu_categories', { params: { per_page: 100 } }),
      api.get('/ingredients', { params: { per_page: 100 } })
    ])
    const catsRaw = cr.data?.data
    const ingsRaw = ir.data?.data
    const rawCats = Array.isArray(catsRaw) ? catsRaw : Array.isArray(catsRaw?.data) ? catsRaw.data : []
    const rawIngs = Array.isArray(ingsRaw) ? ingsRaw : Array.isArray(ingsRaw?.data) ? ingsRaw.data : []
    categories.value = rawCats.filter(c => c != null)
    ingredients.value = rawIngs.filter(i => i != null)
  } catch(e) {
    console.error('Failed to load data', e)
  }

  // Populate form if editing
  if (props.record) {
    name.value = props.record.name || ''
    description.value = props.record.description || ''
    categoryId.value = props.record.menu_category_id || ''
    isAvailable.value = props.record.is_available ?? true
    if (Array.isArray(props.record.recipe_ingredients)) {
      rows.value = props.record.recipe_ingredients
        .filter(r => r != null)
        .map(r => ({
          ingredient_id: r.ingredient_id || '',
          qty: Number(r.quantity_required) || 0,
          unit: r.ingredient?.unit || '',
          calPerUnit: Number(r.ingredient?.calories_per_unit) || 0,
          pricePerUnit: Number(r.ingredient?.cost_per_unit) || 0
        }))
    }
  }
})

function addRow() {
  rows.value = [
    ...rows.value,
    { ingredient_id: '', qty: 0, unit: '', calPerUnit: 0, pricePerUnit: 0 }
  ]
}

function removeRow(i) {
  rows.value = rows.value.filter((_, idx) => idx !== i)
}

function onSelect(i) {
  const id = rows.value[i].ingredient_id
  const ing = safeIngredients.value.find(x => String(x.id) === String(id))
  if (ing) {
    rows.value = rows.value.map((r, idx) => idx === i ? {
      ...r,
      unit: ing.unit || '',
      calPerUnit: Number(ing.calories_per_unit) || 0,
      pricePerUnit: Number(ing.cost_per_unit) || 0
    } : r)
  }
}

function recalc() {
  // totals are computed — no action needed
}

async function submit() {
  if (!name.value.trim()) { err.value = 'Name is required'; return }
  err.value = ''
  saving.value = true
  try {
    const payload = {
      name: name.value.trim(),
      description: description.value,
      menu_category_id: categoryId.value || null,
      is_available: isAvailable.value,
      restaurant_id: props.restaurantId,
      price: Number(totalPrice.value),
      calories: Number(totalCal.value),
      recipe_ingredients: rows.value
        .filter(r => r.ingredient_id)
        .map(r => ({
          ingredient_id: r.ingredient_id,
          quantity_required: r.qty
        }))
    }
    if (props.record?.id) {
      await api.put(`/menu_items/${props.record.id}`, payload)
      toast.success('Menu item updated successfully')
    } else {
      await api.post('/menu_items', payload)
      toast.success('Menu item created successfully')
    }
    emit('saved')
  } catch(e) {
    err.value = e?.response?.data?.message || 'Failed to save'
    toast.error(err.value)
  }
  saving.value = false
}
</script>

<style scoped>
.overlay{position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem}
.card{background:#18181B;border:1px solid #27272A;border-radius:1.5rem;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.8)}
.card-head{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #27272A;position:sticky;top:0;background:#18181B;z-index:2;font-weight:700;color:#FAFAFA;font-size:1.1rem}
.card-head button{background:none;border:none;color:#71717A;font-size:1.5rem;cursor:pointer;line-height:1}
.card-head button:hover{color:#FAFAFA}
.card-body{padding:1.5rem}
.card-foot{display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid #27272A;position:sticky;bottom:0;background:#18181B;z-index:2}

.field{margin-bottom:1rem}
.field label{display:block;font-size:0.78rem;font-weight:600;color:#A1A1AA;margin-bottom:0.375rem}
.field input,.field select,.field textarea{width:100%;padding:0.6rem 0.875rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.625rem;color:#FAFAFA;font-size:0.875rem;font-family:inherit;outline:none;transition:border-color 150ms,box-shadow 150ms}
.field input:focus,.field select:focus,.field textarea:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}
.field textarea{resize:vertical;min-height:70px}

.toggle-row{display:flex;align-items:center;gap:0.75rem;color:#A1A1AA;font-size:0.875rem}
.toggle{position:relative;width:44px;height:24px;background:#27272A;border:none;border-radius:9999px;cursor:pointer;transition:background 200ms;padding:0;flex-shrink:0}
.toggle.on{background:#F97316}
.knob{position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;transition:transform 200ms;box-shadow:0 1px 3px rgba(0,0,0,0.3)}
.toggle.on .knob{transform:translateX(20px)}

.divider{height:1px;background:#27272A;margin:1.25rem 0}
.section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}
.section-label{font-size:0.7rem;font-weight:700;color:#52525B;text-transform:uppercase;letter-spacing:0.1em}
.add-btn{background:rgba(249,115,22,0.12);color:#FB923C;border:1px solid rgba(249,115,22,0.25);border-radius:0.5rem;padding:0.4rem 0.875rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 150ms;font-family:inherit}
.add-btn:hover{background:rgba(249,115,22,0.22)}

.empty{text-align:center;padding:1.5rem;color:#52525B;font-size:0.875rem;border:1px dashed #27272A;border-radius:0.75rem;margin-bottom:0.75rem}

.row-card{background:#1C1C1F;border:1px solid #27272A;border-radius:0.875rem;padding:1rem;margin-bottom:0.75rem}
.row-top{display:flex;gap:0.5rem;margin-bottom:0.75rem;align-items:center}
.row-top select{flex:1;padding:0.5rem 0.75rem;background:#18181B;border:1px solid #27272A;border-radius:0.5rem;color:#FAFAFA;font-size:0.875rem;outline:none;font-family:inherit}
.row-top select:focus{border-color:#F97316}
.rm-btn{background:rgba(244,63,94,0.1);color:#F43F5E;border:1px solid rgba(244,63,94,0.2);border-radius:0.5rem;width:32px;height:32px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 150ms}
.rm-btn:hover{background:rgba(244,63,94,0.2)}

.row-fields{display:grid;grid-template-columns:repeat(6,1fr);gap:0.5rem}
.rf label{display:block;font-size:0.65rem;font-weight:700;color:#52525B;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem}
.rf input{width:100%;padding:0.4rem 0.5rem;background:#18181B;border:1px solid #27272A;border-radius:0.5rem;color:#FAFAFA;font-size:0.8rem;outline:none;font-family:inherit}
.rf input:focus{border-color:#F97316}
.rf input[readonly]{color:#71717A;cursor:not-allowed}
.rf.hi input[readonly]{color:#FB923C;border-color:rgba(249,115,22,0.15)}

.totals{display:flex;gap:1rem;margin-top:1rem;padding:1rem;background:#111113;border:1px solid rgba(249,115,22,0.15);border-radius:0.875rem}
.total-box{flex:1;text-align:center}
.tl{display:block;font-size:0.7rem;color:#71717A;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem}
.tv{font-size:1.25rem;font-weight:800}
.green{color:#10B981}
.orange{color:#FB923C}

.err{color:#F43F5E;font-size:0.8rem;margin-top:0.75rem;padding:0.75rem;background:rgba(244,63,94,0.08);border-radius:0.5rem;border:1px solid rgba(244,63,94,0.2)}

.btn-cancel{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:600;cursor:pointer;background:#1C1C1F;color:#A1A1AA;border:1px solid #27272A;transition:all 150ms;font-family:inherit}
.btn-cancel:hover{background:#27272A;color:#FAFAFA}
.btn-save{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:700;cursor:pointer;background:#F97316;color:white;border:none;transition:all 150ms;font-family:inherit}
.btn-save:hover{background:#FB923C}
.btn-save:disabled{opacity:0.6;cursor:not-allowed}
</style>

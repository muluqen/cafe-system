<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <div class="modal-header">
        <span class="modal-title">{{ record ? 'Edit' : 'Add' }} {{ singularLabel }}</span>
        <button class="modal-close" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <div v-for="field in entityConfig.fields" :key="field" class="form-group">
          <label class="form-label">{{ fmtLabel(field) }}<span v-if="isRequired(field)" style="color:#F43F5E"> *</span></label>
          <div v-if="isBool(field)" class="toggle-wrap">
            <button class="toggle" :class="{ 'toggle--on': form[field] }" @click="form[field] = !form[field]" type="button"><span class="toggle__knob"/></button>
            <span class="toggle-text">{{ form[field] ? 'Yes' : 'No' }}</span>
          </div>
          <select v-else-if="getOpts(field)" v-model="form[field]" class="form-select">
            <option value="">Select {{ fmtLabel(field) }}</option>
            <option v-for="o in getOpts(field)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
          <textarea v-else-if="isTextarea(field)" v-model="form[field]" class="form-textarea" :placeholder="`Enter ${fmtLabel(field).toLowerCase()}...`"/>
          <input v-else-if="isNum(field)" v-model.number="form[field]" type="number" step="0.01" class="form-input" :placeholder="`Enter ${fmtLabel(field).toLowerCase()}...`"/>
          <input v-else-if="field === 'password'" v-model="form[field]" type="password" class="form-input" placeholder="Enter password..."/>
          <input v-else v-model="form[field]" type="text" class="form-input" :placeholder="`Enter ${fmtLabel(field).toLowerCase()}...`"/>
        </div>
        <div v-if="error" class="form-error">{{ error }}</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
        <button class="btn btn-primary" @click="submit" :disabled="saving">{{ saving ? 'Saving...' : record ? 'Save Changes' : 'Add Record' }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from '../../composables/useToast'
import api from '../../services/api'

const props = defineProps({ entityKey: String, entityConfig: Object, restaurantId: [Number,String], record: Object })
const emit = defineEmits(['saved','close'])
const toast = useToast()
const saving = ref(false); const error = ref(''); const form = ref({})
const endpointMap = { tables: 'tables', team: 'users' }
const endpoint = computed(() => endpointMap[props.entityKey] || props.entityKey)
const singularLabel = computed(() => { const l = props.entityConfig?.label||''; return l.endsWith('s')?l.slice(0,-1):l })

watch(() => props.record, r => {
  if (r) { form.value = { ...r } }
  else { const b = {}; props.entityConfig?.fields?.forEach(f => b[f]=''); form.value = b }
}, { immediate: true })

function fmtLabel(f) { return f.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) }
function isRequired(f) { return ['name','email','password','price'].includes(f) }
function isBool(f) { return ['is_active','is_available','can_read','can_write'].includes(f) }
function isTextarea(f) { return ['description','notes','address','note'].includes(f) }
function isNum(f) { return ['price','current_stock','reorder_level','cost_per_unit','quantity','quantity_required','capacity','preparation_time_minutes','amount','subtotal','tax','total','display_order','guest_count'].includes(f) }
function getOpts(f) {
  const o = {
    staff_role: [{value:'manager',label:'Manager'},{value:'floor_manager',label:'Floor Manager'},{value:'cashier',label:'Cashier'},{value:'server',label:'Server'},{value:'kitchen',label:'Kitchen'},{value:'barista',label:'Barista'},{value:'host',label:'Host'},{value:'inventory',label:'Inventory'}],
    status: [{value:'active',label:'Active'},{value:'inactive',label:'Inactive'},{value:'pending',label:'Pending'}],
    unit: [{value:'g',label:'Grams'},{value:'kg',label:'Kilograms'},{value:'ml',label:'Milliliters'},{value:'l',label:'Liters'},{value:'pcs',label:'Pieces'},{value:'cups',label:'Cups'}],
    type: [{value:'addition',label:'Addition'},{value:'deduction',label:'Deduction'},{value:'adjustment',label:'Adjustment'}],
    method: [{value:'cash',label:'Cash'},{value:'card',label:'Card'},{value:'mobile',label:'Mobile'}],
  }
  return o[f] || null
}

async function submit() {
  error.value = ''; saving.value = true
  try {
    const p = { ...form.value, restaurant_id: props.restaurantId }
    if (props.record?.id) { await api.put(`/${endpoint.value}/${props.record.id}`, p); toast.success(`${singularLabel.value} updated successfully`) }
    else { await api.post(`/${endpoint.value}`, p); toast.success(`${singularLabel.value} created successfully`) }
    emit('saved')
  } catch(e) { error.value = e?.response?.data?.message || 'Failed'; toast.error(error.value) }
  saving.value = false
}
</script>

<style scoped>
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem}
.modal-card{background:#18181B;border:1px solid #27272A;border-radius:1.5rem;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.7)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #27272A}
.modal-title{font-weight:700;color:#FAFAFA;font-size:1.1rem}
.modal-close{color:#52525B;font-size:1.25rem;transition:color 150ms ease;background:none;border:none;cursor:pointer}
.modal-close:hover{color:#FAFAFA}
.modal-body{padding:1.5rem}
.modal-footer{display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid #27272A}

.form-group{margin-bottom:1rem}
.form-label{display:block;font-size:0.8rem;font-weight:600;color:#A1A1AA;margin-bottom:0.375rem}
.form-input,.form-select,.form-textarea{width:100%;padding:0.625rem 0.875rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.625rem;color:#FAFAFA;font-size:0.875rem;transition:border-color 150ms ease,box-shadow 150ms ease}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}
.form-textarea{resize:vertical;min-height:80px}
.form-error{color:#F43F5E;font-size:0.8rem;margin-top:0.5rem}

.toggle-wrap{display:flex;align-items:center;gap:0.75rem}
.toggle{position:relative;width:44px;height:24px;background:#27272A;border:none;border-radius:9999px;cursor:pointer;transition:background 200ms ease;padding:0}
.toggle--on{background:#F97316}
.toggle__knob{position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;transition:transform 200ms ease;box-shadow:0 1px 3px rgba(0,0,0,0.3)}
.toggle--on .toggle__knob{transform:translateX(20px)}
.toggle-text{font-size:0.875rem;color:#A1A1AA}

.btn{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 150ms ease;border:none}
.btn-primary{background:#F97316;color:white}
.btn-primary:hover{background:#FB923C}
.btn-secondary{background:#1C1C1F;color:#A1A1AA;border:1px solid #27272A}
.btn-secondary:hover{background:#27272A;color:#FAFAFA}
</style>

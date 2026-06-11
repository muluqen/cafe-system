<template>
  <div class="table-container">
    <div class="table-toolbar">
      <input v-model="search" class="table-search" :placeholder="`Search ${entityConfig?.label?.toLowerCase()}...`" />
      <button class="btn btn-ghost" @click="fetchData">↻ Refresh</button>
    </div>

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th v-for="f in visibleFields" :key="f">{{ formatLabel(f) }}</th>
            <th v-if="canWrite" class="th-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td :colspan="visibleFields.length + 1"><div v-for="i in 5" :key="i" class="shimmer"/></td></tr>
          <tr v-else-if="!Array.isArray(filtered) || filtered.length === 0"><td :colspan="visibleFields.length + 1"><div class="empty">No records found</div></td></tr>
          <tr v-else v-for="row in (Array.isArray(filtered) ? filtered : []).filter(i => i != null)" :key="row?.id">
            <td v-for="f in visibleFields" :key="f">
              <span v-if="f === 'current_stock'" :class="stockClass(row)">{{ row?.[f] }} {{ row?.unit }}</span>
              <span v-else-if="f === 'is_active' || f === 'is_available' || f === 'status'" class="badge" :class="badgeClass(row?.[f])">{{ fmtBool(row?.[f]) }}</span>
              <span v-else-if="['price','amount','unit_price'].includes(f)">${{ Number(row?.[f]||0).toFixed(2) }}</span>
              <span v-else>{{ row?.[f] ?? 'N/A' }}</span>
            </td>
            <td v-if="canWrite" class="td-actions">
              <button class="action-btn action-btn--edit" @click="$emit('edit', row)">Edit</button>
              <button class="action-btn action-btn--delete" @click="confirmDelete(row)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal-card" style="max-width:400px">
        <div class="modal-header"><span class="modal-title">Confirm Delete</span><button class="modal-close" @click="deleteTarget = null">×</button></div>
        <div class="modal-body"><p style="color:#A1A1AA">Are you sure? This cannot be undone.</p></div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="deleteTarget = null">Cancel</button>
          <button class="btn btn-danger" @click="doDelete">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from '../../composables/useToast'
import api from '../../services/api'

const props = defineProps({ entityKey: String, entityConfig: Object, restaurantId: [Number,String], canWrite: Boolean })
const emit = defineEmits(['edit','deleted'])
const toast = useToast()
const rows = ref([]); const loading = ref(false); const search = ref(''); const deleteTarget = ref(null)
const endpointMap = { tables: 'tables', team: 'users' }
const endpoint = computed(() => endpointMap[props.entityKey] || props.entityKey)
const visibleFields = computed(() => props.entityConfig?.fields?.slice(0,6) || [])
const filtered = computed(() => {
  const raw = rows.value ?? []
  const list = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : []
  if (!search.value) return list
  const q = search.value.toLowerCase()
  return list.filter(item => {
    if (!item) return false
    return Object.values(item).some(v => String(v ?? '').toLowerCase().includes(q))
  })
})

async function fetchData() {
  if (!props.restaurantId) return
  loading.value = true
  try {
    const res = await api.get(`/${endpoint.value}`, { params: { restaurant_id: props.restaurantId, per_page: 100 } })
    const raw = res.data?.data || res.data
    const items = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : []
    rows.value = items
  }
  catch(e) {
    console.error('Failed to load', endpoint.value, e)
    rows.value = []
    toast.error(e?.response?.data?.message || 'Failed to load data')
  }
  loading.value = false
}
function confirmDelete(row) { deleteTarget.value = row }
async function doDelete() {
  if (!deleteTarget.value?.id) return
  const id = deleteTarget.value.id
  try {
    await api.delete(`/${endpoint.value}/${id}`)
    rows.value = rows.value.filter(r => r?.id !== id)
    deleteTarget.value = null
    toast.success(`${props.entityConfig?.label?.replace(/s$/,'') || 'Record'} deleted successfully`)
  } catch(e) {
    toast.error(e?.response?.data?.message || 'Failed to delete')
  }
  emit('deleted')
}
function formatLabel(f) { return f.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) }
function fmtBool(v) { if(v===true||v===1) return 'Active'; if(v===false||v===0) return 'Inactive'; return v }
function badgeClass(v) { if(v===true||v===1||v==='active'||v==='paid') return 'badge--success'; if(v===false||v===0||v==='inactive'||v==='cancelled') return 'badge--danger'; if(v==='pending') return 'badge--warning'; return 'badge--neutral' }
function stockClass(r) { if(!r.reorder_level) return 'stock--ok'; if(r.current_stock<=0) return 'stock--low'; if(r.current_stock<=r.reorder_level) return 'stock--warn'; return 'stock--ok' }

defineExpose({ fetchData })
onMounted(fetchData)
watch(() => props.entityKey, fetchData)
</script>

<style scoped>
.table-toolbar{display:flex;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap}
.table-search{flex:1;max-width:360px;padding:0.6rem 1rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.625rem;color:#FAFAFA;font-size:0.875rem;transition:border-color 150ms ease}
.table-search::placeholder{color:#52525B}
.table-search:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}

.table-wrapper{background:#111113;border:1px solid #27272A;border-radius:1rem;overflow:hidden}
.table{width:100%;border-collapse:collapse}
.table th{background:#18181B;color:#52525B;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;padding:0.75rem 1rem;text-align:left;border-bottom:1px solid #27272A}
.table td{padding:0.75rem 1rem;border-bottom:1px solid #1C1C1F;color:#FAFAFA;font-size:0.875rem}
.table tr:hover td{background:#18181B}
.th-actions{text-align:right}
.td-actions{display:flex;gap:0.5rem;justify-content:flex-end}

.badge{display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600}
.badge--success{background:rgba(16,185,129,0.12);color:#10B981}
.badge--danger{background:rgba(244,63,94,0.12);color:#F43F5E}
.badge--warning{background:rgba(245,158,11,0.12);color:#F59E0B}
.badge--neutral{background:rgba(161,161,170,0.12);color:#A1A1AA}

.stock--ok{color:#10B981;font-weight:600}
.stock--low{color:#F43F5E;font-weight:700}
.stock--warn{color:#F59E0B;font-weight:600}

.action-btn{padding:0.375rem 0.75rem;border-radius:0.375rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 150ms ease;background:transparent;border:none}
.action-btn--edit{color:#06B6D4}
.action-btn--edit:hover{background:rgba(6,182,212,0.10);color:#22D3EE}
.action-btn--delete{color:#52525B}
.action-btn--delete:hover{background:rgba(244,63,94,0.10);color:#F43F5E}

.shimmer{height:44px;background:linear-gradient(90deg,#1C1C1F 25%,#27272A 50%,#1C1C1F 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;margin:2px 0;border-radius:0.5rem}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

.empty{text-align:center;padding:2rem;color:#52525B;font-size:0.9rem}

.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem}
.modal-card{background:#18181B;border:1px solid #27272A;border-radius:1.5rem;width:100%;overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #27272A}
.modal-title{font-weight:700;color:#FAFAFA;font-size:1.1rem}
.modal-close{color:#52525B;font-size:1.25rem;transition:color 150ms ease;background:none;border:none;cursor:pointer}
.modal-close:hover{color:#FAFAFA}
.modal-body{padding:1.5rem}
.modal-footer{display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid #27272A}

.btn{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 150ms ease;border:none}
.btn-primary{background:#F97316;color:white}
.btn-primary:hover{background:#FB923C}
.btn-secondary{background:#1C1C1F;color:#A1A1AA;border:1px solid #27272A}
.btn-secondary:hover{background:#27272A;color:#FAFAFA}
.btn-danger{background:rgba(244,63,94,0.15);color:#F43F5E;border:1px solid rgba(244,63,94,0.25)}
.btn-danger:hover{background:rgba(244,63,94,0.25)}
.btn-ghost{background:transparent;color:#A1A1AA;border:1px solid #27272A}
.btn-ghost:hover{background:#1C1C1F;color:#FAFAFA}
</style>

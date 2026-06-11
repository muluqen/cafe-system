<template>
  <div class="rbac">
    <div class="rbac__tabs">
      <button class="rbac__tab" :class="{'rbac__tab--active': tab==='builtin'}" @click="tab='builtin'">Built-in Roles</button>
      <button class="rbac__tab" :class="{'rbac__tab--active': tab==='custom'}" @click="tab='custom'">Custom Roles <span v-if="customRoles.length" class="rbac__tab-count">{{ customRoles.length }}</span></button>
    </div>

    <!-- Built-in -->
    <div v-if="tab==='builtin'" class="rbac__builtin">
      <p class="rbac__info">Default permissions for each built-in role. Applies to all staff unless overridden.</p>
      <div class="rbac__role-tabs">
        <button v-for="r in builtinRoles" :key="r" class="rbac__role-tab" :class="{'rbac__role-tab--active': selectedRole===r}" @click="selectRole(r)">{{ r.replace(/_/g,' ') }}</button>
      </div>
      <div v-if="selectedRole" class="perm-matrix">
        <div class="perm-grid">
          <div class="perm-grid__head"><span>Module</span><span>Read</span><span>Write</span></div>
          <div v-for="e in allEntities" :key="e.key" class="perm-grid__row" :class="{'perm-grid__row--group': e.isGroupHeader}">
            <span class="perm-grid__name" :class="{'perm-grid__name--group': e.isGroupHeader}">{{ e.label }}</span>
            <template v-if="!e.isGroupHeader">
              <div class="perm-grid__cell">
                <button class="toggle-btn" :class="{'toggle-btn--on':getRolePerm(e.key,'read')}" @click="toggleBuiltinPerm(e.key,'read')" type="button"><span class="toggle-btn__knob"/></button>
              </div>
              <div class="perm-grid__cell">
                <button class="toggle-btn" :class="{'toggle-btn--on':getRolePerm(e.key,'write')}" @click="toggleBuiltinPerm(e.key,'write')" type="button"><span class="toggle-btn__knob"/></button>
              </div>
            </template>
          </div>
        </div>
        <div class="rbac-save-row">
          <p class="rbac-save-hint">Changes affect all staff with this role who don't have custom permissions set.</p>
          <button class="btn btn-primary" @click="saveBuiltinPerms" :disabled="savingBuiltin">{{ savingBuiltin?'Saving...':'Save Role Permissions' }}</button>
        </div>
      </div>
    </div>

    <!-- Custom -->
    <div v-if="tab==='custom'" class="rbac__custom">
      <div class="rbac__custom-toolbar">
        <p class="rbac__info">Create custom roles with specific permissions.</p>
        <button class="btn btn-primary" @click="openCreate">+ Create Custom Role</button>
      </div>
      <div v-if="!customRoles.length" class="empty">
        <div class="empty__icon">🎭</div>
        <div class="empty__title">No custom roles yet</div>
        <div class="empty__text">Create a role like "Head Chef" with tailored permissions.</div>
      </div>
      <div v-else class="custom-grid">
        <div v-for="r in customRoles" :key="r.id" class="custom-card" @click="editRole(r)">
          <div class="custom-card__name">{{ r.name }}</div>
          <div class="custom-card__base">Based on: {{ r.based_on || 'none' }}</div>
          <div class="custom-card__desc">{{ r.description || 'No description' }}</div>
          <div class="custom-card__footer">
            <span class="custom-badge">Custom Role</span>
            <button class="action-btn action-btn--delete" @click.stop="deleteRole(r)">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate=false">
      <div class="modal-card" style="max-width:720px">
        <div class="modal-header"><span class="modal-title">{{ editingRole?'Edit':'Create' }} Custom Role</span><button class="modal-close" @click="showCreate=false">×</button></div>
        <div class="modal-body">
          <div class="form-group"><label class="form-label">Role Name *</label><input v-model="rf.name" class="form-input" placeholder="e.g. Head Chef..."/></div>
          <div class="form-group"><label class="form-label">Description</label><input v-model="rf.description" class="form-input" placeholder="Brief description..."/></div>
          <div class="form-group"><label class="form-label">Based On</label><select v-model="rf.based_on" class="form-select"><option value="">Start from scratch</option><option v-for="r in builtinRoles" :key="r" :value="r">{{ r.replace(/_/g,' ') }}</option></select></div>
          <div class="form-group">
            <label class="form-label">Permissions</label>
            <div class="perm-grid" style="margin-top:0.5rem">
              <div class="perm-grid__head"><span>Module</span><span>Read</span><span>Write</span></div>
              <div v-for="e in allEntities" :key="e.key" class="perm-grid__row" :class="{'perm-grid__row--group': e.isGroupHeader}">
                <span class="perm-grid__name" :class="{'perm-grid__name--group': e.isGroupHeader}">{{ e.label }}</span>
                <template v-if="!e.isGroupHeader">
                  <div class="perm-grid__cell"><button class="toggle toggle--sm" :class="{'toggle--on':rp[e.key]?.read}" @click="toggleRP(e.key,'read')" type="button"><span class="toggle__knob"/></button></div>
                  <div class="perm-grid__cell"><button class="toggle toggle--sm" :class="{'toggle--on':rp[e.key]?.write}" @click="toggleRP(e.key,'write')" type="button"><span class="toggle__knob"/></button></div>
                </template>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showCreate=false">Cancel</button>
          <button class="btn btn-primary" @click="saveRole" :disabled="savingRole">{{ savingRole?'Saving...':'Save Role' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from '../../composables/useToast'
import api from '../../services/api'
import { entities } from '../../config/entities'

const props = defineProps({ restaurantId: [Number,String] })
const toast = useToast()
const tab = ref('builtin'); const selectedRole = ref('manager'); const customRoles = ref([])
const showCreate = ref(false); const editingRole = ref(null); const savingRole = ref(false)
const rp = ref({}); const builtinPerms = ref({}); const savingBuiltin = ref(false)
const builtinRoles = ['manager','floor_manager','cashier','server','kitchen','barista','host','inventory']
const allEntities = computed(() => {
  const flat = []
  for (const e of entities) {
    if (e.subPermissions && e.subPermissions.length) {
      flat.push({ key: '__settings_group__', label: e.label, isGroupHeader: true })
      for (const sp of e.subPermissions) {
        flat.push({ key: sp.key, label: sp.label })
      }
    } else {
      flat.push(e)
    }
  }
  return flat
})
const rf = ref({name:'',description:'',based_on:''})

async function selectRole(role) {
  selectedRole.value = role
  builtinPerms.value = {}
  
  try {
    console.log('Fetching defaults for role:', role)
    const res = await api.get(`/rbac/defaults/${role}`)
    console.log('Raw response:', res)
    console.log('Response data:', res.data)
    
    // API returns { data: { role: "...", permissions: [...] } }
    const responseData = res.data?.data || res.data || {}
    console.log('Response data object:', responseData)
    
    // Extract permissions array - could be in .permissions or be the array itself
    const rawData = responseData.permissions || responseData
    console.log('Raw data:', rawData)
    
    const permsArray = Array.isArray(rawData) ? rawData : 
                       Object.values(rawData)
    console.log('Perms array:', permsArray)
    
    const map = {}
    permsArray.forEach(p => {
      if (p.entity_key) {
        map[p.entity_key] = { 
          read: Boolean(p.can_read), 
          write: Boolean(p.can_write) 
        }
      }
    })
    
    console.log('Final map:', map)
    builtinPerms.value = map
    
  } catch(e) {
    console.error('Error fetching defaults:', e)
    console.error('Error response:', e?.response?.data)
    console.error('Error status:', e?.response?.status)
  }
}

function getRolePerm(entityKey, action) {
  const perm = builtinPerms.value[entityKey]
  if (!perm) return false
  return action === 'read' ? Boolean(perm.read) : Boolean(perm.write)
}

function toggleBuiltinPerm(entityKey, action) {
  if (!builtinPerms.value[entityKey]) {
    builtinPerms.value[entityKey] = { read: false, write: false }
  }
  builtinPerms.value[entityKey][action] = !builtinPerms.value[entityKey][action]
  if (action === 'write' && builtinPerms.value[entityKey].write) {
    builtinPerms.value[entityKey].read = true
  }
  if (action === 'read' && !builtinPerms.value[entityKey].read) {
    builtinPerms.value[entityKey].write = false
  }
}

async function saveBuiltinPerms() {
  savingBuiltin.value = true
  try {
    const permissions = Object.entries(builtinPerms.value)
      .map(([key, val]) => ({
        entity_key: key,
        can_read: val.read,
        can_write: val.write,
      }))
    
    await api.put(`/rbac/defaults/${selectedRole.value}`, { permissions })
    toast.success(`${selectedRole.value.replace(/_/g,' ')} permissions saved successfully`)
  } catch(e) {
    toast.error(e?.response?.data?.message || 'Failed to save permissions')
  } finally {
    savingBuiltin.value = false
  }
}
function toggleRP(k,a){if(!rp.value[k])rp.value[k]={read:false,write:false};rp.value[k][a]=!rp.value[k][a];if(a==='write'&&rp.value[k].write)rp.value[k].read=true}
async function fetchCustom(){try{const r=await api.get('/rbac/custom-roles');customRoles.value=r.data?.data||r.data||[]}catch(e){}}
watch(()=>rf.value.based_on,async r=>{if(!r){rp.value={};return}try{const res=await api.get(`/rbac/defaults/${r}`);const responseData=res.data?.data||res.data||{};const rawData=responseData.permissions||responseData;const p=Array.isArray(rawData)?rawData:Object.values(rawData);const m={};p.forEach(x=>{if(x.entity_key){m[x.entity_key]={read:Boolean(x.can_read),write:Boolean(x.can_write)}}});rp.value=m}catch(e){console.error('Failed to load based_on defaults',e)}})
function openCreate(){editingRole.value=null;rf.value={name:'',description:'',based_on:''};rp.value={};showCreate.value=true}
function editRole(r){editingRole.value=r;rf.value={name:r.name,description:r.description||'',based_on:r.based_on||''};const m={};(r.permissions||[]).forEach(p=>{m[p.entity_key]={read:p.can_read,write:p.can_write}});rp.value=m;showCreate.value=true}
async function saveRole(){if(!rf.value.name){toast.warning('Name required');return}savingRole.value=true;try{const p=Object.entries(rp.value).map(([k,v])=>({entity_key:k,can_read:v.read,can_write:v.write}));if(editingRole.value){await api.put(`/rbac/custom-roles/${editingRole.value.id}`,{...rf.value,permissions:p});toast.success('Custom role updated successfully')}else{await api.post('/rbac/custom-roles',{...rf.value,permissions:p});toast.success('Custom role created successfully')}showCreate.value=false;editingRole.value=null;rf.value={name:'',description:'',based_on:''};rp.value={};await fetchCustom()}catch(e){toast.error(e?.response?.data?.message||'Failed to save custom role')}savingRole.value=false}
async function deleteRole(r){if(!confirm(`Delete "${r.name}"?`))return;try{await api.delete(`/rbac/custom-roles/${r.id}`);customRoles.value=customRoles.value.filter(x=>x.id!==r.id);toast.success('Custom role deleted successfully')}catch(e){toast.error('Failed to delete custom role')}}

onMounted(()=>{selectRole('manager');fetchCustom()})
</script>

<style scoped>
.rbac__tabs{display:flex;gap:0.5rem;margin-bottom:1.5rem;border-bottom:1px solid #27272A;padding-bottom:1rem}
.rbac__tab{display:flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;background:none;border:1px solid #27272A;border-radius:0.5rem;color:#A1A1AA;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 150ms ease;font-family:inherit}
.rbac__tab:hover{border-color:#F97316;color:#F97316}
.rbac__tab--active{background:rgba(249,115,22,0.10);border-color:#F97316;color:#FB923C}
.rbac__tab-count{background:#F97316;color:white;border-radius:9999px;padding:0 6px;font-size:0.65rem;font-weight:700}

.rbac__info{font-size:0.875rem;color:#71717A;margin-bottom:1rem}
.rbac__role-tabs{display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem}
.rbac__role-tab{padding:0.375rem 0.75rem;background:#111113;border:1px solid #27272A;border-radius:9999px;color:#A1A1AA;font-size:0.75rem;font-weight:600;cursor:pointer;text-transform:capitalize;transition:all 150ms ease;font-family:inherit}
.rbac__role-tab:hover{border-color:#06B6D4;color:#06B6D4}
.rbac__role-tab--active{background:rgba(6,182,212,0.10);border-color:#06B6D4;color:#06B6D4}

.perm-matrix{margin-top:1rem}
.perm-grid{border:1px solid #27272A;border-radius:0.75rem;overflow:hidden}
.perm-grid__head{display:grid;grid-template-columns:1fr 70px 70px;background:#18181B;padding:0.6rem 1rem;font-size:0.65rem;font-weight:700;color:#52525B;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #27272A}
.perm-grid__row{display:grid;grid-template-columns:1fr 70px 70px;align-items:center;padding:0.5rem 1rem;border-bottom:1px solid #1C1C1F;transition:background 150ms ease}
.perm-grid__row:last-child{border-bottom:none}
.perm-grid__row:hover{background:#18181B}
.perm-grid__row--group{background:#0D0D0F;border-bottom:1px solid #27272A}
.perm-grid__row--group:hover{background:#0D0D0F}
.perm-grid__name{font-size:0.875rem;color:#FAFAFA;font-weight:500;text-transform:capitalize}
.perm-grid__name--group{font-size:0.7rem;font-weight:700;color:#F97316;text-transform:uppercase;letter-spacing:0.06em}
.perm-grid__cell{display:flex;justify-content:center}

.perm-dot{width:12px;height:12px;border-radius:50%;margin:0 auto}
.perm-dot--on{background:#10B981;box-shadow:0 0 8px rgba(16,185,129,0.4)}
.perm-dot--off{background:#27272A}

.toggle-btn{position:relative;width:44px;height:24px;background:#27272A;border:none;border-radius:9999px;cursor:pointer;transition:background 200ms ease;padding:0}
.toggle-btn--on{background:#F97316}
.toggle-btn__knob{position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;transition:transform 200ms ease;box-shadow:0 1px 3px rgba(0,0,0,0.3)}
.toggle-btn--on .toggle-btn__knob{transform:translateX(20px)}

.rbac-save-row{display:flex;align-items:center;justify-content:space-between;margin-top:1rem;padding:1rem;background:#111113;border:1px solid #27272A;border-radius:0.75rem;gap:1rem}
.rbac-save-hint{font-size:0.875rem;color:#71717A;flex:1}

.rbac__custom-toolbar{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem}
.custom-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
.custom-card{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1.25rem;cursor:pointer;transition:all 200ms ease}
.custom-card:hover{border-color:#F97316;box-shadow:0 0 20px rgba(249,115,22,0.10);transform:translateY(-2px)}
.custom-card__name{font-size:1.1rem;font-weight:700;color:#FAFAFA}
.custom-card__base{font-size:0.75rem;color:#71717A;margin-top:0.25rem;text-transform:capitalize}
.custom-card__desc{font-size:0.875rem;color:#A1A1AA;margin:0.75rem 0}
.custom-card__footer{display:flex;align-items:center;justify-content:space-between}
.custom-badge{display:inline-flex;padding:0.2rem 0.6rem;background:rgba(249,115,22,0.10);color:#FB923C;border:1px solid rgba(249,115,22,0.25);border-radius:9999px;font-size:0.75rem;font-weight:600}

.toggle{position:relative;width:44px;height:24px;background:#27272A;border:none;border-radius:9999px;cursor:pointer;transition:background 200ms ease;padding:0}
.toggle--on{background:#F97316}
.toggle__knob{position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;transition:transform 200ms ease;box-shadow:0 1px 3px rgba(0,0,0,0.3)}
.toggle--on .toggle__knob{transform:translateX(20px)}
.toggle--sm{width:40px;height:22px}
.toggle--sm .toggle__knob{width:16px;height:16px;top:3px;left:3px}
.toggle--sm.toggle--on .toggle__knob{transform:translateX(18px)}

.empty{text-align:center;padding:3rem;background:#111113;border:1px solid #27272A;border-radius:1rem}
.empty__icon{font-size:2.5rem;margin-bottom:0.75rem}
.empty__title{font-size:1.1rem;font-weight:700;color:#FAFAFA;margin-bottom:0.5rem}
.empty__text{color:#A1A1AA;font-size:0.9rem}

.action-btn{padding:0.375rem 0.75rem;border-radius:0.375rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 150ms ease;background:transparent;border:none}
.action-btn--delete{color:#52525B}
.action-btn--delete:hover{background:rgba(244,63,94,0.10);color:#F43F5E}

.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem}
.modal-card{background:#18181B;border:1px solid #27272A;border-radius:1.5rem;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.7)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #27272A}
.modal-title{font-weight:700;color:#FAFAFA;font-size:1.1rem}
.modal-close{color:#52525B;font-size:1.25rem;transition:color 150ms ease;background:none;border:none;cursor:pointer}
.modal-close:hover{color:#FAFAFA}
.modal-body{padding:1.5rem}
.modal-footer{display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid #27272A}

.form-group{margin-bottom:1rem}
.form-label{display:block;font-size:0.8rem;font-weight:600;color:#A1A1AA;margin-bottom:0.375rem}
.form-input,.form-select{width:100%;padding:0.625rem 0.875rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.625rem;color:#FAFAFA;font-size:0.875rem;transition:border-color 150ms ease,box-shadow 150ms ease}
.form-input:focus,.form-select:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}

.btn{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 150ms ease;border:none}
.btn-primary{background:#F97316;color:white}
.btn-primary:hover{background:#FB923C}
.btn-secondary{background:#1C1C1F;color:#A1A1AA;border:1px solid #27272A}
.btn-secondary:hover{background:#27272A;color:#FAFAFA}
</style>

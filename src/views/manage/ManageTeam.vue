<template>
  <div class="team">
    <div class="team__toolbar">
      <input v-model="search" class="team__search" placeholder="Search team members..." />
      <button v-if="canWrite" class="btn btn-primary" @click="showAdd = true">+ Add Team Member</button>
    </div>

    <div class="team__table-wrap">
      <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Custom Role</th><th>Access</th><th>Status</th><th v-if="canWrite">Actions</th></tr></thead>
        <tbody>
          <tr v-if="loading"><td :colspan="canWrite?7:6"><div v-for="i in 4" :key="i" class="shimmer"/></td></tr>
          <tr v-else-if="!Array.isArray(filtered) || filtered.length===0"><td :colspan="canWrite?7:6"><div class="empty">No team members yet</div></td></tr>
          <tr v-else v-for="m in (Array.isArray(filtered)?filtered:[]).filter(x=>x!=null)" :key="m?.id">
            <td><div class="member"><div class="member__avatar">{{ m.name?.charAt(0)?.toUpperCase() }}</div><span class="member__name">{{ m.name }}</span></div></td>
            <td style="color:#A1A1AA">{{ m.email }}</td>
            <td><span class="role-badge">{{ m.staff_role || 'N/A' }}</span></td>
            <td><span v-if="m.custom_role" class="custom-badge">{{ m.custom_role?.name }}</span><span v-else style="color:#52525B">Default</span></td>
            <td><span class="access-indicator" :class="getMemberAccessClass(m)">{{ getMemberAccessLabel(m) }}</span></td>
            <td><span class="badge badge--success">Active</span></td>
            <td v-if="canWrite"><div style="display:flex;gap:0.5rem"><button class="action-btn action-btn--edit" @click="openPerms(m)">Permissions</button><button class="action-btn action-btn--delete" @click="remove(m)">Remove</button></div></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Modal -->
    <div v-if="showAdd" class="modal-overlay" @click.self="showAdd=false">
      <div class="modal-card" style="max-width:720px">
        <div class="modal-header"><span class="modal-title">Add Team Member</span><button class="modal-close" @click="showAdd=false">×</button></div>
        <div class="modal-body">
          <div class="form-group"><label class="form-label">Full Name *</label><input v-model="nm.name" class="form-input" placeholder="John Doe"/></div>
          <div class="form-group"><label class="form-label">Email *</label><input v-model="nm.email" type="email" class="form-input" placeholder="john@cafe.com"/></div>
          <div class="form-group"><label class="form-label">Password *</label><input v-model="nm.password" type="password" class="form-input" placeholder="Min 8 characters"/></div>
          <div class="form-group"><label class="form-label">Base Role *</label><select v-model="nm.staff_role" class="form-select"><option value="">Select role...</option><option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option></select></div>
          <div v-if="addError" class="form-error">{{ addError }}</div>

          <div v-if="!nm.staff_role" class="perms-hint">
            <span>Select a role above to load default permissions</span>
          </div>
          <div v-else>
            <div class="perms-role-label">
              Showing default permissions for:
              <strong style="color:#06B6D4;text-transform:capitalize">{{ nm.staff_role?.replace(/_/g,' ') }}</strong>
              <span style="color:#71717A">. Customize as needed</span>
            </div>
            <div class="perm-grid">
              <div class="perm-grid__head"><span>Module</span><span>Read</span><span>Write</span></div>
              <div v-for="e in allEntities" :key="e.key" class="perm-grid__row">
                <span class="perm-grid__name">{{ e.label }}</span>
                <div class="perm-grid__cell"><button class="toggle toggle--sm" :class="{'toggle--on':getNewMP(e.key,'read')}" @click="toggleNewMP(e.key,'read')" type="button"><span class="toggle__knob"/></button></div>
                <div class="perm-grid__cell"><button class="toggle toggle--sm" :class="{'toggle--on':getNewMP(e.key,'write')}" @click="toggleNewMP(e.key,'write')" type="button"><span class="toggle__knob"/></button></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showAdd=false">Cancel</button>
          <button class="btn btn-primary" @click="addMember" :disabled="adding">{{ adding?'Adding...':'Add Member' }}</button>
        </div>
      </div>
    </div>

    <!-- Permissions Modal -->
    <div v-if="permTarget" class="modal-overlay" @click.self="permTarget=null">
      <div class="modal-card" style="max-width:720px">
        <div class="modal-header"><div><div class="modal-title">Permissions: {{ permTarget.name }}</div><div style="font-size:0.75rem;color:#71717A;margin-top:2px">Role: {{ permTarget.staff_role }}</div></div><button class="modal-close" @click="permTarget=null">×</button></div>
        <div class="modal-body">
          <div class="perm-grid">
            <div class="perm-grid__head"><span>Module</span><span>Read</span><span>Write</span></div>
            <div v-for="e in allEntities" :key="e.key" class="perm-grid__row">
              <span class="perm-grid__name">{{ e.label }}</span>
              <div class="perm-grid__cell"><button class="toggle toggle--sm" :class="{'toggle--on':getP(e.key,'read')}" @click="toggleP(e.key,'read')" type="button"><span class="toggle__knob"/></button></div>
              <div class="perm-grid__cell"><button class="toggle toggle--sm" :class="{'toggle--on':getP(e.key,'write')}" @click="toggleP(e.key,'write')" type="button"><span class="toggle__knob"/></button></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="revokeAll" type="button">🔒 Revoke All</button>
          <button class="btn btn-secondary" @click="restoreAll" type="button">🔓 Restore All</button>
          <button class="btn btn-secondary" @click="permTarget=null">Cancel</button>
          <button class="btn btn-primary" @click="savePerms" :disabled="savingP">{{ savingP?'Saving...':'Save Permissions' }}</button>
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

const props = defineProps({ restaurantId: [Number,String], canWrite: Boolean })
const toast = useToast()
const members = ref([]); const loading = ref(false); const search = ref('')
const showAdd = ref(false); const adding = ref(false); const addError = ref('')
const permTarget = ref(null); const permOverrides = ref({}); const savingP = ref(false)
const allEntities = computed(() => entities)
const roles = [{value:'manager',label:'Manager'},{value:'floor_manager',label:'Floor Manager'},{value:'cashier',label:'Cashier'},{value:'server',label:'Server'},{value:'kitchen',label:'Kitchen'},{value:'barista',label:'Barista'},{value:'host',label:'Host'},{value:'inventory',label:'Inventory'}]
const nm = ref({name:'',email:'',password:'',staff_role:''})
const newMemberPerms = ref({})
const filtered = computed(() => { const raw=members.value??[]; const list=Array.isArray(raw)?raw:[]; if(!search.value)return list; const q=search.value.toLowerCase(); return list.filter(m=>m&&(m.name?.toLowerCase().includes(q)||m.email?.toLowerCase().includes(q))) })

watch(() => nm.value.staff_role, async (role) => {
  if (!role) {
    const perms = {}
    entities.forEach(e => {
      perms[e.key] = { read: false, write: false }
    })
    newMemberPerms.value = perms
    return
  }
  
  try {
    const res = await api.get(`/rbac/defaults/${role}`)
    // API returns { data: { role: "...", permissions: [...] } }
    const responseData = res.data?.data || res.data || {}
    const rawData = responseData.permissions || responseData
    const permsArray = Array.isArray(rawData) ? rawData : 
                       Object.values(rawData)
    
    const perms = {}
    entities.forEach(e => {
      perms[e.key] = { read: false, write: false }
    })
    
    permsArray.forEach(d => {
      if (d.entity_key && perms[d.entity_key] !== undefined) {
        perms[d.entity_key] = {
          read: Boolean(d.can_read),
          write: Boolean(d.can_write)
        }
      }
    })
    
    newMemberPerms.value = { ...perms }
  } catch(e) {
    console.error('Failed to load role defaults', e)
    const perms = {}
    entities.forEach(e => {
      perms[e.key] = { read: false, write: false }
    })
    newMemberPerms.value = perms
  }
}, { immediate: false })

function getNewMP(k,a){return newMemberPerms.value[k]?.[a]??false}
function toggleNewMP(k,a){if(!newMemberPerms.value[k])newMemberPerms.value[k]={read:false,write:false};newMemberPerms.value[k][a]=!newMemberPerms.value[k][a];if(a==='write'&&newMemberPerms.value[k].write)newMemberPerms.value[k].read=true}

async function fetchMembers(){
  loading.value=true;
  try{
    const r=await api.get('/users',{params:{restaurant_id:props.restaurantId}});
    const raw=r.data?.data;
    const memberList=Array.isArray(raw)?raw:Array.isArray(raw?.data)?raw.data:[];
    const list=Array.isArray(memberList)?memberList.filter(m=>m!=null):[];
    const membersWithPerms=await Promise.all(list.map(async(member)=>{
      try{
        const permRes=await api.get(`/rbac/users/${member.id}/permissions`);
        const raw = permRes.data?.data || permRes.data || {};
        const effective = raw.effective || raw;
        if (Array.isArray(effective)) {
          return{...member, permissions: effective}
        }
        const permsArray = Object.entries(effective).map(([key, val]) => ({
          entity_key: key, can_read: !!val?.can_read, can_write: !!val?.can_write
        }));
        return{...member, permissions: permsArray}
      }catch(e){
        return{...member,permissions:[]}
      }
    }));
    members.value=membersWithPerms
  }catch(e){
    console.error('Failed to load team members', e);
    toast.error(e?.response?.data?.message || 'Failed to load team members');
    members.value=[];
  }finally{
    loading.value=false;
  }
}
async function addMember() {
  addError.value=''; if(!nm.value.name||!nm.value.email||!nm.value.password||!nm.value.staff_role){addError.value='Fill all required fields';return}
  adding.value=true; try{
    const res=await api.post('/users',{...nm.value,restaurant_id:props.restaurantId,role:'restaurant'});
    const newUser=res.data?.data||res.data;
    
    const permissions=Object.entries(newMemberPerms.value).map(([k,v])=>({entity_key:k,can_read:v.read,can_write:v.write}));
    await api.put(`/rbac/users/${newUser.id}/permissions`,{permissions});
    
    toast.success('Team member added successfully');
    showAdd.value=false;nm.value={name:'',email:'',password:'',staff_role:''};newMemberPerms.value={};await fetchMembers()
  }catch(e){addError.value=e?.response?.data?.message||'Failed'}adding.value=false
}
async function openPerms(m) {
  permTarget.value = m
  permOverrides.value = {}
  
  try {
    const res = await api.get(`/rbac/users/${m.id}/permissions`)
    const rawData = res.data?.data || res.data || {}
    const effective = rawData.effective || rawData
    const permsArray = Array.isArray(effective) ? effective :
                       Object.entries(effective).map(([key, val]) => ({
                         entity_key: key, can_read: !!val?.can_read, can_write: !!val?.can_write
                       }))
    
    const map = {}
    entities.forEach(e => {
      map[e.key] = { read: false, write: false }
    })
    
    permsArray.forEach(p => {
      if (p.entity_key) {
        map[p.entity_key] = {
          read: Boolean(p.can_read),
          write: Boolean(p.can_write)
        }
      }
    })
    
    permOverrides.value = map
  } catch(e) {
    console.error('Failed to load user permissions', e)
    const map = {}
    entities.forEach(e => {
      map[e.key] = { read: false, write: false }
    })
    permOverrides.value = map
  }
}
function getP(k,a){return permOverrides.value[k]?.[a]??false}
function toggleP(k,a){if(!permOverrides.value[k])permOverrides.value[k]={read:false,write:false};permOverrides.value[k][a]=!permOverrides.value[k][a];if(a==='write'&&permOverrides.value[k].write)permOverrides.value[k].read=true}
async function savePerms(){savingP.value=true;try{const p=Object.entries(permOverrides.value).map(([k,v])=>({entity_key:k,can_read:v.read,can_write:v.write}));await api.put(`/rbac/users/${permTarget.value.id}/permissions`,{permissions:p});toast.success('Permissions saved successfully');permTarget.value=null;await fetchMembers()}catch(e){toast.error('Failed to save permissions')}savingP.value=false}
function revokeAll(){entities.forEach(e=>{permOverrides.value[e.key]={read:false,write:false}});toast.warning('All permissions revoked. Click Save to confirm')}
function restoreAll(){entities.forEach(e=>{permOverrides.value[e.key]={read:true,write:true}});toast.success('All permissions restored. Click Save to confirm')}
function getMemberAccessLabel(member){if(!member.permissions||member.permissions.length===0)return'Full Access';const hasAny=member.permissions.some(p=>p.can_read||p.can_write);const hasAll=member.permissions.every(p=>p.can_read&&p.can_write);if(hasAll)return'Full Access';if(!hasAny)return'No Access';return'Custom'}
function getMemberAccessClass(member){const label=getMemberAccessLabel(member);if(label==='Full Access')return'access-full';if(label==='No Access')return'access-none';return'access-custom'}
async function remove(m){if(!confirm(`Remove ${m?.name}?`))return;try{await api.delete(`/users/${m?.id}`);members.value=(members.value||[]).filter(x=>x?.id!==m?.id);toast.success('Team member removed successfully')}catch(e){toast.error('Failed to remove team member')}}

onMounted(fetchMembers)
</script>

<style scoped>
.team__toolbar{display:flex;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap}
.team__search{flex:1;max-width:360px;padding:0.6rem 1rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.625rem;color:#FAFAFA;font-size:0.875rem;transition:border-color 150ms ease}
.team__search::placeholder{color:#52525B}
.team__search:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}
.team__table-wrap{background:#111113;border:1px solid #27272A;border-radius:1rem;overflow:hidden}

.member{display:flex;align-items:center;gap:0.75rem}
.member__avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#F97316,#FB923C);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:white;flex-shrink:0}
.member__name{font-weight:500;color:#FAFAFA}

.role-badge{display:inline-flex;padding:0.2rem 0.6rem;background:rgba(6,182,212,0.10);color:#06B6D4;border:1px solid rgba(6,182,212,0.25);border-radius:9999px;font-size:0.75rem;font-weight:600;text-transform:capitalize}
.custom-badge{display:inline-flex;padding:0.2rem 0.6rem;background:rgba(249,115,22,0.10);color:#FB923C;border:1px solid rgba(249,115,22,0.25);border-radius:9999px;font-size:0.75rem;font-weight:600}

.access-indicator{display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600}
.access-full{background:rgba(16,185,129,0.10);color:#10B981;border:1px solid rgba(16,185,129,0.25)}
.access-none{background:rgba(244,63,94,0.10);color:#F43F5E;border:1px solid rgba(244,63,94,0.25)}
.access-custom{background:rgba(249,194,46,0.10);color:#F9C22E;border:1px solid rgba(249,194,46,0.25)}

.perm-grid{border:1px solid #27272A;border-radius:0.75rem;overflow:hidden}
.perm-grid__head{display:grid;grid-template-columns:1fr 70px 70px;background:#18181B;padding:0.6rem 1rem;font-size:0.65rem;font-weight:700;color:#52525B;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #27272A}
.perm-grid__row{display:grid;grid-template-columns:1fr 70px 70px;align-items:center;padding:0.5rem 1rem;border-bottom:1px solid #1C1C1F;transition:background 150ms ease}
.perm-grid__row:last-child{border-bottom:none}
.perm-grid__row:hover{background:#18181B}
.perm-grid__name{font-size:0.875rem;color:#FAFAFA;font-weight:500;text-transform:capitalize}
.perm-grid__cell{display:flex;justify-content:center}

.toggle{position:relative;width:44px;height:24px;background:#27272A;border:none;border-radius:9999px;cursor:pointer;transition:background 200ms ease;padding:0}
.toggle--on{background:#F97316}
.toggle__knob{position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;transition:transform 200ms ease;box-shadow:0 1px 3px rgba(0,0,0,0.3)}
.toggle--on .toggle__knob{transform:translateX(20px)}
.toggle--sm{width:40px;height:22px}
.toggle--sm .toggle__knob{width:16px;height:16px;top:3px;left:3px}
.toggle--sm.toggle--on .toggle__knob{transform:translateX(18px)}

.table{width:100%;border-collapse:collapse}
.table th{background:#18181B;color:#52525B;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;padding:0.75rem 1rem;text-align:left;border-bottom:1px solid #27272A}
.table td{padding:0.75rem 1rem;border-bottom:1px solid #1C1C1F;color:#FAFAFA;font-size:0.875rem}
.table tr:hover td{background:#18181B}

.badge{display:inline-flex;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600}
.badge--success{background:rgba(16,185,129,0.12);color:#10B981}

.action-btn{padding:0.375rem 0.75rem;border-radius:0.375rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 150ms ease;background:transparent;border:none}
.action-btn--edit{color:#06B6D4}
.action-btn--edit:hover{background:rgba(6,182,212,0.10)}
.action-btn--delete{color:#52525B}
.action-btn--delete:hover{background:rgba(244,63,94,0.10);color:#F43F5E}

.shimmer{height:44px;background:linear-gradient(90deg,#1C1C1F 25%,#27272A 50%,#1C1C1F 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;margin:2px 0;border-radius:0.5rem}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.empty{text-align:center;padding:2rem;color:#52525B;font-size:0.9rem}

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
.form-input:focus,.form-select:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,0.12)}
.form-error{color:#F43F5E;font-size:0.8rem;margin-top:0.5rem}

.btn{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 150ms ease;border:none}
.btn-primary{background:#F97316;color:white}
.btn-primary:hover{background:#FB923C}
.btn-secondary{background:#1C1C1F;color:#A1A1AA;border:1px solid #27272A}
.btn-secondary:hover{background:#27272A;color:#FAFAFA}

.perms-hint{padding:1rem;background:#18181B;border:1px dashed #27272A;border-radius:0.75rem;text-align:center;color:#71717A;font-size:0.875rem}
.perms-role-label{font-size:0.875rem;color:#A1A1AA;margin-bottom:0.5rem}
</style>

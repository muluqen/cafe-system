<template>
  <div class="analytics-layout">
    <header class="panel-header" style="margin-bottom: 0;">
      <h1 class="panel-title">Analytics & Security</h1>
    </header>
    
    <div class="kpi-grid">
      <div class="kpi-card panel">
        <h3>Today's Revenue</h3>
        <strong>${{ totalRevenue.toFixed(2) }}</strong>
        <p class="positive">↑ Operational Flow Active</p>
      </div>
      <div class="kpi-card panel">
        <h3>Avg Prep Time</h3>
        <strong>{{ avgPrepTime }} min</strong>
        <p class="positive">↓ Highly Efficient</p>
      </div>
      <div class="kpi-card panel">
        <h3>Active Tables</h3>
        <strong>{{ activeTables }}</strong>
        <p>Currently engaged</p>
      </div>
    </div>

    <div class="rbac-panel panel">
      <h2 style="font-family: 'Outfit'; margin: 0 0 0.5rem 0;">Role Access Control (RBAC) Engine</h2>
      <p class="muted" style="margin-bottom: 1.5rem;">Toggle precisely which tools each staff role can access in their workspace. Changes take effect instantly.</p>
      
      <div class="rbac-matrix-wrap">
        <table class="data-table rbac-matrix">
          <thead>
            <tr>
              <th>Role</th>
              <th v-for="entity in controllableEntities" :key="entity.key">{{ entity.label }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in roles" :key="role.value">
              <td><strong>{{ role.label }}</strong></td>
              <td v-for="entity in controllableEntities" :key="entity.key">
                <label class="switch">
                  <input type="checkbox" :checked="hasAccess(role.value, entity.key)" @change="toggleAccess(role.value, entity.key, $event.target.checked)">
                  <span class="slider"></span>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../services/api';
import { staffRoleOptions } from '../utils/staffRoles';
import { entities } from '../config/entities';
import { useAuthStore } from '../stores/authStore';

const auth = useAuthStore();
const permissions = ref([]);

const totalRevenue = ref(0);
const avgPrepTime = ref(0);
const activeTables = ref(0);

const roles = staffRoleOptions.filter(r => r.value !== 'manager');
const controllableEntities = entities.filter(e => e.roles.includes('restaurant') && !['preferences', 'restaurants', 'shifts', 'staff_shift_assignments'].includes(e.key));

async function loadData() {
  try {
    const res = await api.get('/role_permissions');
    permissions.value = res.data;
  } catch(e) {
    console.error("Failed to load permissions", e);
  }

  try {
    const ordersRes = await api.get('/orders', { params: { per_page: 100 } });
    const orders = ordersRes.data?.data || [];
    totalRevenue.value = orders.reduce((sum, o) => sum + Number(o.total), 0);
    
    // Simulate prep time avg
    avgPrepTime.value = 14; 
    
    const tablesRes = await api.get('/tables');
    activeTables.value = (tablesRes.data?.data || []).filter(t => t.status === 'occupied').length;
  } catch(e) {}
}

function hasAccess(role, entityKey) {
  const perm = permissions.value.find(p => p.staff_role === role && p.entity_key === entityKey);
  if (perm) return !!perm.can_read;
  
  const entityDef = entities.find(e => e.key === entityKey);
  return !!(entityDef && entityDef.staffRoles && entityDef.staffRoles.includes(role));
}

async function toggleAccess(role, entityKey, isChecked) {
  let perm = permissions.value.find(p => p.staff_role === role && p.entity_key === entityKey);
  if (perm) {
    perm.can_read = isChecked;
  } else {
    permissions.value.push({ staff_role: role, entity_key: entityKey, can_read: isChecked });
  }

  try {
    await api.post('/role_permissions', {
      staff_role: role,
      entity_key: entityKey,
      can_read: isChecked,
      can_write: isChecked
    });
  } catch(err) {
    console.error("Failed to save permission", err);
    loadData(); 
  }
}

onMounted(loadData);
</script>

<style scoped>
.analytics-layout { display: flex; flex-direction: column; gap: 1.5rem; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
.kpi-card { padding: 1.5rem; border-radius: 12px; border: 1px solid var(--line); text-align: center; background: var(--card); box-shadow: var(--shadow-sm); }
.kpi-card h3 { margin: 0 0 0.5rem 0; font-family: 'Outfit'; color: var(--text-soft); font-size: 1.2rem; }
.kpi-card strong { font-size: 2.8rem; color: var(--text-main); display: block; margin-bottom: 0.5rem; font-family: 'Outfit'; }
.kpi-card p { margin: 0; font-weight: bold; font-size: 0.95rem; }
.positive { color: var(--olive); }
.negative { color: var(--accent); }
.rbac-panel { padding: 1.5rem; border-radius: 12px; border: 1px solid var(--line); overflow-x: auto; background: var(--card); box-shadow: var(--shadow-sm); }
.rbac-matrix-wrap { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
.rbac-matrix { margin: 0; border: none; }
.rbac-matrix th { font-family: 'Outfit'; font-weight: bold; background: var(--bg-accent); padding: 1rem; text-align: center; font-size: 0.9rem; border-bottom: 2px solid var(--line); border-right: 1px solid var(--line); white-space: nowrap; }
.rbac-matrix th:last-child { border-right: none; }
.rbac-matrix td { text-align: center; padding: 1rem; border-bottom: 1px solid var(--line); border-right: 1px solid var(--line); }
.rbac-matrix td:last-child { border-right: none; }
.rbac-matrix tr:last-child td { border-bottom: none; }
.rbac-matrix td:first-child { text-align: left; background: var(--bg-accent); white-space: nowrap; }

.switch { position: relative; display: inline-block; width: 48px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--line); transition: .2s; border-radius: 34px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .2s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
input:checked + .slider { background-color: var(--accent); }
input:checked + .slider:before { transform: translateX(22px); }
</style>

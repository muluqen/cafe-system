<template>
  <div class="manage">
    <div v-if="entityKey !== 'restaurant_settings'" class="manage__header animate-on-scroll">
      <div>
        <div class="manage__title">{{ entityConfig?.label }}</div>
        <div class="manage__sub">Manage your {{ entityConfig?.label?.toLowerCase() }}</div>
      </div>
      <button v-if="canWrite && entityKey !== 'role_permissions'" class="btn btn-primary" @click="openAdd">+ Add {{ singularLabel }}</button>
    </div>

    <div v-if="!canRead" class="empty-state">
      <div class="empty-state__icon">🔒</div>
      <div class="empty-state__title">Access Denied</div>
      <div class="empty-state__text">You don't have permission to view this section.</div>
    </div>

    <div v-else-if="!entityConfig" class="empty-state">
      <div class="empty-state__icon">❓</div>
      <div class="empty-state__title">Not Found</div>
      <div class="empty-state__text">This section doesn't exist.</div>
    </div>

    <ManageTeam v-else-if="entityKey === 'users'" :restaurant-id="restaurantId" :can-write="canWrite" />
    <ManageRbac v-else-if="entityKey === 'role_permissions'" :restaurant-id="restaurantId" />
    <RestaurantSettings v-else-if="entityKey === 'restaurant_settings'" :restaurant-id="restaurantId" />

    <template v-else>
      <!-- Loading skeleton -->
      <div v-if="loading" class="skeleton-wrap">
        <div v-for="i in 5" :key="i" class="shimmer-row" />
      </div>
      <div v-if="error" style="padding: 2rem; text-align: center; color: #F43F5E; background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); border-radius: 1rem; margin-top: 1rem;">
        <div style="font-size: 1.5rem; margin-bottom: 0.5rem">⚠️</div>
        <div style="font-weight: 600; margin-bottom: 0.25rem">Failed to load data</div>
        <div style="font-size: 0.875rem; color: #A1A1AA">{{ error }}</div>
        <button @click="fetchData" style="margin-top: 1rem; padding: 0.5rem 1.25rem; background: #F97316; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Try Again</button>
      </div>
      <ManageTable
        v-else
        :entity-key="entityKey"
        :entity-config="entityConfig"
        :restaurant-id="restaurantId"
        :can-write="canWrite"
        @edit="openEdit"
        @deleted="refresh"
        ref="tableRef"
      />
      <MenuItemForm v-if="formVisible && entityKey === 'menu_items'" :restaurant-id="restaurantId" :record="editingRecord" @saved="onSaved" @close="closeForm" />
      <ManageForm v-else-if="formVisible && entityKey !== 'menu_items'" :entity-key="entityKey" :entity-config="entityConfig" :restaurant-id="restaurantId" :record="editingRecord" @saved="onSaved" @close="closeForm" />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { entities } from '../../config/entities'
import ManageTable from './ManageTable.vue'
import ManageForm from './ManageForm.vue'
import MenuItemForm from './MenuItemForm.vue'
import ManageTeam from './ManageTeam.vue'
import ManageRbac from './ManageRbac.vue'
import RestaurantSettings from './RestaurantSettings.vue'

const route = useRoute()
const auth = useAuthStore()
const entityKey = computed(() => route.params.entityKey)
const restaurantId = computed(() => auth.user?.restaurant_id)
const entityConfig = computed(() => entities.find(e => e.key === entityKey.value))
const singularLabel = computed(() => { const l = entityConfig.value?.label || ''; return l.endsWith('s') ? l.slice(0,-1) : l })

const canRead = computed(() => {
  if (auth.isSuperAdmin) return true
  return auth.canRead(entityKey.value)
})
const canWrite = computed(() => {
  if (auth.isSuperAdmin) return true
  return auth.canWrite(entityKey.value)
})

const formVisible = ref(false)
const editingRecord = ref(null)
const tableRef = ref(null)
const loading = ref(true)
const error = ref('')

function openAdd() { editingRecord.value = null; formVisible.value = true }
function openEdit(r) { editingRecord.value = { ...r }; formVisible.value = true }
function closeForm() { formVisible.value = false; editingRecord.value = null }
function onSaved() { closeForm(); refresh() }

async function refresh() {
  error.value = ''
  loading.value = true
  await nextTick()
  try {
    if (tableRef.value) {
      await tableRef.value.fetchData()
    }
  } catch(e) {
    error.value = e?.response?.data?.message || e.message || 'Failed to refresh data'
  } finally {
    loading.value = false
  }
}

async function fetchData() {
  error.value = ''
  loading.value = true
  try {
    if (tableRef.value) {
      await tableRef.value.fetchData()
    }
  } catch(e) {
    error.value = e?.response?.data?.message || e.message || 'Failed to load data'
  } finally {
    loading.value = false
  }
}

watch(entityKey, () => {
  formVisible.value = false
  editingRecord.value = null
  loading.value = true
  error.value = ''
})

onMounted(() => {
  fetchData()
  const obs = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting){e.target.classList.add('is-visible');obs.unobserve(e.target)} }), { threshold:0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => obs.observe(el))
})
</script>

<style scoped>
.manage{max-width:1400px}
.manage__header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;gap:1rem}
.manage__title{font-size:1.5rem;font-weight:800;color:#FAFAFA}
.manage__sub{font-size:0.875rem;color:#A1A1AA;margin-top:0.25rem}

.empty-state{text-align:center;padding:3rem;background:#111113;border:1px solid #27272A;border-radius:1rem}
.empty-state__icon{font-size:2.5rem;margin-bottom:0.75rem}
.empty-state__title{font-size:1.1rem;font-weight:700;color:#F43F5E;margin-bottom:0.5rem}
.empty-state__text{color:#A1A1AA;font-size:0.9rem}

.btn{padding:0.625rem 1.25rem;border-radius:0.625rem;font-size:0.875rem;font-weight:700;cursor:pointer;transition:all 150ms ease}
.btn-primary{background:#F97316;color:white}
.btn-primary:hover{background:#FB923C;box-shadow:0 0 20px rgba(249,115,22,0.3)}

.skeleton-wrap{background:#111113;border:1px solid #27272A;border-radius:1rem;padding:1rem}
.shimmer-row{height:48px;background:linear-gradient(90deg,#1C1C1F 25%,#27272A 50%,#1C1C1F 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:0.5rem;margin-bottom:0.5rem}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

.animate-on-scroll{opacity:0;transform:translateY(20px);transition:opacity 0.5s ease,transform 0.5s ease}
.animate-on-scroll.is-visible{opacity:1;transform:translateY(0)}
</style>

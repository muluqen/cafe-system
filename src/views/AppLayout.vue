<template>
  <div class="app-layout">
    <!-- Mobile FAB -->
    <button class="mobile-fab" @click="mobileOpen = !mobileOpen" aria-label="Toggle menu">
      <span class="mobile-fab__dots"><span/><span/><span/><span/></span>
    </button>

    <!-- Backdrop -->
    <div v-if="mobileOpen" class="mobile-backdrop" @click="mobileOpen = false"/>

    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar--open': mobileOpen }">
      <!-- Header -->
      <div class="sidebar__head">
        <div class="sidebar__brand">🍽 Tavliq</div>
        <div class="sidebar__restaurant">{{ restaurantBrand }}</div>
        <div class="sidebar__role-badge">{{ staffRoleLabel }}</div>
      </div>

      <!-- Customer selector -->
      <div v-if="auth.isCustomer" class="sidebar__selector">
        <label class="sidebar__selector-label">Restaurant</label>
        <select v-model="selectedRestaurant" class="sidebar__select" @change="auth.setSelectedRestaurant(selectedRestaurant)">
          <option value="">Choose one</option>
          <option v-for="r in auth.publicRestaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </div>

      <!-- Nav -->
      <nav class="sidebar__nav">
        <template v-for="(link, idx) in navLinks" :key="link.divider ? 'd'+idx : link.name">
          <div v-if="link.divider" class="sidebar__divider">{{ link.label }}</div>
          <RouterLink
            v-else
            class="sidebar__link"
            :to="link.path || { name: link.name }"
            @click="mobileOpen = false"
          >
            <span class="sidebar__link-icon">{{ link.icon }}</span>
            <span class="sidebar__link-label">{{ link.label }}</span>
          </RouterLink>
        </template>
      </nav>

      <!-- Footer -->
      <div class="sidebar__foot">
        <div class="sidebar__user">
          <div class="sidebar__avatar">{{ userInitials }}</div>
          <div class="sidebar__user-info">
            <div class="sidebar__user-name">{{ auth.user?.name }}</div>
            <div class="sidebar__user-role">{{ auth.staffRole }}</div>
          </div>
        </div>
        <button class="sidebar__logout" @click="logout">Sign out</button>
      </div>
    </aside>

    <!-- Main -->
    <main class="main">
      <RouterView v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="$route.fullPath" />
        </Transition>
      </RouterView>
    </main>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { getStaffRoleMeta } from '../utils/staffRoles'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const mobileOpen = ref(false)
const selectedRestaurant = ref(auth.selectedRestaurantId || '')

const userInitials = computed(() => {
  const n = auth.user?.name || 'U'
  return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const restaurantBrand = computed(() => {
  const m = auth.publicRestaurants.find(r => String(r.id) === String(auth.user?.restaurant_id || ''))
  return m?.name || 'Tavliq'
})

const staffRoleLabel = computed(() => getStaffRoleMeta(auth.staffRole).label)

const navLinks = computed(() => {
  if (auth.isCustomer) {
    return [
      { icon: '🔍', name: 'customer-discover', label: 'Discover', path: '/customer/orders' },
    ]
  }
  const role = auth.staffRole
  const isLeader = role === 'manager' || role === 'floor_manager'

  const leaderOps = isLeader ? [
    { icon: '📊', name: 'staff-dashboard', label: 'Dashboard', path: '/app/dashboard' },
    { icon: '📈', name: 'analytics', label: 'Analytics', path: '/app/analytics' },
    { icon: '💡', name: 'restaurant-pulse', label: 'Restaurant Pulse', path: '/app/pulse' },
  ] : []

  const allPermOps = [
    { icon: '🧾', name: 'pos', label: 'Point of Sale', path: '/app/pos', permKey: 'orders' },
    { icon: '🍳', name: 'kds', label: 'Kitchen Display', path: '/app/kitchen', permKey: 'kds_kitchen' },
  ]

  const allMgmt = [
    { icon: '🍽', name: 'menu_categories', label: 'Menu Categories', path: '/app/manage/menu_categories', permKey: 'menu_categories' },
    { icon: '🥗', name: 'menu_items', label: 'Menu Items', path: '/app/manage/menu_items', permKey: 'menu_items' },
    { icon: '📦', name: 'ingredients', label: 'Ingredients', path: '/app/manage/ingredients', permKey: 'ingredients' },
    { icon: '📊', name: 'inventory_transactions', label: 'Inventory', path: '/app/manage/inventory_transactions', permKey: 'inventory_transactions' },
    { icon: '🔗', name: 'recipe_ingredients', label: 'Recipe Links', path: '/app/manage/recipe_ingredients', permKey: 'recipe_ingredients' },
    { icon: '🪑', name: 'tables', label: 'Tables', path: '/app/manage/tables', permKey: 'tables' },
    { icon: '👥', name: 'users', label: 'Team', path: '/app/manage/users', permKey: 'users' },
    { icon: '🕐', name: 'shifts', label: 'Shifts', path: '/app/manage/shifts', permKey: 'shifts' },
    { icon: '💳', name: 'payment_events', label: 'Payments', path: '/app/manage/payment_events', permKey: 'payment_events' },
    { icon: '⚙️', name: 'restaurant_settings', label: 'Settings', path: '/app/manage/restaurant_settings', permKey: 'restaurant_settings' },
    { icon: '🔒', name: 'role_permissions', label: 'Permissions', path: '/app/manage/role_permissions', permKey: 'role_permissions' },
  ]
  const div = { divider: true, label: 'MANAGE' }

  const visiblePermOps = allPermOps.filter(l => auth.canRead(l.permKey))
  const visibleMgmt = allMgmt.filter(l => auth.canRead(l.permKey))

  const result = [...leaderOps, ...visiblePermOps]
  if (visibleMgmt.length > 0) result.push(div, ...visibleMgmt)
  return result
})

async function logout() {
  await auth.logout()
  router.push('/login')
}

watch(() => auth.selectedRestaurantId, v => { selectedRestaurant.value = v || '' })
watch(() => route.fullPath, () => { if (window.innerWidth <= 768) mobileOpen.value = false })
</script>

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
button{border:none;cursor:pointer;font-family:inherit;background:none}
input,select,textarea{font-family:inherit;outline:none}
a{text-decoration:none}
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#27272A;border-radius:9999px}
::-webkit-scrollbar-thumb:hover{background:#F97316}
</style>

<style scoped>
.app-layout{display:flex;min-height:100vh;background:#09090B;font-family:'Inter',system-ui,sans-serif;color:#FAFAFA}

.sidebar{width:260px;position:fixed;left:0;top:0;bottom:0;background:#111113;border-right:1px solid #27272A;display:flex;flex-direction:column;z-index:200;overflow-y:auto}
.sidebar__head{padding:1.5rem;border-bottom:1px solid #27272A}
.sidebar__brand{font-weight:800;font-size:1.1rem;color:#FAFAFA}
.sidebar__restaurant{font-size:0.75rem;color:#A1A1AA;margin-top:0.25rem}
.sidebar__role-badge{display:inline-block;margin-top:0.5rem;background:rgba(249,115,22,0.12);color:#FB923C;border:1px solid rgba(249,115,22,0.2);border-radius:9999px;padding:0.2rem 0.7rem;font-size:0.7rem;font-weight:700;text-transform:capitalize}

.sidebar__selector{padding:1rem 1.5rem;border-bottom:1px solid #27272A}
.sidebar__selector-label{display:block;font-size:0.65rem;font-weight:700;color:#52525B;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:0.375rem}
.sidebar__select{width:100%;padding:0.5rem 0.75rem;background:#1C1C1F;border:1px solid #27272A;border-radius:0.5rem;color:#FAFAFA;font-size:0.8rem}

.sidebar__nav{flex:1;padding:0.75rem 0;display:flex;flex-direction:column;gap:2px}
.sidebar__divider{padding:1.5rem 1rem 0.5rem;font-size:0.65rem;font-weight:700;letter-spacing:0.1em;color:#52525B;text-transform:uppercase}
.sidebar__link{display:flex;align-items:center;gap:0.75rem;padding:0.6rem 1rem;margin:0.1rem 0.5rem;border-radius:0.5rem;font-size:0.875rem;color:#A1A1AA;transition:all 150ms ease;cursor:pointer}
.sidebar__link:hover{background:#1C1C1F;color:#FAFAFA;transform:translateX(2px)}
.sidebar__link.router-link-active{background:rgba(249,115,22,0.10);color:#FB923C;border-left:2px solid #F97316;font-weight:600}
.sidebar__link-icon{font-size:1rem;width:20px;text-align:center}
.sidebar__link-label{flex:1}

.sidebar__foot{margin-top:auto;padding:1rem 1.5rem;border-top:1px solid #27272A}
.sidebar__user{display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem}
.sidebar__avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#F97316,#FB923C);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.8rem;flex-shrink:0}
.sidebar__user-name{font-size:0.875rem;font-weight:600;color:#FAFAFA}
.sidebar__user-role{font-size:0.75rem;color:#71717A;text-transform:capitalize}
.sidebar__logout{width:100%;padding:0.5rem;color:#71717A;font-size:0.8rem;font-weight:600;border-radius:0.5rem;transition:all 150ms ease}
.sidebar__logout:hover{color:#F43F5E;background:rgba(244,63,94,0.08)}

.main{margin-left:260px;flex:1;padding:2rem;background:#09090B;min-height:100vh}

.mobile-fab{display:none;position:fixed;bottom:1.5rem;left:1.5rem;z-index:250;width:52px;height:52px;border-radius:50%;background:#F97316;color:white;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(249,115,22,0.4)}
.mobile-fab__dots{display:grid;grid-template-columns:1fr 1fr;gap:3px}
.mobile-fab__dots span{width:5px;height:5px;background:white;border-radius:1px}
.mobile-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:150}

.page-enter-active,.page-leave-active{transition:opacity 0.2s ease}
.page-enter-from,.page-leave-to{opacity:0}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);transition:transform 0.3s ease}
  .sidebar--open{transform:translateX(0)}
  .mobile-fab{display:flex}
  .mobile-backdrop{display:block}
  .main{margin-left:0;padding:1rem}
}
</style>

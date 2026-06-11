<template>
  <div class="settings-page">
    <div class="settings-header">
      <div>
        <h1 class="settings-title">Restaurant Settings</h1>
        <p class="settings-sub">Customize how your restaurant appears to customers</p>
      </div>
      <button class="btn btn-primary" @click="saveAll" :disabled="saving" :style="saveBtnStyle">
        {{ saving ? 'Saving...' : 'Save All Changes' }}
      </button>
    </div>

    <div v-if="loading" class="settings-grid">
      <div v-for="i in 4" :key="i" class="card skeleton-card">
        <div class="skeleton skeleton-title" />
        <div class="skeleton skeleton-body" />
        <div class="skeleton skeleton-body short" />
      </div>
    </div>

    <div v-else class="settings-layout">
      <div class="settings-form">
        <!-- Restaurant Name -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">🏪</span>
            <div>
              <h2 class="card-title">Restaurant Name</h2>
              <p class="card-desc">Your restaurant's display name</p>
            </div>
          </div>
          <div class="card-body">
            <div class="field">
              <label class="field-label">Name</label>
              <input v-model="form.name" type="text" class="field-input" placeholder="e.g. The Daily Grind" maxlength="255" />
            </div>
          </div>
        </div>

        <!-- Branding -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">🎨</span>
            <div>
              <h2 class="card-title">Branding</h2>
              <p class="card-desc">Your restaurant's visual identity</p>
            </div>
          </div>
          <div class="card-body">
            <div class="logo-upload-area" @click="triggerLogoUpload" @dragover.prevent @drop.prevent="handleDrop">
              <input ref="logoInput" type="file" accept="image/*" style="display:none" @change="handleLogoFile" />
              <div v-if="form.logo_url" class="logo-preview">
                <img :src="form.logo_url" alt="Logo" />
                <button class="logo-remove" @click.stop="removeLogo">✕</button>
              </div>
              <div v-else class="logo-placeholder">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#52525B" stroke-width="1.5">
                  <rect x="3" y="3" width="18" height="18" rx="3"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <path d="M21 15l-5-5L5 21"/>
                </svg>
                <span>Click or drag to upload logo</span>
                <span class="upload-hint">PNG, JPG, WebP. Max 2MB</span>
              </div>
            </div>

            <div class="field">
              <label class="field-label">Motto / Tagline</label>
              <input v-model="form.motto" type="text" class="field-input" placeholder="e.g. Freshly brewed, always" maxlength="255" />
            </div>
          </div>
        </div>

        <!-- Brand Colors -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">🌈</span>
            <div>
              <h2 class="card-title">Brand Colors</h2>
              <p class="card-desc">Used across buttons, banners, and order screens</p>
            </div>
          </div>
          <div class="card-body">
            <div v-for="color in colorSlots" :key="color.key" class="color-slot">
              <div class="color-slot-header" @click="togglePicker(color.key)">
                <div class="color-slot-left">
                  <div class="color-dot" :style="{ background: getColorValue(color.key) || '#27272A' }" />
                  <span class="color-slot-label">{{ color.label }}</span>
                </div>
                <span class="color-slot-hex" :style="{ color: getColorValue(color.key) }">{{ getColorValue(color.key) || 'Not set' }}</span>
              </div>
              <div v-if="activeColorSlot === color.key" class="color-slot-picker">
                <ColorSpectrumPicker :modelValue="getColorValue(color.key)" @update:modelValue="setColor(color.key, $event)" />
              </div>
            </div>
          </div>
        </div>

        <!-- Today's Special -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">⭐</span>
            <div>
              <h2 class="card-title">Today's Special</h2>
              <p class="card-desc">Highlight a menu item for customers</p>
            </div>
          </div>
          <div class="card-body">
            <div class="field">
              <label class="field-label">Select Special</label>
              <select v-model="form.today_special_id" class="field-input">
                <option :value="null">No special set</option>
                <option v-for="item in menuItems" :key="item.id" :value="item.id">
                  {{ item.name }} · ${{ Number(item.price || 0).toFixed(2) }}
                </option>
              </select>
            </div>
            <div v-if="selectedSpecial" class="special-preview" :style="{ borderColor: primaryColor }">
              <div class="special-badge" :style="{ color: primaryColor }">TODAY'S SPECIAL</div>
              <div class="special-name">{{ selectedSpecial.name }}</div>
              <div class="special-price" :style="{ color: primaryColor }">${{ Number(selectedSpecial.price || 0).toFixed(2) }}</div>
            </div>
          </div>
        </div>

        <!-- Announcements -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">📣</span>
            <div>
              <h2 class="card-title">Announcements</h2>
              <p class="card-desc">Banner message shown at the top of the app</p>
            </div>
          </div>
          <div class="card-body">
            <div class="field">
              <label class="field-label">Banner Message</label>
              <textarea v-model="form.banner_message" class="field-input textarea" rows="3" placeholder="e.g. Happy hour starts at 5PM!" maxlength="1000" />
              <div class="char-count">{{ (form.banner_message || '').length }} / 1000</div>
            </div>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">📞</span>
            <div>
              <h2 class="card-title">Contact Info</h2>
              <p class="card-desc">How customers can reach you</p>
            </div>
          </div>
          <div class="card-body">
            <div class="field-row">
              <div class="field">
                <label class="field-label">Phone</label>
                <input v-model="form.phone" type="tel" class="field-input" placeholder="+1 (555) 123-4567" maxlength="30" />
              </div>
            </div>
            <div class="field">
              <label class="field-label">Address</label>
              <textarea v-model="form.address" class="field-input textarea" rows="2" placeholder="123 Main St, City, State" maxlength="500" />
            </div>
          </div>
        </div>

        <!-- Operating Hours -->
        <div class="card">
          <div class="card-header">
            <span class="card-icon">🕐</span>
            <div>
              <h2 class="card-title">Operating Hours</h2>
              <p class="card-desc">When your restaurant is open</p>
            </div>
          </div>
          <div class="card-body">
            <div v-for="day in daysOfWeek" :key="day.key" class="hours-row">
              <label class="hours-label">
                <input type="checkbox" :checked="!isDayClosed(day.key)" @change="toggleDay(day.key)" />
                {{ day.label }}
              </label>
              <div v-if="!isDayClosed(day.key)" class="hours-times">
                <input type="time" :value="getDayHours(day.key).open" @change="setDayOpen(day.key, $event.target.value)" class="time-input" />
                <span class="time-sep">to</span>
                <input type="time" :value="getDayHours(day.key).close" @change="setDayClose(day.key, $event.target.value)" class="time-input" />
              </div>
              <span v-else class="hours-closed">Closed</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Preview Panel -->
      <div class="preview-panel">
        <div class="preview-sticky">
          <h3 class="preview-heading">Customer Preview</h3>
          <div class="preview-phone">
            <div class="preview-status-bar">
              <span>9:41</span>
              <span>●●●</span>
            </div>
            <div class="preview-banner" v-if="form.banner_message" :style="{ background: primaryColor }">
              {{ form.banner_message }}
            </div>
            <div class="preview-header" :style="{ borderColor: primaryColor }">
              <div class="preview-logo-wrap" v-if="form.logo_url">
                <img :src="form.logo_url" class="preview-logo" />
              </div>
              <div v-else class="preview-logo-placeholder" :style="{ background: primaryColor + '20', color: primaryColor }">
                {{ (display_name || 'R')[0] }}
              </div>
              <div class="preview-restaurant-name">{{ display_name }}</div>
              <div class="preview-motto" v-if="form.motto">{{ form.motto }}</div>
              <div class="preview-contact" v-if="form.phone || form.address">
                <span v-if="form.phone">📞 {{ form.phone }}</span>
                <span v-if="form.address">📍 {{ form.address }}</span>
              </div>
            </div>
            <div class="preview-special" v-if="selectedSpecial" :style="{ borderColor: primaryColor }">
              <div class="preview-special-label" :style="{ color: primaryColor }">⭐ TODAY'S SPECIAL</div>
              <div class="preview-special-name">{{ selectedSpecial.name }}</div>
              <div class="preview-special-price" :style="{ color: primaryColor }">${{ Number(selectedSpecial.price || 0).toFixed(2) }}</div>
            </div>
            <div class="preview-order-btn" :style="{ background: primaryColor }">
              Place Order
            </div>
            <div class="preview-pay-btn" :style="{ background: secondaryColor, color: '#fff' }">
              Pay Now
            </div>
            <div class="preview-hours" v-if="hasOperatingHours">
              <div class="preview-hours-title">Hours</div>
              <div v-for="day in daysOfWeek" :key="day.key" class="preview-hours-row">
                <span>{{ day.label.slice(0, 3) }}</span>
                <span v-if="!isDayClosed(day.key)">{{ getDayHours(day.key).open }} – {{ getDayHours(day.key).close }}</span>
                <span v-else class="preview-closed">Closed</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from '../../composables/useToast'
import api from '../../services/api'
import ColorSpectrumPicker from '../../components/ui/ColorSpectrumPicker.vue'

const auth = useAuthStore()
const toast = useToast()

const loading = ref(true)
const saving = ref(false)
const logoInput = ref(null)
const menuItems = ref([])
const activeColorSlot = ref(null)

const form = ref({
  name: '',
  logo_url: null,
  logo_path: null,
  motto: '',
  banner_message: '',
  today_special_id: null,
  brand_colors: { primary: '#F97316', secondary: '#1E40AF', accent: '#10B981', success: '#10B981', danger: '#F43F5E' },
  phone: '',
  address: '',
  operating_hours: null,
})

const colorSlots = [
  { key: 'primary', label: 'Primary: Buttons, links, highlights' },
  { key: 'secondary', label: 'Secondary: Pay buttons, secondary actions' },
  { key: 'accent', label: 'Accent: Badges, tags, highlights' },
  { key: 'success', label: 'Success: Order confirmed, positive states' },
  { key: 'danger', label: 'Danger: Errors, delete, warnings' },
]

const primaryColor = computed(() => form.value.brand_colors?.primary || '#F97316')
const secondaryColor = computed(() => form.value.brand_colors?.secondary || '#1E40AF')
const display_name = computed(() => form.value.name || auth.user?.restaurant?.name || 'My Restaurant')

const saveBtnStyle = computed(() => ({
  background: primaryColor.value,
}))

const daysOfWeek = [
  { key: 'monday', label: 'Monday' },
  { key: 'tuesday', label: 'Tuesday' },
  { key: 'wednesday', label: 'Wednesday' },
  { key: 'thursday', label: 'Thursday' },
  { key: 'friday', label: 'Friday' },
  { key: 'saturday', label: 'Saturday' },
  { key: 'sunday', label: 'Sunday' },
]

const selectedSpecial = computed(() => {
  if (!form.value.today_special_id) return null
  return menuItems.value.find(i => i.id === form.value.today_special_id) || null
})

const hasOperatingHours = computed(() => {
  const h = form.value.operating_hours
  if (!h || typeof h !== 'object') return false
  return daysOfWeek.some(d => h[d.key] && !h[d.key].closed)
})

function getColorValue(key) {
  return form.value.brand_colors?.[key] || null
}

function setColor(key, hex) {
  if (!form.value.brand_colors) form.value.brand_colors = {}
  form.value.brand_colors[key] = hex
}

function clearColor(key) {
  if (form.value.brand_colors) form.value.brand_colors[key] = null
}

function togglePicker(key) {
  activeColorSlot.value = activeColorSlot.value === key ? null : key
}

function getDayHours(dayKey) {
  const h = form.value.operating_hours?.[dayKey]
  return { open: h?.open || '09:00', close: h?.close || '17:00' }
}

function isDayClosed(dayKey) {
  const h = form.value.operating_hours?.[dayKey]
  return !h || h.closed === true
}

function toggleDay(dayKey) {
  if (!form.value.operating_hours) form.value.operating_hours = {}
  const current = form.value.operating_hours[dayKey]
  if (current && !current.closed) {
    form.value.operating_hours[dayKey] = { ...current, closed: true }
  } else {
    form.value.operating_hours[dayKey] = { open: '09:00', close: '17:00', closed: false }
  }
}

function setDayOpen(dayKey, val) {
  if (!form.value.operating_hours) form.value.operating_hours = {}
  if (!form.value.operating_hours[dayKey]) form.value.operating_hours[dayKey] = {}
  form.value.operating_hours[dayKey].open = val
}

function setDayClose(dayKey, val) {
  if (!form.value.operating_hours) form.value.operating_hours = {}
  if (!form.value.operating_hours[dayKey]) form.value.operating_hours[dayKey] = {}
  form.value.operating_hours[dayKey].close = val
}

function triggerLogoUpload() {
  logoInput.value?.click()
}

function handleLogoFile(e) {
  const file = e.target.files?.[0]
  if (file) uploadLogo(file)
}

function handleDrop(e) {
  const file = e.dataTransfer.files?.[0]
  if (file && file.type.startsWith('image/')) uploadLogo(file)
}

async function uploadLogo(file) {
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Logo must be under 2MB')
    return
  }
  const fd = new FormData()
  fd.append('logo', file)
  try {
    const res = await api.post('/restaurant-settings/logo', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    form.value.logo_url = res.data?.data?.logo_url || res.data?.data?.logo_path
    toast.success('Logo uploaded')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Upload failed')
  }
}

function removeLogo() {
  form.value.logo_url = null
  form.value.logo_path = null
}

async function loadSettings() {
  loading.value = true
  try {
    const [settingsRes, menuRes] = await Promise.all([
      api.get('/restaurant-settings'),
      api.get('/menu_items', { params: { restaurant_id: auth.user?.restaurant_id, per_page: 200 } })
    ])
    const data = settingsRes.data?.data
    if (data) {
      let bc = data.brand_colors
      if (Array.isArray(bc)) {
        bc = {
          primary: bc[0] || '#F97316',
          secondary: bc[1] || '#1E40AF',
          accent: bc[2] || '#10B981',
          success: bc[3] || '#10B981',
          danger: bc[4] || '#F43F5E',
        }
      }
      form.value = {
        name: data.name || '',
        logo_url: data.logo_path || null,
        logo_path: data.logo_path || null,
        motto: data.motto || '',
        banner_message: data.banner_message || '',
        today_special_id: data.today_special_id || null,
        brand_colors: bc || { primary: '#F97316', secondary: '#1E40AF', accent: '#10B981', success: '#10B981', danger: '#F43F5E' },
        phone: data.phone || '',
        address: data.address || '',
        operating_hours: data.operating_hours || null,
      }
    }
    const menuRaw = menuRes.data?.data
    menuItems.value = Array.isArray(menuRaw) ? menuRaw : Array.isArray(menuRaw?.data) ? menuRaw.data : []
  } catch (e) {
    toast.error('Failed to load settings')
  }
  loading.value = false
}

async function saveAll() {
  saving.value = true
  try {
    await api.put('/restaurant-settings', {
      name: form.value.name || null,
      motto: form.value.motto || null,
      banner_message: form.value.banner_message || null,
      today_special_id: form.value.today_special_id || null,
      brand_colors: form.value.brand_colors || null,
      phone: form.value.phone || null,
      address: form.value.address || null,
      operating_hours: form.value.operating_hours || null,
    })
    if (form.value.name && form.value.name !== auth.user?.restaurant?.name) {
      auth.user.restaurant = { ...auth.user.restaurant, name: form.value.name }
    }
    toast.success('Settings saved successfully')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Failed to save')
  }
  saving.value = false
}

onMounted(loadSettings)
</script>

<style scoped>
.settings-page { max-width: 1400px; }
.settings-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
.settings-title { font-size: 1.5rem; font-weight: 800; color: #FAFAFA; }
.settings-sub { font-size: 0.875rem; color: #A1A1AA; margin-top: 0.25rem; }

.settings-layout { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; }
@media (max-width: 900px) { .settings-layout { grid-template-columns: 1fr; } }

.settings-form { display: flex; flex-direction: column; gap: 1.25rem; }

.card { background: #111113; border: 1px solid #27272A; border-radius: 1rem; overflow: hidden; }
.card-header { display: flex; align-items: center; gap: 0.875rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid #1C1C1F; }
.card-icon { font-size: 1.25rem; }
.card-title { font-size: 0.95rem; font-weight: 700; color: #FAFAFA; }
.card-desc { font-size: 0.8rem; color: #71717A; margin-top: 0.125rem; }
.card-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; }

.field-label { display: block; font-size: 0.8rem; font-weight: 600; color: #A1A1AA; margin-bottom: 0.375rem; }
.field-input { width: 100%; padding: 0.625rem 0.875rem; background: #1C1C1F; border: 1px solid #27272A; border-radius: 0.625rem; color: #FAFAFA; font-size: 0.875rem; transition: border-color 150ms ease, box-shadow 150ms ease; }
.field-input:focus { outline: none; border-color: #F97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.12); }
.field-input::placeholder { color: #52525B; }
.textarea { resize: vertical; min-height: 60px; }
.char-count { font-size: 0.75rem; color: #52525B; text-align: right; margin-top: 0.25rem; }

.logo-upload-area { border: 2px dashed #27272A; border-radius: 0.75rem; padding: 2rem; text-align: center; cursor: pointer; transition: border-color 150ms ease, background 150ms ease; }
.logo-upload-area:hover { border-color: #F97316; background: rgba(249,115,22,0.04); }
.logo-placeholder { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; color: #52525B; font-size: 0.875rem; }
.upload-hint { font-size: 0.75rem; color: #3F3F46; }
.logo-preview { position: relative; display: inline-block; }
.logo-preview img { width: 100px; height: 100px; object-fit: contain; border-radius: 0.75rem; background: #1C1C1F; }
.logo-remove { position: absolute; top: -6px; right: -6px; width: 22px; height: 22px; border-radius: 50%; background: #F43F5E; color: white; border: none; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }

.color-slot { border-bottom: 1px solid #1C1C1F; }
.color-slot:last-child { border-bottom: none; }
.color-slot-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; cursor: pointer; transition: background 150ms ease; }
.color-slot-header:hover { background: rgba(255,255,255,0.02); }
.color-slot-left { display: flex; align-items: center; gap: 0.625rem; }
.color-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #3F3F46; flex-shrink: 0; transition: transform 150ms ease; }
.color-slot-header:hover .color-dot { transform: scale(1.1); }
.color-slot-label { font-size: 0.8rem; color: #D4D4D8; font-weight: 500; }
.color-slot-hex { font-size: 0.7rem; font-family: monospace; font-weight: 600; }
.color-slot-picker { padding: 0.5rem 0 1rem; }

.special-preview { background: #1C1C1F; border: 1px solid #27272A; border-radius: 0.75rem; padding: 1rem 1.25rem; }
.special-badge { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em; margin-bottom: 0.375rem; }
.special-name { font-size: 1rem; font-weight: 700; color: #FAFAFA; }
.special-price { font-size: 1.1rem; font-weight: 800; margin-top: 0.25rem; }

.hours-row { display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #1C1C1F; }
.hours-row:last-child { border-bottom: none; }
.hours-label { display: flex; align-items: center; gap: 0.5rem; min-width: 140px; font-size: 0.875rem; color: #D4D4D8; cursor: pointer; }
.hours-label input[type="checkbox"] { accent-color: #F97316; }
.hours-times { display: flex; align-items: center; gap: 0.5rem; }
.time-input { padding: 0.375rem 0.5rem; background: #1C1C1F; border: 1px solid #27272A; border-radius: 0.375rem; color: #FAFAFA; font-size: 0.8rem; }
.time-input:focus { outline: none; border-color: #F97316; }
.time-sep { color: #52525B; font-size: 0.8rem; }
.hours-closed { color: #52525B; font-size: 0.8rem; font-style: italic; }

.btn { padding: 0.625rem 1.25rem; border-radius: 0.625rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: all 150ms ease; border: none; }
.btn-primary { color: white; }
.btn-primary:hover { box-shadow: 0 0 20px rgba(249,115,22,0.3); filter: brightness(1.1); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; filter: none; }

.skeleton-card { padding: 2rem; }
.skeleton { background: linear-gradient(90deg, #1C1C1F 25%, #27272A 50%, #1C1C1F 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 0.5rem; }
.skeleton-title { height: 20px; width: 40%; margin-bottom: 1rem; }
.skeleton-body { height: 40px; width: 100%; margin-bottom: 0.75rem; }
.skeleton-body.short { width: 60%; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* Preview Panel */
.preview-panel { position: sticky; top: 2rem; }
.preview-sticky { display: flex; flex-direction: column; gap: 0.75rem; }
.preview-heading { font-size: 0.8rem; font-weight: 700; color: #71717A; text-transform: uppercase; letter-spacing: 0.06em; padding-left: 0.5rem; }
.preview-phone { background: #09090B; border: 1px solid #27272A; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.preview-status-bar { display: flex; justify-content: space-between; padding: 0.5rem 1.25rem; font-size: 0.7rem; color: #52525B; }
.preview-banner { padding: 0.5rem 1rem; color: white; font-size: 0.75rem; font-weight: 600; text-align: center; }
.preview-header { padding: 1.25rem; text-align: center; border-bottom: 2px solid #F97316; }
.preview-logo-wrap { margin-bottom: 0.5rem; }
.preview-logo { width: 48px; height: 48px; object-fit: contain; border-radius: 0.5rem; }
.preview-logo-placeholder { width: 48px; height: 48px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 800; margin: 0 auto 0.5rem; }
.preview-restaurant-name { font-size: 1rem; font-weight: 800; color: #FAFAFA; }
.preview-motto { font-size: 0.75rem; color: #A1A1AA; margin-top: 0.25rem; }
.preview-contact { display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.5rem; font-size: 0.7rem; color: #71717A; }
.preview-special { margin: 1rem; padding: 0.75rem; border: 1px solid #F97316; border-radius: 0.75rem; background: rgba(249,115,22,0.06); }
.preview-special-label { font-size: 0.6rem; font-weight: 800; letter-spacing: 0.06em; }
.preview-special-name { font-size: 0.85rem; font-weight: 700; color: #FAFAFA; margin-top: 0.25rem; }
.preview-special-price { font-size: 0.9rem; font-weight: 800; margin-top: 0.125rem; }
.preview-order-btn { margin: 0.75rem 1rem; padding: 0.5rem; border-radius: 0.5rem; color: white; font-size: 0.75rem; font-weight: 700; text-align: center; }
.preview-pay-btn { margin: 0 1rem 0.75rem; padding: 0.5rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 700; text-align: center; }
.preview-hours { padding: 0.75rem 1.25rem 1.25rem; }
.preview-hours-title { font-size: 0.7rem; font-weight: 700; color: #52525B; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.5rem; }
.preview-hours-row { display: flex; justify-content: space-between; font-size: 0.7rem; color: #A1A1AA; padding: 0.2rem 0; }
.preview-closed { color: #52525B; font-style: italic; }
</style>

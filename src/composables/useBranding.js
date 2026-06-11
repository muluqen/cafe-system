import { ref, onMounted, watch } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/authStore'

const brandColors = ref(null)
const restaurantName = ref(null)

export function useBranding() {
  const auth = useAuthStore()

  function applyColors(colors) {
    if (!colors || typeof colors !== 'object') return
    const root = document.documentElement

    const colorMap = {
      primary: '--color-primary',
      secondary: '--color-secondary',
      accent: '--color-accent',
      success: '--color-success',
      danger: '--color-danger',
    }

    Object.entries(colorMap).forEach(([key, varName]) => {
      if (colors[key]) {
        root.style.setProperty(varName, colors[key])
        root.style.setProperty(`${varName}-light`, colors[key] + '1F')
        root.style.setProperty(`${varName}-glow`, colors[key] + '38')
        root.style.setProperty(`${varName}-subtle`, colors[key] + '14')
      }
    })

    if (colors.primary) {
      root.style.setProperty('--color-warm', colors.primary)
      root.style.setProperty('--color-border-focus', colors.primary)
      root.style.setProperty('--shadow-primary', `0 0 20px ${colors.primary}4D`)
      root.style.setProperty('--shadow-warm', `0 0 20px ${colors.primary}4D`)
    }

    if (colors.secondary) {
      root.style.setProperty('--color-cool', colors.secondary)
      root.style.setProperty('--shadow-cool', `0 0 20px ${colors.secondary}4D`)
    }

    brandColors.value = colors
  }

  async function loadBranding() {
    try {
      const res = await api.get('/restaurant-settings/public')
      const data = res.data?.data
      if (data) {
        const settings = data.settings
        if (settings?.brand_colors) applyColors(settings.brand_colors)
        if (data.restaurant?.name) {
          restaurantName.value = data.restaurant.name
          document.title = data.restaurant.name
        }
      }
    } catch (e) {
      // Silent fail - use default colors
    }
  }

  onMounted(() => {
    loadBranding()
  })

  return {
    brandColors,
    restaurantName,
    applyColors,
    loadBranding,
  }
}

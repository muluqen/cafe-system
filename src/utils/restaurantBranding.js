const DEFAULT_CONFIG = {
  headline: "",
  customerMessage: "",
  brandColor: "#ff7a59",
  brandColors: ["#ff7a59", "#ffb36c"],
  logoUrl: ""
};

export function restaurantBrandingKey(restaurantId) {
  return restaurantId ? `cafe_restaurant_site_config_${restaurantId}` : "";
}

function normalizeColors(config = {}) {
  const colors = Array.isArray(config.brandColors)
    ? config.brandColors.filter(Boolean).slice(0, 3)
    : [];

  if (config.brandColor && !colors.includes(config.brandColor)) {
    colors.unshift(config.brandColor);
  }

  const safeColors = colors.length ? colors : [...DEFAULT_CONFIG.brandColors];

  return {
    ...DEFAULT_CONFIG,
    ...config,
    brandColor: safeColors[0],
    brandColors: safeColors
  };
}

export function getRestaurantBranding(restaurantId) {
  const key = restaurantBrandingKey(restaurantId);
  if (!key) {
    return normalizeColors();
  }

  const raw = localStorage.getItem(key);
  if (!raw) {
    return normalizeColors();
  }

  try {
    return normalizeColors(JSON.parse(raw));
  } catch {
    return normalizeColors();
  }
}

export function saveRestaurantBranding(restaurantId, config) {
  const key = restaurantBrandingKey(restaurantId);
  if (!key) {
    return;
  }

  localStorage.setItem(key, JSON.stringify(normalizeColors(config)));
}

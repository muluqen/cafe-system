import { ref, watchEffect } from "vue";

const THEME_KEY = "tavliq_theme";
const theme = ref(localStorage.getItem(THEME_KEY) || "dark");

export function useTheme() {
  function applyTheme(value) {
    document.documentElement.dataset.theme = value;
  }

  function toggleTheme() {
    theme.value = theme.value === "dark" ? "light" : "dark";
  }

  watchEffect(() => {
    applyTheme(theme.value);
    localStorage.setItem(THEME_KEY, theme.value);
  });

  return {
    theme,
    toggleTheme,
  };
}

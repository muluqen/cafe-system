<template>
  <button class="theme-toggle" type="button" @click="toggleTheme">
    {{ theme === "dark" ? "Light" : "Dark" }}
  </button>
  <RouterView />
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import { RouterView } from "vue-router";

const THEME_KEY = "platrick_theme";
const theme = ref("light");

function applyTheme(value) {
  document.documentElement.dataset.theme = value;
}

function toggleTheme() {
  theme.value = theme.value === "dark" ? "light" : "dark";
}

onMounted(() => {
  const savedTheme = localStorage.getItem(THEME_KEY);
  const preferredDark =
    window.matchMedia &&
    window.matchMedia("(prefers-color-scheme: dark)").matches;

  theme.value = savedTheme || (preferredDark ? "dark" : "light");
  applyTheme(theme.value);
});

watch(theme, (value) => {
  applyTheme(value);
  localStorage.setItem(THEME_KEY, value);
});
</script>

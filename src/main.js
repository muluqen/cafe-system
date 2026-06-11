import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import "./styles/design-system.css";
import "./styles.css";
import { useAuthStore } from "./stores/authStore";
import { useBranding } from "./composables/useBranding";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const auth = useAuthStore(pinia);
auth.fetchMe().then(() => {
  const { loadBranding } = useBranding()
  loadBranding()
});

app.mount("#app");

// Fallback: force all animate-on-scroll visible after 2s
setTimeout(() => {
  document.querySelectorAll('.animate-on-scroll:not(.is-visible)')
    .forEach(el => el.classList.add('is-visible'))
}, 2000)

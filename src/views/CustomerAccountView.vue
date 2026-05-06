<template>
  <section class="customer-stack" :style="themeStyle">
    <section class="panel account-panel">
      <header class="panel-header panel-header-rich">
        <div>
          <span class="eyebrow">My Account</span>
          <h1 class="panel-title">Profile settings</h1>
        </div>
      </header>

      <div class="content-pad">
        <form class="account-form" @submit.prevent="save">
          <label>
            Full name
            <input v-model="form.name" class="input" type="text" required />
          </label>
          <label>
            Email
            <input v-model="form.email" class="input" type="email" required />
          </label>
          <label>
            New password (optional)
            <input v-model="form.password" class="input" type="password" minlength="8" />
          </label>

          <p v-if="error" class="error-text">{{ error }}</p>
          <p v-if="saved" class="muted">Profile saved.</p>

          <button class="button" :disabled="saving">
            {{ saving ? "Saving..." : "Save changes" }}
          </button>
        </form>
      </div>
    </section>
  </section>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";
import { getRestaurantBranding } from "../utils/restaurantBranding";

const auth = useAuthStore();
const saving = ref(false);
const saved = ref(false);
const error = ref("");
const restaurantBranding = computed(() => getRestaurantBranding(auth.selectedRestaurantId));
const themeStyle = computed(() => ({
  "--restaurant-brand": restaurantBranding.value.brandColor || "#1A2A40",
  "--restaurant-brand-soft": `${restaurantBranding.value.brandColor || "#1A2A40"}1f`,
  "--restaurant-brand-gradient": `linear-gradient(135deg, ${(restaurantBranding.value.brandColors || [restaurantBranding.value.brandColor || "#1A2A40"]).join(", ")})`
}));
const form = reactive({
  name: auth.user?.name || "",
  email: auth.user?.email || "",
  password: ""
});

async function save() {
  if (!auth.user?.id) {
    return;
  }

  saving.value = true;
  error.value = "";
  saved.value = false;

  try {
    const payload = {
      name: form.name,
      email: form.email
    };

    if (form.password.trim()) {
      payload.password = form.password.trim();
    }

    await api.put(`/users/${auth.user.id}`, payload);
    auth.user = { ...auth.user, name: form.name, email: form.email };
    auth.persist();
    form.password = "";
    saved.value = true;
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to update profile.";
  } finally {
    saving.value = false;
  }
}
</script>

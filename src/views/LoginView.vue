<template>
  <section class="auth-shell login-pro">
    <div class="login-grid">
      <aside class="login-showcase panel">
        <p class="eyebrow">Platrick</p>
        <h1>Find your next cafe in minutes.</h1>
        <p class="muted">
          Sign in as a customer to browse nearby restaurants, reserve a table, and order before you
          arrive.
        </p>
        <div class="login-chips">
          <span v-for="restaurant in featuredRestaurants" :key="restaurant.id" class="showcase-chip">
            {{ restaurant.name }}
          </span>
        </div>
      </aside>

      <article class="login-card panel">
        <div class="login-head">
          <span class="auth-dot" />
          <RouterLink class="staff-link" :to="{ name: 'staff-login' }">Restaurant team portal</RouterLink>
        </div>

        <h2>{{ isSignup ? "Create customer account" : "Welcome back" }}</h2>
        <p class="muted">
          {{ isSignup ? "Set up your account and start exploring restaurants." : "Log in to continue your orders and bookings." }}
        </p>

        <form class="auth-form" @submit.prevent="submit">
          <label v-if="isSignup">
            Full name
            <input v-model="form.name" class="input" type="text" required />
          </label>

          <label>
            Email
            <input v-model="form.email" class="input" type="email" required />
          </label>

          <label>
            Password
            <input v-model="form.password" class="input" type="password" minlength="8" required />
          </label>

          <p v-if="auth.error" class="error-text">{{ auth.error }}</p>

          <button class="button auth-button" type="submit" :disabled="auth.loading">
            {{ auth.loading ? "Please wait..." : isSignup ? "Create account" : "Sign in" }}
          </button>
        </form>

        <button class="text-switch" type="button" @click="toggleMode">
          {{ isSignup ? "Already have an account? Sign in" : "New to Platrick? Create account" }}
        </button>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const router = useRouter();
const isSignup = ref(false);
const form = reactive({
  name: "",
  email: "",
  password: ""
});

const featuredRestaurants = computed(() => auth.publicRestaurants.slice(0, 6));

const resetForm = () => {
  form.name = "";
  form.email = "";
  form.password = "";
  auth.error = "";
};

const toggleMode = () => {
  isSignup.value = !isSignup.value;
  resetForm();
};

const submit = async () => {
  const payload = { email: form.email, password: form.password };
  if (isSignup.value) {
    payload.name = form.name;
    await auth.register(payload);
  } else {
    await auth.login(payload);
  }
  await router.push({ name: "dashboard" });
};

onMounted(async () => {
  if (!auth.publicRestaurants.length) {
    await auth.loadPublicRestaurants();
  }
});
</script>

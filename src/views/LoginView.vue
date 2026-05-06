<template>
  <section class="auth-shell login-pro">
    <div class="login-grid">
      <aside class="login-showcase panel customer-login-showcase">
        <div class="login-showcase-top">
          <p class="eyebrow">DineDirect</p>
          <RouterLink class="staff-link showcase-staff-link" :to="{ name: 'staff-login' }">
            Restaurant team portal
          </RouterLink>
        </div>

        <div class="login-showcase-copy">
          <span class="login-kicker">Customer sign in</span>
          <h1>Find a place that fits your mood, then walk in already expected.</h1>
          <p class="muted">
            Discover nearby restaurants, save your favorites, book a table, and send your order
            ahead before you arrive.
          </p>
        </div>

        <div class="login-glance-grid">
          <article class="login-glance-card">
            <span class="auth-note-kicker">Smooth arrival</span>
            Reserve first, order early, and spend less time waiting.
          </article>
          <article class="login-glance-card">
            <span class="auth-note-kicker">Your taste remembered</span>
            Keep your go-to meals and reorder them in a few taps.
          </article>
        </div>

        <div class="login-showcase-footer">
          <div class="login-restaurant-strip">
            <span class="selector-label">Popular on DineDirect</span>
            <div class="login-chips">
              <span
                v-for="restaurant in featuredRestaurants"
                :key="restaurant.id"
                class="showcase-chip"
              >
                {{ restaurant.name }}
              </span>
            </div>
          </div>
        </div>
      </aside>

      <article class="login-card panel customer-login-card">
        <div class="login-head">
          <div class="login-head-copy">
            <span class="auth-dot" />
            <span class="login-head-label">Customer access</span>
          </div>
          <span class="login-head-status">Secure sign in</span>
        </div>

        <div class="customer-auth-intro">
          <h2>{{ isSignup ? "Create your customer account" : "Welcome back" }}</h2>
          <p class="muted">
            {{
              isSignup
                ? "Set up your profile and start choosing restaurants, tables, and orders in one place."
                : "Sign in to continue with your saved orders, bookings, and restaurant picks."
            }}
          </p>
        </div>

        <div class="customer-auth-switch">
          <button
            class="pill-btn"
            :class="{ active: !isSignup }"
            type="button"
            @click="setMode(false)"
          >
            Sign in
          </button>
          <button
            class="pill-btn"
            :class="{ active: isSignup }"
            type="button"
            @click="setMode(true)"
          >
            Create account
          </button>
        </div>

        <form class="auth-form" @submit.prevent="submit">
          <label v-if="isSignup">
            Full name
            <input v-model="form.name" class="input" type="text" placeholder="Your full name" required />
          </label>

          <label>
            Email
            <input v-model="form.email" class="input" type="email" placeholder="you@example.com" required />
          </label>

          <label>
            Password
            <input
              v-model="form.password"
              class="input"
              type="password"
              minlength="8"
              :placeholder="isSignup ? 'Create a strong password' : 'Enter your password'"
              required
            />
          </label>

          <p v-if="auth.error" class="error-text">{{ auth.error }}</p>

          <button class="button auth-button" type="submit" :disabled="auth.loading">
            {{ auth.loading ? "Please wait..." : isSignup ? "Create my account" : "Continue to DineDirect" }}
          </button>
        </form>

        <p class="text-switch-copy">
          {{ isSignup ? "Already have an account?" : "New to DineDirect?" }}
          <button class="text-switch" type="button" @click="toggleMode">
            {{ isSignup ? "Sign in instead" : "Create an account" }}
          </button>
        </p>
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

const setMode = (nextValue) => {
  isSignup.value = nextValue;
  resetForm();
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
  await auth.loadPublicRestaurants();
});
</script>

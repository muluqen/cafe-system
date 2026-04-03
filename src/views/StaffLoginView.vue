<template>
  <section class="auth-shell login-pro staff-shell">
    <div class="login-grid staff-grid join-grid">
      <aside class="login-showcase panel staff-showcase">
        <p class="eyebrow">Platrick Team</p>
        <h1>Open your restaurant workspace the right way.</h1>
        <p class="muted">
          Existing teams can sign in fast. New restaurants can join from here and instantly get a
          private operations space for menus, service, and staff.
        </p>

        <div class="auth-notes">
          <div class="auth-note">
            <span class="auth-note-kicker">Private by default</span>
            Every restaurant gets its own isolated workspace and scoped team access.
          </div>
          <div class="auth-note">
            <span class="auth-note-kicker">Owner-first setup</span>
            Joining creates the restaurant and the first manager account in one step.
          </div>
          <div class="auth-note">
            <span class="auth-note-kicker">Built for service</span>
            Orders, menus, tables, and shift tools are ready after setup.
          </div>
        </div>
      </aside>

      <article class="login-card panel">
        <div class="login-head">
          <span class="auth-dot" />
          <RouterLink class="staff-link" :to="{ name: 'login' }">Customer login</RouterLink>
        </div>

        <div class="staff-mode-switch">
          <button
            v-for="option in modeOptions"
            :key="option.value"
            class="pill-btn"
            :class="{ active: mode === option.value }"
            type="button"
            @click="setMode(option.value)"
          >
            {{ option.label }}
          </button>
        </div>

        <div v-if="mode === 'login'">
          <h2>Restaurant team sign in</h2>
          <p class="muted">Use your work account and restaurant access key to enter your portal.</p>

          <form class="auth-form" @submit.prevent="submitLogin">
            <label>
              Work email
              <input v-model="loginForm.email" class="input" type="email" required />
            </label>

            <label>
              Password
              <input v-model="loginForm.password" class="input" type="password" minlength="8" required />
            </label>

            <label>
              Restaurant access key
              <input v-model="loginForm.access_key" class="input" type="password" required />
            </label>

            <p v-if="auth.error" class="error-text">{{ auth.error }}</p>

            <button class="button auth-button" type="submit" :disabled="auth.loading">
              {{ auth.loading ? "Please wait..." : "Sign in" }}
            </button>
          </form>
        </div>

        <div v-else-if="mode === 'team'">
          <h2>Create team account</h2>
          <p class="muted">For staff joining an existing restaurant already on Platrick.</p>

          <form class="auth-form" @submit.prevent="submitTeamSignup">
            <label>
              Full name
              <input v-model="teamForm.name" class="input" type="text" required />
            </label>

            <label>
              Work email
              <input v-model="teamForm.email" class="input" type="email" required />
            </label>

            <label>
              Password
              <input v-model="teamForm.password" class="input" type="password" minlength="8" required />
            </label>

            <label>
              Restaurant access key
              <input v-model="teamForm.access_key" class="input" type="password" required />
            </label>

            <label>
              Restaurant
              <select v-model="teamForm.restaurant_id" class="input" required>
                <option value="">Select restaurant</option>
                <option v-for="restaurant in auth.publicRestaurants" :key="restaurant.id" :value="restaurant.id">
                  {{ restaurant.name }}
                </option>
              </select>
            </label>

            <p v-if="auth.error" class="error-text">{{ auth.error }}</p>

            <button class="button auth-button" type="submit" :disabled="auth.loading">
              {{ auth.loading ? "Please wait..." : "Create team account" }}
            </button>
          </form>
        </div>

        <div v-else>
          <h2>Join as a new restaurant</h2>
          <p class="muted">
            Create your restaurant and owner account together. This is the first step for a brand-new venue.
          </p>

          <form class="auth-form" @submit.prevent="submitRestaurantSignup">
            <label>
              Restaurant name
              <input v-model="restaurantForm.restaurant_name" class="input" type="text" required />
            </label>

            <label>
              Owner or manager name
              <input v-model="restaurantForm.owner_name" class="input" type="text" required />
            </label>

            <label>
              Owner work email
              <input v-model="restaurantForm.email" class="input" type="email" required />
            </label>

            <label>
              Password
              <input
                v-model="restaurantForm.password"
                class="input"
                type="password"
                minlength="8"
                required
              />
            </label>

            <label>
              Restaurant access key
              <input v-model="restaurantForm.access_key" class="input" type="password" required />
            </label>

            <label>
              Restaurant phone
              <input v-model="restaurantForm.phone" class="input" type="text" placeholder="+251..." />
            </label>

            <label>
              Restaurant email
              <input
                v-model="restaurantForm.restaurant_email"
                class="input"
                type="email"
                placeholder="Optional if same as owner email"
              />
            </label>

            <label>
              Address
              <input
                v-model="restaurantForm.address"
                class="input"
                type="text"
                placeholder="Street, area, city"
              />
            </label>

            <p v-if="auth.error" class="error-text">{{ auth.error }}</p>

            <button class="button auth-button" type="submit" :disabled="auth.loading">
              {{ auth.loading ? "Setting up..." : "Join Platrick" }}
            </button>
          </form>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const router = useRouter();
const mode = ref("login");
const modeOptions = [
  { value: "login", label: "Sign in" },
  { value: "team", label: "Team account" },
  { value: "restaurant", label: "New restaurant" }
];

const loginForm = reactive({
  email: "",
  password: "",
  access_key: ""
});

const teamForm = reactive({
  name: "",
  email: "",
  password: "",
  access_key: "",
  restaurant_id: ""
});

const restaurantForm = reactive({
  restaurant_name: "",
  owner_name: "",
  email: "",
  password: "",
  access_key: "",
  phone: "",
  restaurant_email: "",
  address: ""
});

function clearErrors() {
  auth.error = "";
}

function setMode(nextMode) {
  mode.value = nextMode;
  clearErrors();
}

async function submitLogin() {
  await auth.login({
    email: loginForm.email,
    password: loginForm.password,
    access_key: loginForm.access_key
  });

  await router.push({ name: "dashboard" });
}

async function submitTeamSignup() {
  await auth.register({
    name: teamForm.name,
    email: teamForm.email,
    password: teamForm.password,
    access_key: teamForm.access_key,
    restaurant_id: Number(teamForm.restaurant_id)
  });

  await router.push({ name: "dashboard" });
}

async function submitRestaurantSignup() {
  await auth.registerRestaurant({
    restaurant_name: restaurantForm.restaurant_name,
    owner_name: restaurantForm.owner_name,
    email: restaurantForm.email,
    password: restaurantForm.password,
    access_key: restaurantForm.access_key,
    phone: restaurantForm.phone || null,
    restaurant_email: restaurantForm.restaurant_email || null,
    address: restaurantForm.address || null
  });

  await router.push({ name: "dashboard" });
}

onMounted(async () => {
  await auth.loadPublicRestaurants();
});
</script>

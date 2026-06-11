<template>
  <div class="login-page">
    <div class="bg-layer">
      <div class="orb orb--1" />
      <div class="orb orb--2" />
    </div>

    <div class="login-container">
      <div class="glass-card">
        <div class="glass-header">
          <div class="brand-mark">
            <span class="brand-emoji">☕</span>
          </div>
          <h1 class="glass-title">Tavliq</h1>
          <div class="badge">Staff</div>
          <p class="glass-subtitle">
            {{ mode === "login" ? "Team sign in" : mode === "team" ? "Join your team" : "Register your restaurant" }}
          </p>
        </div>

        <div class="tab-bar" role="tablist">
          <button v-for="opt in modeOptions" :key="opt.value" role="tab" :aria-selected="mode === opt.value"
            class="tab" :class="{ active: mode === opt.value }"
            @click="setMode(opt.value)">{{ opt.label }}</button>
        </div>

        <Transition name="fade" mode="out-in">
          <form key="login" v-if="mode === 'login'" class="glass-form" @submit.prevent="submitLogin" novalidate>
            <div class="field" :class="{ filled: loginForm.email, error: formErrors.email }">
              <input id="li-email" v-model="loginForm.email" class="field-input" type="email" autocomplete="email" @input="formErrors.email = ''" @blur="touch('email')" />
              <label for="li-email" class="field-label">Work email</label>
              <span v-if="formErrors.email" class="field-err">{{ formErrors.email }}</span>
            </div>

            <div class="field" :class="{ filled: loginForm.password, error: formErrors.password }">
              <input id="li-pass" v-model="loginForm.password" class="field-input" :type="showPass ? 'text' : 'password'" autocomplete="current-password" @input="formErrors.password = ''" @blur="touch('password')" />
              <label for="li-pass" class="field-label">Password</label>
              <button class="password-toggle" type="button" @click="showPass = !showPass" :aria-label="showPass ? 'Hide' : 'Show'" tabindex="-1">
                <svg v-if="!showPass" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
              <span v-if="formErrors.password" class="field-err">{{ formErrors.password }}</span>
            </div>

            <div class="field" :class="{ filled: loginForm.access_key, error: formErrors.access_key }">
              <input id="li-ak" v-model="loginForm.access_key" class="field-input" type="password" autocomplete="off" placeholder="Enter your restaurant access key" @input="formErrors.access_key = ''" @blur="touch('access_key')" />
              <label for="li-ak" class="field-label">Restaurant Access Key</label>
              <span v-if="formErrors.access_key" class="field-err">{{ formErrors.access_key }}</span>
            </div>

            <p v-if="auth.error" class="error-msg" role="alert">{{ auth.error }}</p>
            <button class="glass-btn" type="submit" :disabled="auth.loading">
              <span v-if="auth.loading" class="spinner" />
              <span v-else>Sign in</span>
            </button>
          </form>

          <form key="team" v-else-if="mode === 'team'" class="glass-form" @submit.prevent="submitTeamSignup" novalidate>
            <div class="field" :class="{ filled: teamForm.name }">
              <input id="tm-name" v-model="teamForm.name" class="field-input" type="text" autocomplete="name" />
              <label for="tm-name" class="field-label">Full name</label>
            </div>
            <div class="field" :class="{ filled: teamForm.email }">
              <input id="tm-email" v-model="teamForm.email" class="field-input" type="email" autocomplete="email" />
              <label for="tm-email" class="field-label">Work email</label>
            </div>
            <div class="field" :class="{ filled: teamForm.password }">
              <input id="tm-pass" v-model="teamForm.password" class="field-input" :type="showPass ? 'text' : 'password'" autocomplete="new-password" />
              <label for="tm-pass" class="field-label">Password</label>
              <button class="password-toggle" type="button" @click="showPass = !showPass" :aria-label="showPass ? 'Hide' : 'Show'" tabindex="-1">
                <svg v-if="!showPass" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <div class="field" :class="{ filled: teamForm.access_key }">
              <input id="tm-ak" v-model="teamForm.access_key" class="field-input" type="password" autocomplete="off" />
              <label for="tm-ak" class="field-label">Access key</label>
            </div>
            <div class="field" :class="{ filled: teamForm.restaurant_id }">
              <select id="tm-rest" v-model="teamForm.restaurant_id" class="field-input field-select" required>
                <option value="" disabled></option>
                <option v-for="r in auth.publicRestaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
              <label for="tm-rest" class="field-label">Restaurant</label>
            </div>
            <p v-if="auth.error" class="error-msg" role="alert">{{ auth.error }}</p>
            <button class="glass-btn" type="submit" :disabled="auth.loading">
              <span v-if="auth.loading" class="spinner" />
              <span v-else>Create account</span>
            </button>
          </form>

          <form key="restaurant" v-else class="glass-form" @submit.prevent="submitRestaurantSignup" novalidate>
            <div class="field" :class="{ filled: restaurantForm.restaurant_name }">
              <input id="rn-name" v-model="restaurantForm.restaurant_name" class="field-input" type="text" />
              <label for="rn-name" class="field-label">Restaurant name</label>
            </div>
            <div class="field" :class="{ filled: restaurantForm.owner_name }">
              <input id="rn-owner" v-model="restaurantForm.owner_name" class="field-input" type="text" />
              <label for="rn-owner" class="field-label">Your name</label>
            </div>
            <div class="field" :class="{ filled: restaurantForm.email }">
              <input id="rn-email" v-model="restaurantForm.email" class="field-input" type="email" autocomplete="email" />
              <label for="rn-email" class="field-label">Email</label>
            </div>
            <div class="field" :class="{ filled: restaurantForm.password }">
              <input id="rn-pass" v-model="restaurantForm.password" class="field-input" :type="showPass ? 'text' : 'password'" autocomplete="new-password" />
              <label for="rn-pass" class="field-label">Password</label>
              <button class="password-toggle" type="button" @click="showPass = !showPass" :aria-label="showPass ? 'Hide' : 'Show'" tabindex="-1">
                <svg v-if="!showPass" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <div class="field" :class="{ filled: restaurantForm.access_key }">
              <input id="rn-ak" v-model="restaurantForm.access_key" class="field-input" type="password" autocomplete="off" />
              <label for="rn-ak" class="field-label">Access key</label>
            </div>
            <div class="field-row">
              <div class="field flex" :class="{ filled: restaurantForm.phone }">
                <input id="rn-phone" v-model="restaurantForm.phone" class="field-input" type="text" />
                <label for="rn-phone" class="field-label">Phone</label>
              </div>
              <div class="field flex" :class="{ filled: restaurantForm.restaurant_email }">
                <input id="rn-r-email" v-model="restaurantForm.restaurant_email" class="field-input" type="email" />
                <label for="rn-r-email" class="field-label">Rest. email</label>
              </div>
            </div>
            <div class="field" :class="{ filled: restaurantForm.address }">
              <input id="rn-addr" v-model="restaurantForm.address" class="field-input" type="text" />
              <label for="rn-addr" class="field-label">Address</label>
            </div>
            <p v-if="auth.error" class="error-msg" role="alert">{{ auth.error }}</p>
            <button class="glass-btn" type="submit" :disabled="auth.loading">
              <span v-if="auth.loading" class="spinner" />
              <span v-else>Register restaurant</span>
            </button>
          </form>
        </Transition>

        <div class="glass-footer">
          <RouterLink class="staff-link" :to="{ name: 'login' }">Customer login</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onMounted, reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";

const auth = useAuthStore();
const router = useRouter();
const mode = ref("login");
const showPass = ref(false);

const modeOptions = [
  { value: "login", label: "Sign in" },
  { value: "team", label: "Team" },
  { value: "restaurant", label: "New venue" }
];

const loginForm = reactive({ email: "", password: "", access_key: "" });
const teamForm = reactive({ name: "", email: "", password: "", access_key: "", restaurant_id: "" });
const restaurantForm = reactive({
  restaurant_name: "", owner_name: "", email: "", password: "",
  access_key: "", phone: "", restaurant_email: "", address: ""
});

const formErrors = reactive({ email: "", password: "", access_key: "" });
const touched = reactive({ email: false, password: false, access_key: false });

function touch(field) {
  touched[field] = true;
  validate(field);
}

function validate(field) {
  if (!touched[field]) return;
  const v = field === "email" ? loginForm.email : field === "password" ? loginForm.password : loginForm.access_key;
  if (!v) { formErrors[field] = "Required"; return; }
  if (field === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { formErrors[field] = "Invalid email"; return; }
  if (field === "password" && v.length < 6) { formErrors[field] = "Min 6 characters"; return; }
  if (field === "access_key" && !v) {
    formErrors[field] = "Required";
    return;
  }
  formErrors[field] = "";
}

function clearErrors() { auth.error = ""; Object.keys(formErrors).forEach(k => formErrors[k] = ""); Object.keys(touched).forEach(k => touched[k] = false); }

function setMode(val) { mode.value = val; clearErrors(); nextTick(() => document.querySelector(".field-input")?.focus()); }

async function submitLogin() {
  Object.keys(touched).forEach(k => touch(k));
  if (Object.values(formErrors).some(Boolean)) return;
  await auth.login({ email: loginForm.email.trim(), password: loginForm.password, access_key: loginForm.access_key.trim() });
}

async function submitTeamSignup() {
  await auth.register({ name: teamForm.name, email: teamForm.email.trim(), password: teamForm.password, access_key: teamForm.access_key.trim(), restaurant_id: Number(teamForm.restaurant_id) });
  await router.push({ name: "dashboard" });
}

async function submitRestaurantSignup() {
  const name = restaurantForm.restaurant_name;
  await auth.registerRestaurant({
    restaurant_name: name, owner_name: restaurantForm.owner_name,
    email: restaurantForm.email.trim(), password: restaurantForm.password,
    access_key: restaurantForm.access_key.trim(), phone: restaurantForm.phone || null,
    restaurant_email: restaurantForm.restaurant_email.trim() || null, address: restaurantForm.address || null
  });
  auth.setRestaurantWelcomeNotice(`Welcome. ${name} is ready.`);
  await router.push({ name: "dashboard" });
}

onMounted(async () => {
  await auth.loadPublicRestaurants();
  document.getElementById("li-email")?.focus();
});
</script>

<style scoped>
.login-page {
  position: relative;
  min-height: 100svh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #09090B;
  overflow: hidden;
  padding: 24px;
  --color-bg: #09090B;
  --color-bg-elevated: #111113;
  --color-bg-overlay: #18181B;
  --color-bg-subtle: #1C1C1F;
  --color-border: #27272A;
  --color-text-primary: #FAFAFA;
  --color-text-secondary: #A1A1AA;
  --color-text-muted: #52525B;
}

.bg-layer {
  position: fixed;
  inset: 0;
  pointer-events: none;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: float 8s ease-in-out infinite;
}

.orb--1 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(249, 115, 22, 0.10), transparent 70%);
  top: -100px; left: -100px;
  animation-duration: 8s;
}

.orb--2 {
  width: 350px; height: 350px;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.08), transparent 70%);
  bottom: -100px; right: -100px;
  animation-duration: 10s;
  animation-direction: reverse;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}

.login-container {
  width: 100%;
  max-width: 440px;
  position: relative;
}

.glass-card {
  background: #111113;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-2xl);
  padding: var(--space-10);
  box-shadow: var(--shadow-xl), 0 0 60px rgba(249,115,22,0.06);
  animation: cardIn 0.5s ease forwards;
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(16px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.glass-header {
  text-align: center;
  margin-bottom: 24px;
}

.brand-mark {
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  border-radius: var(--radius-xl);
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-primary);
}

.brand-emoji { font-size: 1.5rem; }

.glass-title {
  margin: 0;
  font-weight: 800;
  font-size: var(--text-2xl);
  color: white;
  letter-spacing: -0.03em;
}

.glass-title::after {
  content: '';
  display: block;
  width: 32px;
  height: 2px;
  background: var(--color-warm);
  margin: var(--space-2) auto 0;
  border-radius: var(--radius-full);
}

.badge {
  display: inline-block;
  margin-top: 6px;
  font-size: 0.55rem;
  font-weight: var(--font-extrabold);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  padding: 3px 10px;
  border-radius: var(--radius-full);
  background: var(--color-bg-subtle);
  color: var(--color-accent);
  border: 1px solid var(--color-border);
}

.glass-subtitle {
  margin: 10px 0 0;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.tab-bar {
  display: flex;
  gap: 4px;
  padding: 4px;
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  margin-bottom: 24px;
}

.tab {
  flex: 1;
  padding: 10px;
  border: none;
  border-radius: var(--radius-sm);
  font: inherit;
  font-weight: var(--font-semibold);
  font-size: 0.8rem;
  cursor: pointer;
  color: var(--color-text-muted);
  background: transparent;
  transition: all var(--transition-base);
}

.tab.active {
  background: var(--color-bg-overlay);
  color: var(--color-text-primary);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
}

.tab:hover:not(.active) { color: var(--color-text-secondary); }

.glass-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field { position: relative; }

.field-input {
  width: 100%;
  padding: 18px 14px 6px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font: inherit;
  font-size: 0.875rem;
  transition: border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
  outline: none;
  box-sizing: border-box;
}

.field-input:focus {
  border-color: var(--color-warm);
  background: var(--color-bg-overlay);
  box-shadow: 0 0 0 3px var(--color-warm-glow);
}

.field.error .field-input { border-color: var(--color-danger); }
.field-input::placeholder { color: transparent; }

.field-select {
  cursor: pointer;
  appearance: auto;
}

.field-select option {
  background: var(--color-bg-overlay);
  color: var(--color-text-primary);
}

.field-label {
  position: absolute;
  left: 14px;
  top: 14px;
  font-size: 0.85rem;
  color: var(--color-text-muted);
  pointer-events: none;
  transform-origin: left center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.field-input:focus ~ .field-label,
.field.filled .field-label {
  top: 6px;
  font-size: 0.62rem;
  color: var(--color-text-secondary);
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.field-input:focus ~ .field-label { color: var(--color-primary-light); }

.field-err {
  display: block;
  margin-top: 4px;
  font-size: 0.7rem;
  color: var(--color-danger);
  padding-left: 2px;
}

.password-toggle {
  position: absolute;
  right: 10px;
  bottom: 8px;
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: var(--radius-sm);
  transition: color 0.2s ease;
}

.password-toggle:hover { color: var(--color-text-secondary); }

.field-row {
  display: flex;
  gap: 12px;
}

.flex { flex: 1; }

.glass-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--radius-md);
  background: var(--color-warm);
  color: white;
  font: inherit;
  font-weight: 800;
  font-size: 0.925rem;
  width: 100%;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.25s ease, opacity 0.2s ease;
  position: relative;
  overflow: hidden;
}

.glass-btn::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.12) 50%, transparent 60%);
  transform: translateX(-100%);
  transition: transform 0.5s ease;
}

.glass-btn:hover:not(:disabled)::after { transform: translateX(100%); }

.glass-btn:hover:not(:disabled) {
  transform: translateY(-1px) scale(1.01);
  box-shadow: var(--shadow-warm-lg);
}

.glass-btn:active:not(:disabled) { transform: translateY(0); transition-duration: 0.05s; }
.glass-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.25);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.error-msg {
  margin: 0;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: var(--color-danger-light);
  border: 1px solid rgba(244, 63, 94, 0.2);
  color: var(--color-danger);
  font-size: 0.825rem;
  font-weight: var(--font-medium);
  animation: shake 0.35s ease;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  50% { transform: translateX(4px); }
  75% { transform: translateX(-2px); }
}

.glass-footer {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
  text-align: center;
}

.staff-link {
  color: var(--color-text-muted);
  font-size: 0.78rem;
  font-weight: var(--font-medium);
  text-decoration: none;
  transition: color 0.2s ease;
}

.staff-link:hover { color: var(--color-text-secondary); }

.fade-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-enter-from { opacity: 0; transform: translateY(6px); }
.fade-leave-to { opacity: 0; transform: translateY(-4px); }

@media (max-width: 480px) {
  .glass-card { padding: var(--space-7) var(--space-5) var(--space-5); }
  .login-page { padding: 16px; }
  .field-row { flex-direction: column; gap: 16px; }
}
</style>

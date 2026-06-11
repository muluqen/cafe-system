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
          <p class="glass-subtitle">{{ isSignup ? "Create your customer account" : "Welcome back" }}</p>
        </div>

        <div class="tab-bar" role="tablist">
          <button role="tab" :aria-selected="!isSignup" class="tab" :class="{ active: !isSignup }" @click="setMode(false)">Sign in</button>
          <button role="tab" :aria-selected="isSignup" class="tab" :class="{ active: isSignup }" @click="setMode(true)">Register</button>
        </div>

        <Transition name="fade" mode="out-in">
          <form key="signin" v-if="!isSignup" class="glass-form" @submit.prevent="submit" novalidate>
            <div class="field" :class="{ filled: form.email, error: errors.email }">
              <input id="si-email" v-model="form.email" class="field-input" type="email" autocomplete="email" ref="emailInput" @input="errors.email = ''" @blur="validateEmail('si-email')" />
              <label for="si-email" class="field-label">Email</label>
              <span v-if="errors.email" class="field-err">{{ errors.email }}</span>
            </div>

            <div class="field" :class="{ filled: form.password, error: errors.password }">
              <input :id="'si-pass'" v-model="form.password" class="field-input" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" @input="errors.password = ''" @blur="validatePassword('si-pass')" />
              <label :for="'si-pass'" class="field-label">Password</label>
              <button class="password-toggle" type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" tabindex="-1">
                <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
              <span v-if="errors.password" class="field-err">{{ errors.password }}</span>
            </div>

            <div class="field-actions">
              <label class="checkbox">
                <input type="checkbox" v-model="form.remember" />
                <span>Remember me</span>
              </label>
            </div>

            <p v-if="auth.error" class="error-msg" role="alert">{{ auth.error }}</p>
            <button class="glass-btn" type="submit" :disabled="auth.loading">
              <span v-if="auth.loading" class="spinner" />
              <span v-else>Sign in</span>
            </button>
          </form>

          <form key="signup" v-else class="glass-form" @submit.prevent="submit" novalidate>
            <div class="field" :class="{ filled: form.name, error: errors.name }">
              <input id="su-name" v-model="form.name" class="field-input" type="text" autocomplete="name" @input="errors.name = ''" @blur="validateName" />
              <label for="su-name" class="field-label">Full name</label>
              <span v-if="errors.name" class="field-err">{{ errors.name }}</span>
            </div>

            <div class="field" :class="{ filled: form.email, error: errors.email }">
              <input id="su-email" v-model="form.email" class="field-input" type="email" autocomplete="email" @input="errors.email = ''" @blur="validateEmail('su-email')" />
              <label for="su-email" class="field-label">Email</label>
              <span v-if="errors.email" class="field-err">{{ errors.email }}</span>
            </div>

            <div class="field" :class="{ filled: form.password, error: errors.password }">
              <input id="su-pass" v-model="form.password" class="field-input" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" @input="errors.password = ''" @blur="validatePassword('su-pass')" />
              <label for="su-pass" class="field-label">Password</label>
              <button class="password-toggle" type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" tabindex="-1">
                <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
              <span v-if="errors.password" class="field-err">{{ errors.password }}</span>
              <div v-if="form.password && !errors.password" class="password-strength" :class="passwordStrengthClass">
                <div class="strength-bar"><span :style="{ width: passwordStrengthPercent + '%' }" /></div>
                <span class="strength-label">{{ passwordStrengthLabel }}</span>
              </div>
            </div>

            <p v-if="auth.error" class="error-msg" role="alert">{{ auth.error }}</p>
            <button class="glass-btn" type="submit" :disabled="auth.loading || !!errors.password">
              <span v-if="auth.loading" class="spinner" />
              <span v-else>Create account</span>
            </button>
          </form>
        </Transition>

        <div class="glass-footer">
          <p class="switch-text">
            {{ isSignup ? "Already have an account?" : "New to Tavliq?" }}
            <button class="link" @click="toggleMode">{{ isSignup ? "Sign in" : "Create an account" }}</button>
          </p>
          <RouterLink class="staff-link" :to="{ name: 'staff-login' }">Restaurant staff? Sign in here</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";
import { useToast } from "../composables/useToast";

const auth = useAuthStore();
const toast = useToast();
const router = useRouter();
const emailInput = ref(null);
const showPassword = ref(false);
const isSignup = ref(false);

const form = reactive({ name: "", email: "", password: "", remember: false });
const errors = reactive({ name: "", email: "", password: "" });

const passwordStrength = computed(() => {
  const v = form.password;
  if (!v) return 0;
  let score = 0;
  if (v.length >= 6) score += 25;
  if (v.length >= 10) score += 25;
  if (/[A-Z]/.test(v)) score += 15;
  if (/[0-9]/.test(v)) score += 15;
  if (/[^A-Za-z0-9]/.test(v)) score += 20;
  return Math.min(score, 100);
});

const passwordStrengthPercent = computed(() => passwordStrength.value);

const passwordStrengthClass = computed(() => {
  const s = passwordStrength.value;
  if (s < 35) return "weak";
  if (s < 65) return "medium";
  return "strong";
});

const passwordStrengthLabel = computed(() => {
  const s = passwordStrength.value;
  if (s < 35) return "Weak";
  if (s < 65) return "Medium";
  return "Strong";
});

const validateEmail = (id) => {
  const el = document.getElementById(id);
  if (!el?.value) { errors.email = "Email is required"; return false; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) { errors.email = "Enter a valid email address"; return false; }
  errors.email = "";
  return true;
};

const validatePassword = (id) => {
  const el = document.getElementById(id);
  if (!el?.value) { errors.password = "Password is required"; return false; }
  if (el.value.length < 6) { errors.password = "Must be at least 6 characters"; return false; }
  errors.password = "";
  return true;
};

const validateName = () => {
  if (!form.name?.trim()) { errors.name = "Name is required"; return false; }
  errors.name = "";
  return true;
};

const validate = () => {
  const ok = isSignup.value ? validateName() : true;
  return validateEmail(isSignup.value ? "su-email" : "si-email") && validatePassword(isSignup.value ? "su-pass" : "si-pass") && ok;
};

const resetForm = () => {
  form.name = ""; form.email = ""; form.password = ""; form.remember = false;
  errors.name = ""; errors.email = ""; errors.password = "";
  auth.error = "";
};

const setMode = (val) => { isSignup.value = val; resetForm(); nextTick(() => focusFirst()); };
const toggleMode = () => { isSignup.value = !isSignup.value; resetForm(); nextTick(() => focusFirst()); };

const focusFirst = () => {
  const id = isSignup.value ? "su-name" : "si-email";
  document.getElementById(id)?.focus();
};

const submit = async () => {
  if (!validate()) return;
  auth.error = "";
  try {
    const payload = { email: form.email.trim(), password: form.password };
    if (isSignup.value) { payload.name = form.name.trim(); await auth.register(payload); toast.success("Welcome to Tavliq! Your account has been created."); }
    else { await auth.login(payload); }
    // Redirect is handled by authStore.submitAuth()
  } catch (error) {
    const status = error.response?.status
    const data = error.response?.data

    if (status === 422) {
      const errors = data?.errors
      if (errors?.email) {
        formErrors.email = 'No account found with this email. Please register first.'
      } else if (errors?.password) {
        formErrors.password = 'Incorrect password. Please try again.'
      } else if (data?.message?.toLowerCase().includes('password')) {
        formErrors.password = 'Incorrect password. Please try again.'
      } else if (data?.message?.toLowerCase().includes('email')) {
        formErrors.email = 'No account found with this email. Please register first.'
      } else {
        formErrors.general = 'Please check your details and try again.'
      }
    } else if (status === 401) {
      formErrors.general = 'Wrong email or password. Please try again.'
    } else if (status === 403) {
      formErrors.general = 'Your restaurant is pending approval or has been suspended. Please contact support.'
    } else if (status === 404) {
      formErrors.general = 'No account found. Please register first.'
    } else if (status === 429) {
      formErrors.general = 'Too many attempts. Please wait a few minutes and try again.'
    } else if (!navigator.onLine) {
      formErrors.general = 'No internet connection. Please check your network.'
    } else {
      formErrors.general = 'Something went wrong. Please try again.'
    }
  }
};

onMounted(async () => {
  await auth.loadPublicRestaurants();
  focusFirst();
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
  max-width: 420px;
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
  margin-bottom: 28px;
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

.brand-emoji {
  font-size: 1.5rem;
}

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

.glass-subtitle {
  margin: 6px 0 0;
  color: var(--color-text-muted);
  font-size: 0.875rem;
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
  font-size: 0.825rem;
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

.tab:hover:not(.active) {
  color: var(--color-text-secondary);
}

.glass-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.field {
  position: relative;
}

.field-input {
  width: 100%;
  padding: 18px 14px 6px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font: inherit;
  font-size: 0.9rem;
  transition: border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
  outline: none;
  box-sizing: border-box;
}

.field-input:focus {
  border-color: var(--color-warm);
  background: var(--color-bg-overlay);
  box-shadow: 0 0 0 3px var(--color-warm-glow);
}

.field.error .field-input {
  border-color: var(--color-danger);
}

.field-input::placeholder { color: transparent; }

.field-label {
  position: absolute;
  left: 14px;
  top: 14px;
  font-size: 0.875rem;
  color: var(--color-text-muted);
  pointer-events: none;
  transform-origin: left center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.field-input:focus ~ .field-label,
.field.filled .field-label {
  top: 6px;
  font-size: 0.65rem;
  color: var(--color-text-secondary);
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.field-input:focus ~ .field-label {
  color: var(--color-primary-light);
}

.field-err {
  display: block;
  margin-top: 4px;
  font-size: 0.72rem;
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

.password-strength {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.strength-bar {
  flex: 1;
  height: 3px;
  border-radius: var(--radius-full);
  background: var(--color-bg-muted);
  overflow: hidden;
}

.strength-bar span {
  display: block;
  height: 100%;
  border-radius: var(--radius-full);
  transition: width 0.3s ease, background 0.3s ease;
}

.weak .strength-bar span { background: var(--color-danger); width: 33% !important; }
.medium .strength-bar span { background: var(--color-warning); width: 66% !important; }
.strong .strength-bar span { background: var(--color-success); width: 100% !important; }

.strength-label {
  font-size: 0.65rem;
  font-weight: var(--font-semibold);
  min-width: 44px;
  text-align: right;
}

.weak .strength-label { color: var(--color-danger); }
.medium .strength-label { color: var(--color-warning); }
.strong .strength-label { color: var(--color-success); }

.field-actions {
  display: flex;
  align-items: center;
  margin-top: -4px;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
  cursor: pointer;
  user-select: none;
}

.checkbox input {
  width: 16px;
  height: 16px;
  accent-color: var(--color-warm);
  margin: 0;
}

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
  font-size: 0.95rem;
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

.switch-text {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.825rem;
}

.link {
  border: none;
  background: none;
  padding: 0;
  font: inherit;
  font-weight: var(--font-bold);
  color: var(--color-warm);
  cursor: pointer;
  transition: color 0.2s ease;
}

.link:hover { color: var(--color-warm-light); }

.staff-link {
  display: inline-block;
  margin-top: 12px;
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
  .glass-card { padding: var(--space-8) var(--space-5) var(--space-5); }
  .login-page { padding: 16px; }
}
</style>

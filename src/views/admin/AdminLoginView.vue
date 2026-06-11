<template>
  <div class="admin-login">
    <div class="admin-login__bg">
      <div class="admin-login__orb admin-login__orb--1" />
      <div class="admin-login__orb admin-login__orb--2" />
    </div>

    <div class="admin-login__card">
      <div class="admin-login__header">
        <div class="admin-login__logo">⚡</div>
        <h1 class="admin-login__title">Tavliq</h1>
        <p class="admin-login__subtitle">Admin Portal</p>
      </div>

      <form class="admin-login__form" @submit.prevent="handleLogin">
        <BaseInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="admin@tavliq.com"
          :error="errors.email"
          :disabled="loading"
        />
        <BaseInput
          v-model="form.password"
          label="Password"
          type="password"
          placeholder="Enter your password"
          :error="errors.password"
          :disabled="loading"
        />

        <p v-if="error" class="admin-login__error">{{ error }}</p>

        <BaseButton
          type="submit"
          variant="primary"
          :loading="loading"
          :disabled="loading"
          style="width: 100%"
        >
          Sign In
        </BaseButton>
      </form>

      <div class="admin-login__footer">
        <RouterLink to="/staff/login" class="admin-login__link">
          Staff login →
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/authStore";
import BaseInput from "../../components/ui/BaseInput.vue";
import BaseButton from "../../components/ui/BaseButton.vue";

const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const error = ref("");
const form = reactive({ email: "", password: "" });
const errors = reactive({ email: "", password: "" });

async function handleLogin() {
  errors.email = "";
  errors.password = "";
  error.value = "";

  if (!form.email) { errors.email = "Email is required"; return; }
  if (!form.password) { errors.password = "Password is required"; return; }

  loading.value = true;
  try {
    await auth.login({ email: form.email.trim(), password: form.password });
    // Manually redirect after successful login
    if (auth.user?.is_super_admin === true) {
      router.push('/admin/dashboard');
    } else {
      error.value = "This login is for administrators only.";
      auth.logoutLocal();
    }
  } catch (e) {
    error.value = auth.error || "Login failed";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.admin-login {
  position: relative;
  min-height: 100svh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #09090B;
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

.admin-login__bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
}

.admin-login__orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: float 8s ease-in-out infinite;
}

.admin-login__orb--1 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(249, 115, 22, 0.10), transparent 70%);
  top: -100px; left: -100px;
}

.admin-login__orb--2 {
  width: 350px; height: 350px;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.08), transparent 70%);
  bottom: -100px; right: -100px;
  animation-direction: reverse;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}

.admin-login__card {
  width: 100%;
  max-width: 400px;
  background: #111113;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-2xl);
  padding: var(--space-10);
  box-shadow: var(--shadow-xl), 0 0 60px rgba(249,115,22,0.06);
  position: relative;
  animation: cardIn 0.5s ease forwards;
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(16px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.admin-login__header {
  text-align: center;
  margin-bottom: var(--space-8);
}

.admin-login__logo {
  font-size: 2.5rem;
  margin-bottom: var(--space-3);
}

.admin-login__title {
  margin: 0;
  font-size: var(--text-2xl);
  font-weight: 800;
  color: white;
}

.admin-login__title::after {
  content: '';
  display: block;
  width: 32px;
  height: 2px;
  background: var(--color-cool);
  margin: var(--space-2) auto 0;
  border-radius: var(--radius-full);
}

.admin-login__subtitle {
  margin: var(--space-1) 0 0;
  font-size: var(--text-sm);
  color: var(--color-cool);
  font-weight: var(--font-semibold);
}

.admin-login__form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.admin-login__error {
  margin: 0;
  padding: var(--space-3);
  border-radius: var(--radius-md);
  background: var(--color-danger-light);
  color: var(--color-danger);
  font-size: var(--text-sm);
  text-align: center;
}

.admin-login__footer {
  margin-top: var(--space-6);
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border);
  text-align: center;
}

.admin-login__link {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.admin-login__link:hover {
  color: var(--color-text-secondary);
}
</style>

<template>
  <div class="register-page">
    <div class="register-page__bg">
      <div class="register-page__orb register-page__orb--1" />
      <div class="register-page__orb register-page__orb--2" />
    </div>

    <div class="register-card">
      <!-- Success State -->
      <div v-if="submitted" class="register-success">
        <div class="register-success__icon">🎉</div>
        <h2 class="register-success__title">Application Submitted!</h2>
        <p class="register-success__text">
          We'll review your restaurant within 24 hours.
          You'll receive an email once approved.
        </p>
        <RouterLink to="/login" class="register-success__link">Go to Login</RouterLink>
      </div>

      <!-- Registration Form -->
      <template v-else>
        <div class="register-header">
          <div class="register-logo">☕</div>
          <h1 class="register-title">Register Your Restaurant</h1>
          <p class="register-subtitle">Join Tavliq and start managing your cafe smarter</p>
        </div>

        <!-- Progress -->
        <div class="progress">
          <div v-for="s in 3" :key="s" :class="['progress__step', { 'progress__step--active': step >= s, 'progress__step--current': step === s }]">
            <span class="progress__number">{{ s }}</span>
            <span class="progress__label">{{ ['Account', 'Restaurant', 'Review'][s - 1] }}</span>
          </div>
        </div>

        <form class="register-form" @submit.prevent="handleSubmit">
          <!-- Step 1 -->
          <div v-if="step === 1" class="form-step">
            <BaseInput v-model="form.owner_name" label="Full Name" placeholder="John Doe" :error="errors.owner_name" />
            <BaseInput v-model="form.email" label="Email" type="email" placeholder="you@example.com" :error="errors.email" />
            <BaseInput v-model="form.password" label="Password" type="password" placeholder="Min 8 characters" :error="errors.password" />
            <BaseInput v-model="form.confirmPassword" label="Confirm Password" type="password" placeholder="Repeat password" :error="errors.confirmPassword" />
          </div>

          <!-- Step 2 -->
          <div v-if="step === 2" class="form-step">
            <BaseInput v-model="form.restaurant_name" label="Restaurant Name" placeholder="My Cafe" :error="errors.restaurant_name" />
            <div class="form-field">
              <label class="form-label">Cuisine Type</label>
              <select v-model="form.cuisine_type" class="form-select">
                <option value="">Select cuisine</option>
                <option value="Coffee & Cafe">Coffee & Cafe</option>
                <option value="Fast Food">Fast Food</option>
                <option value="Fine Dining">Fine Dining</option>
                <option value="Pizza">Pizza</option>
                <option value="Asian">Asian</option>
                <option value="Middle Eastern">Middle Eastern</option>
                <option value="Ethiopian">Ethiopian</option>
                <option value="Burgers">Burgers</option>
                <option value="Desserts">Desserts</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <BaseInput v-model="form.phone" label="Phone" placeholder="+1 234 567 890" />
            <BaseInput v-model="form.address" label="Address" placeholder="123 Main St, City" />
          </div>

          <!-- Step 3 -->
          <div v-if="step === 3" class="form-step review-step">
            <div class="review-section">
              <h3 class="review-section__title">Your Account</h3>
              <p class="review-section__item"><strong>Name:</strong> {{ form.owner_name }}</p>
              <p class="review-section__item"><strong>Email:</strong> {{ form.email }}</p>
            </div>
            <div class="review-section">
              <h3 class="review-section__title">Your Restaurant</h3>
              <p class="review-section__item"><strong>Name:</strong> {{ form.restaurant_name }}</p>
              <p class="review-section__item" v-if="form.phone"><strong>Phone:</strong> {{ form.phone }}</p>
              <p class="review-section__item" v-if="form.address"><strong>Address:</strong> {{ form.address }}</p>
            </div>
            <div class="info-box">
              <p class="info-box__text">
                Your restaurant will be reviewed by our team within 24 hours.
                You'll be notified once approved.
              </p>
            </div>
          </div>

          <p v-if="error" class="register-error">{{ error }}</p>

          <div class="form-actions">
            <BaseButton v-if="step > 1" variant="ghost" type="button" @click="step--">
              Back
            </BaseButton>
            <div style="flex: 1" />
            <BaseButton
              v-if="step < 3"
              variant="primary"
              type="button"
              @click="nextStep"
            >
              Next
            </BaseButton>
            <BaseButton
              v-else
              variant="primary"
              type="submit"
              :loading="loading"
              :disabled="loading"
            >
              Submit for Approval
            </BaseButton>
          </div>
        </form>

        <div class="register-footer">
          <p class="register-footer__text">
            Already have an account?
            <RouterLink to="/staff/login" class="register-footer__link">Staff Sign in</RouterLink>
          </p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore";
import BaseInput from "../components/ui/BaseInput.vue";
import BaseButton from "../components/ui/BaseButton.vue";

const auth = useAuthStore();
const router = useRouter();
const step = ref(1);
const loading = ref(false);
const error = ref("");
const submitted = ref(false);

const form = reactive({
  owner_name: "",
  email: "",
  password: "",
  confirmPassword: "",
  restaurant_name: "",
  cuisine_type: "",
  phone: "",
  address: "",
});

const errors = reactive({
  owner_name: "",
  email: "",
  password: "",
  confirmPassword: "",
  restaurant_name: "",
});

function validateStep1() {
  let ok = true;
  errors.owner_name = "";
  errors.email = "";
  errors.password = "";
  errors.confirmPassword = "";

  if (!form.owner_name.trim()) { errors.owner_name = "Name is required"; ok = false; }
  if (!form.email.trim()) { errors.email = "Email is required"; ok = false; }
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { errors.email = "Invalid email"; ok = false; }
  if (!form.password || form.password.length < 8) { errors.password = "Min 8 characters"; ok = false; }
  if (form.password !== form.confirmPassword) { errors.confirmPassword = "Passwords don't match"; ok = false; }
  return ok;
}

function validateStep2() {
  let ok = true;
  errors.restaurant_name = "";
  if (!form.restaurant_name.trim()) { errors.restaurant_name = "Restaurant name is required"; ok = false; }
  return ok;
}

function nextStep() {
  if (step.value === 1 && !validateStep1()) return;
  if (step.value === 2 && !validateStep2()) return;
  step.value++;
}

async function handleSubmit() {
  loading.value = true;
  error.value = "";
  try {
    await auth.registerRestaurant({
      name: form.owner_name.trim(),
      email: form.email.trim(),
      password: form.password,
      password_confirmation: form.confirmPassword,
      restaurant_name: form.restaurant_name.trim(),
      restaurant_description: "",
      cuisine_type: form.cuisine_type || "",
      phone: form.phone || null,
      address: form.address || null,
      role: "manager",
      staff_role: "manager",
    });
    submitted.value = true;
  } catch (e) {
    error.value = auth.error || "Registration failed";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.register-page {
  position: relative;
  min-height: 100svh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  padding: 24px;
}

.register-page__bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
}

.register-page__orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: orb-drift 20s ease-in-out infinite alternate;
}

.register-page__orb--1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(124, 58, 237, 0.18), transparent 70%);
  bottom: -200px; right: -100px;
}

.register-page__orb--2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.12), transparent 70%);
  top: -150px; left: -100px;
}

@keyframes orb-drift {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(30px, -20px) scale(1.05); }
  100% { transform: translate(-20px, 30px) scale(0.95); }
}

.register-card {
  width: 100%;
  max-width: 480px;
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-2xl);
  padding: var(--space-8);
  box-shadow: var(--shadow-xl);
  position: relative;
}

.register-header {
  text-align: center;
  margin-bottom: var(--space-6);
}

.register-logo {
  font-size: 2rem;
  margin-bottom: var(--space-2);
}

.register-title {
  margin: 0;
  font-size: var(--text-2xl);
  font-weight: var(--font-extrabold);
  color: var(--color-text-primary);
}

.register-subtitle {
  margin: var(--space-1) 0 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

/* Progress */
.progress {
  display: flex;
  justify-content: center;
  gap: var(--space-6);
  margin-bottom: var(--space-8);
}

.progress__step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-1);
  opacity: 0.4;
}

.progress__step--active { opacity: 1; }

.progress__number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-bg-muted);
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
}

.progress__step--current .progress__number {
  background: var(--color-primary);
  color: white;
}

.progress__step--active .progress__number {
  background: var(--color-success);
  color: white;
}

.progress__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-weight: var(--font-medium);
}

/* Form */
.register-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.form-step {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.review-section {
  padding: var(--space-4);
  background: var(--color-bg-subtle);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.review-section__title {
  margin: 0 0 var(--space-2);
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-primary-light);
}

.review-section__item {
  margin: var(--space-1) 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.info-box {
  padding: var(--space-4);
  background: var(--color-info-light);
  border: 1px solid rgba(6, 182, 212, 0.2);
  border-radius: var(--radius-lg);
}

.info-box__text {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-accent);
}

.register-error {
  margin: 0;
  padding: var(--space-3);
  border-radius: var(--radius-md);
  background: var(--color-danger-light);
  color: var(--color-danger);
  font-size: var(--text-sm);
  text-align: center;
}

.form-actions {
  display: flex;
  gap: var(--space-3);
  margin-top: var(--space-2);
}

.register-footer {
  margin-top: var(--space-6);
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border);
  text-align: center;
}

.register-footer__text {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.register-footer__link {
  color: var(--color-primary-light);
  font-weight: var(--font-semibold);
  text-decoration: none;
}

/* Success */
.register-success {
  text-align: center;
  padding: var(--space-8) 0;
}

.register-success__icon {
  font-size: 3rem;
  margin-bottom: var(--space-4);
}

.register-success__title {
  margin: 0 0 var(--space-3);
  font-size: var(--text-2xl);
  font-weight: var(--font-extrabold);
  color: var(--color-text-primary);
}

.register-success__text {
  margin: 0 0 var(--space-6);
  color: var(--color-text-secondary);
  line-height: 1.6;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.form-label {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-secondary);
}

.form-select {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font-family: var(--font-sans);
  font-size: var(--text-base);
  outline: none;
  cursor: pointer;
}

.form-select:focus {
  border-color: var(--color-warm);
  box-shadow: 0 0 0 3px var(--color-warm-glow);
}

.register-success__link {
  color: var(--color-primary-light);
  font-weight: var(--font-semibold);
  text-decoration: none;
}
</style>

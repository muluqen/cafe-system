<template>
  <Transition name="welcome-overlay">
    <div v-if="visible" class="welcome-overlay" aria-live="polite">
      <div class="welcome-orb welcome-orb-a" />
      <div class="welcome-orb welcome-orb-b" />
      <div class="welcome-card">
        <span class="welcome-kicker">Welcome back</span>
        <h1>{{ headline }}</h1>
        <p>{{ message }}</p>
        <div class="welcome-meta">
          <span class="welcome-dot" />
          <strong>{{ restaurantName }}</strong>
        </div>
        <button class="button welcome-enter-btn" type="button" @click="$emit('enter')">
          Enter your experience
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineEmits(["enter"]);

defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  headline: {
    type: String,
    default: "Your table is almost ready."
  },
  message: {
    type: String,
    default: "Menus, orders, and your saved favorites are waiting for you."
  },
  restaurantName: {
    type: String,
    default: "DineDirect"
  }
});
</script>

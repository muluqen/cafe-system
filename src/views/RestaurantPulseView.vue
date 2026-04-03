<template>
  <section class="dashboard-stack">
    <section class="panel feedback-hero">
      <div>
        <span class="eyebrow">Customer Pulse</span>
        <h1 class="panel-title">Compliments, complaints, and guest feedback</h1>
        <p class="muted">
          This is where the restaurant owner sees what customers are praising, what they want fixed,
          and how the restaurant is being felt from the guest side.
        </p>
      </div>
      <div class="feedback-hero-card">
        <span class="selector-label">Restaurant average</span>
        <strong>{{ averageRating }}</strong>
        <p class="muted">{{ feedbackEntries.length }} customer notes collected.</p>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header panel-header-rich">
        <div>
          <span class="eyebrow">Owner Summary</span>
          <h2 class="panel-title">What guests are telling you</h2>
        </div>
      </header>

      <div class="content-pad rewards-grid">
        <article class="metric-card">
          <span class="selector-label">Compliments</span>
          <strong>{{ complimentCount }}</strong>
          <p class="muted">Guests praising food, service, or atmosphere.</p>
        </article>
        <article class="metric-card">
          <span class="selector-label">Complaints</span>
          <strong>{{ complaintCount }}</strong>
          <p class="muted">Issues that likely need a service or menu fix.</p>
        </article>
        <article class="metric-card">
          <span class="selector-label">Chef notes</span>
          <strong>{{ noteCount }}</strong>
          <p class="muted">Direct personal messages sent to the restaurant team.</p>
        </article>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header">
        <div>
          <span class="eyebrow">Live Feed</span>
          <h2 class="panel-title">Recent customer voice</h2>
        </div>
      </header>

      <div class="content-pad">
        <div v-if="feedbackEntries.length" class="feedback-history">
          <article v-for="entry in feedbackEntries" :key="entry.id" class="feedback-card">
            <div class="order-head">
              <strong>{{ entry.customerName || "Customer" }}</strong>
              <span class="badge">{{ entry.rating }}/5</span>
            </div>
            <p class="muted">{{ formatDate(entry.createdAt) }}</p>
            <p v-if="entry.compliment"><strong>Compliment:</strong> {{ entry.compliment }}</p>
            <p v-if="entry.complaint"><strong>Complaint:</strong> {{ entry.complaint }}</p>
            <p v-if="entry.note"><strong>Message:</strong> {{ entry.note }}</p>
            <div v-if="entry.tags?.length" class="quick-grid">
              <span v-for="tag in entry.tags" :key="tag" class="feedback-tag">{{ tag }}</span>
            </div>
          </article>
        </div>
        <div v-else class="empty">
          Customer compliments and complaints will appear here once guests start leaving feedback.
        </div>
      </div>
    </section>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { useAuthStore } from "../stores/authStore";
import { getRestaurantFeedbackFeed } from "../utils/restaurantFeedback";

const auth = useAuthStore();

const feedbackEntries = computed(() => getRestaurantFeedbackFeed(auth.user?.restaurant_id));
const complimentCount = computed(() => feedbackEntries.value.filter((entry) => entry.compliment).length);
const complaintCount = computed(() => feedbackEntries.value.filter((entry) => entry.complaint).length);
const noteCount = computed(() => feedbackEntries.value.filter((entry) => entry.note).length);
const averageRating = computed(() => {
  if (!feedbackEntries.value.length) {
    return "No ratings yet";
  }

  const total = feedbackEntries.value.reduce((sum, entry) => sum + Number(entry.rating || 0), 0);
  return `${(total / feedbackEntries.value.length).toFixed(1)}/5`;
});

function formatDate(value) {
  return new Date(value).toLocaleString();
}
</script>

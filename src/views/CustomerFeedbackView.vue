<template>
  <section class="customer-stack" :style="themeStyle">
    <section class="panel feedback-hero">
      <div>
        <span class="eyebrow">Restaurant Voice</span>
        <h1 class="panel-title">Share what stood out</h1>
        <p class="muted">
          Rate the experience, compliment the kitchen, or report anything that should be better next time.
        </p>
      </div>
      <div class="feedback-hero-card">
        <span class="selector-label">Currently writing about</span>
        <strong>{{ selectedRestaurantName }}</strong>
        <p class="muted">Your note stays tied to this restaurant so the feedback feels specific and useful.</p>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header panel-header-rich">
        <div>
          <span class="eyebrow">Tell Them</span>
          <h2 class="panel-title">Customer feedback</h2>
        </div>
      </header>

      <div class="content-pad">
        <form class="feedback-form" @submit.prevent="saveFeedback">
          <div class="feedback-rating">
            <span class="selector-label">Overall rating</span>
            <div class="feedback-stars" role="radiogroup" aria-label="Restaurant rating">
              <button
                v-for="value in [1, 2, 3, 4, 5]"
                :key="value"
                class="star-btn"
                :class="{ active: value <= form.rating }"
                type="button"
                @click="form.rating = value"
              >
                {{ value <= form.rating ? "★" : "☆" }}
              </button>
            </div>
          </div>

          <label>
            What made the visit feel great?
            <textarea
              v-model="form.compliment"
              class="input kitchen-note"
              rows="4"
              placeholder="Kind staff, perfect coffee, fast table service, beautiful plating..."
            />
          </label>

          <label>
            What should be improved?
            <textarea
              v-model="form.complaint"
              class="input kitchen-note"
              rows="4"
              placeholder="Food temperature, waiting time, taste, seating, service details..."
            />
          </label>

          <label>
            Message to the chef or team
            <textarea
              v-model="form.note"
              class="input kitchen-note"
              rows="4"
              placeholder="Anything personal you want the restaurant to know..."
            />
          </label>

          <div class="feedback-tags">
            <span class="selector-label">Quick themes</span>
            <div class="quick-grid">
              <button
                v-for="tag in quickTags"
                :key="tag"
                class="quick-chip"
                :class="{ active: form.tags.includes(tag) }"
                type="button"
                @click="toggleTag(tag)"
              >
                {{ tag }}
              </button>
            </div>
          </div>

          <p v-if="message" class="muted">{{ message }}</p>

          <div class="order-detail-actions">
            <button class="button button-soft" type="button" @click="resetForm">Clear</button>
            <button class="button" type="submit">Save feedback</button>
          </div>
        </form>
      </div>
    </section>

    <section class="panel">
      <header class="panel-header">
        <div>
          <span class="eyebrow">History</span>
          <h2 class="panel-title">Your recent notes</h2>
        </div>
      </header>

      <div class="content-pad">
        <div v-if="entries.length" class="feedback-history">
          <article v-for="entry in entries" :key="entry.id" class="feedback-card">
            <div class="order-head">
              <strong>{{ entry.restaurantName }}</strong>
              <span class="badge">{{ entry.rating }}/5</span>
            </div>
            <p class="muted">{{ formatDate(entry.createdAt) }}</p>
            <p v-if="entry.compliment"><strong>Compliment:</strong> {{ entry.compliment }}</p>
            <p v-if="entry.complaint"><strong>Complaint:</strong> {{ entry.complaint }}</p>
            <p v-if="entry.note"><strong>Extra note:</strong> {{ entry.note }}</p>
            <div v-if="entry.tags?.length" class="quick-grid">
              <span v-for="tag in entry.tags" :key="tag" class="feedback-tag">{{ tag }}</span>
            </div>
          </article>
        </div>
        <div v-else class="empty">
          No saved feedback yet. Your first note to a restaurant will show up here.
        </div>
      </div>
    </section>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useAuthStore } from "../stores/authStore";
import {
  getCustomerFeedbackHistory,
  saveCustomerFeedbackHistory
} from "../utils/restaurantFeedback";
import { getRestaurantBranding } from "../utils/restaurantBranding";
const auth = useAuthStore();
const message = ref("");
const entries = ref([]);
const quickTags = [
  "Great coffee",
  "Lovely service",
  "Chef compliment",
  "Too slow",
  "Needs seasoning",
  "Clean space",
  "Cozy table",
  "Would order again"
];

const form = reactive({
  rating: 5,
  compliment: "",
  complaint: "",
  note: "",
  tags: []
});

const selectedRestaurantName = computed(() => {
  const restaurant = auth.publicRestaurants.find(
    (row) => String(row.id) === String(auth.selectedRestaurantId || "")
  );
  return restaurant?.name || "the selected restaurant";
});
const restaurantBranding = computed(() => getRestaurantBranding(auth.selectedRestaurantId));
const themeStyle = computed(() => ({
  "--restaurant-brand": restaurantBranding.value.brandColor || "#1A2A40",
  "--restaurant-brand-soft": `${restaurantBranding.value.brandColor || "#1A2A40"}1f`,
  "--restaurant-brand-gradient": `linear-gradient(135deg, ${(restaurantBranding.value.brandColors || [restaurantBranding.value.brandColor || "#1A2A40"]).join(", ")})`
}));

const feedbackKey = computed(
  () => `${auth.user?.id || "guest"}:${auth.selectedRestaurantId || "none"}`
);

function readEntries() {
  entries.value = getCustomerFeedbackHistory(auth.user?.id || "guest", auth.selectedRestaurantId);
}

function toggleTag(tag) {
  form.tags = form.tags.includes(tag)
    ? form.tags.filter((value) => value !== tag)
    : [...form.tags, tag];
}

function resetForm() {
  form.rating = 5;
  form.compliment = "";
  form.complaint = "";
  form.note = "";
  form.tags = [];
  message.value = "";
}

function saveFeedback() {
  const nextEntries = [
    {
      id: `${Date.now()}`,
      restaurantId: auth.selectedRestaurantId || null,
      restaurantName: selectedRestaurantName.value,
      rating: form.rating,
      compliment: form.compliment.trim(),
      complaint: form.complaint.trim(),
      note: form.note.trim(),
      tags: [...form.tags],
      customerName: auth.user?.name || "Customer",
      createdAt: new Date().toISOString()
    },
    ...entries.value
  ].slice(0, 12);

  saveCustomerFeedbackHistory(auth.user?.id || "guest", auth.selectedRestaurantId, nextEntries);
  readEntries();
  resetForm();
  message.value = "Feedback saved for this restaurant.";
}

function formatDate(value) {
  return new Date(value).toLocaleString();
}

onMounted(async () => {
  await auth.loadPublicRestaurants();
  readEntries();
});

watch(feedbackKey, readEntries);
</script>

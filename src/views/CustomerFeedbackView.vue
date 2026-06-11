<template>
  <div class="customer-feedback">
    <PageHeader title="My Feedback">
      <template #right>
        <BaseButton variant="primary" @click="openFeedbackModal()">
          Leave Feedback
        </BaseButton>
      </template>
    </PageHeader>

    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="text" :lines="3" />
    </div>

    <div v-else-if="feedback.length === 0" class="empty-state">
      <div class="empty-state__icon">💬</div>
      <h3 class="empty-state__title">No feedback yet</h3>
      <p class="empty-state__text">Share your dining experience</p>
    </div>

    <div v-else class="feedback-list">
      <div v-for="f in feedback" :key="f.id" class="feedback-card">
        <div class="feedback-card__header">
          <span class="feedback-card__restaurant">{{ f.restaurant?.name || 'Restaurant' }}</span>
          <div class="feedback-card__actions">
            <span class="feedback-card__date">{{ formatDate(f.created_at) }}</span>
            <button class="feedback-card__edit" @click="openEditModal(f)">Edit</button>
          </div>
        </div>
        <div class="feedback-card__stars">
          <span v-for="s in 5" :key="s" :class="['star', { 'star--active': s <= (f.rating || 0) }]">★</span>
        </div>
        <p v-if="f.compliment" class="feedback-card__compliment">👍 {{ f.compliment }}</p>
        <p v-if="f.complaint" class="feedback-card__complaint">👎 {{ f.complaint }}</p>
        <p v-if="f.note" class="feedback-card__note">💬 {{ f.note }}</p>
        <p v-if="f.comment" class="feedback-card__comment">{{ f.comment }}</p>
        <div v-if="f.tags?.length" class="feedback-card__tags">
          <span v-for="tag in f.tags" :key="tag" class="tag">{{ tag }}</span>
        </div>
      </div>
    </div>

    <!-- Feedback Modal -->
    <BaseModal v-model="feedbackModalVisible" :title="editingFeedbackId ? 'Edit Feedback' : 'Leave Feedback'" size="md">
      <div class="feedback-form">
        <div v-if="!editingRestaurantId" class="feedback-form__field">
          <label class="feedback-form__label">Select Restaurant</label>
          <select v-model="selectedRestaurantId" class="feedback-select">
            <option value="" disabled>Choose a restaurant...</option>
            <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <div v-else class="feedback-form__restaurant-name">
          {{ restaurants.find(r => r.id === editingRestaurantId)?.name || 'Restaurant' }}
        </div>

        <div class="feedback-form__field">
          <label class="feedback-form__label">Your Name</label>
          <input v-model="customerName" type="text" class="feedback-input" placeholder="Optional" />
        </div>

        <div class="feedback-form__field">
          <label class="feedback-form__label">Rating</label>
          <div class="star-rating">
            <button v-for="s in 5" :key="s" :class="['star-btn', { 'star-btn--active': s <= rating }]" @click="rating = s">
              ★
            </button>
          </div>
        </div>

        <div class="feedback-form__field">
          <label class="feedback-form__label">👍 Compliment</label>
          <textarea v-model="compliment" class="feedback-textarea" placeholder="What did you love?" rows="2" />
        </div>

        <div class="feedback-form__field">
          <label class="feedback-form__label">👎 Complaint</label>
          <textarea v-model="complaint" class="feedback-textarea" placeholder="What was not good?" rows="2" />
        </div>

        <div class="feedback-form__field">
          <label class="feedback-form__label">💬 Message / Chef Notes</label>
          <textarea v-model="note" class="feedback-textarea" placeholder="Any other thoughts?" rows="2" />
        </div>

        <div class="feedback-form__field">
          <label class="feedback-form__label">Tags</label>
          <div class="feedback-tags">
            <button v-for="tag in availableTags" :key="tag" :class="['tag-btn', { 'tag-btn--active': selectedTags.includes(tag) }]" @click="toggleTag(tag)">
              {{ tag }}
            </button>
          </div>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="feedbackModalVisible = false">Cancel</BaseButton>
        <BaseButton variant="primary" :loading="submitting" :disabled="!selectedRestaurantId && !editingRestaurantId" @click="handleSubmit">Submit</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import customerService from "../services/customerService";
import { useToast } from "../composables/useToast";
import PageHeader from "../components/ui/PageHeader.vue";
import BaseButton from "../components/ui/BaseButton.vue";
import BaseModal from "../components/ui/BaseModal.vue";
import SkeletonLoader from "../components/ui/SkeletonLoader.vue";

const toast = useToast();
const feedback = ref([]);
const restaurants = ref([]);
const loading = ref(true);
const feedbackModalVisible = ref(false);
const rating = ref(5);
const comment = ref("");
const compliment = ref("");
const complaint = ref("");
const note = ref("");
const customerName = ref("");
const selectedTags = ref([]);
const submitting = ref(false);
const selectedRestaurantId = ref("");
const editingRestaurantId = ref(null);
const editingFeedbackId = ref(null);

const availableTags = [
  "Food Quality", "Service", "Ambiance", "Speed", "Value",
  "Cleanliness", "Music", "Seating", "Parking", "Staff"
];

function toggleTag(tag) {
  const idx = selectedTags.value.indexOf(tag);
  if (idx >= 0) {
    selectedTags.value.splice(idx, 1);
  } else {
    selectedTags.value.push(tag);
  }
}

function formatDate(date) {
  if (!date) return "";
  return new Date(date).toLocaleDateString();
}

function openFeedbackModal(restaurantId = null) {
  editingFeedbackId.value = null;
  editingRestaurantId.value = restaurantId;
  selectedRestaurantId.value = restaurantId || "";
  rating.value = 5;
  comment.value = "";
  compliment.value = "";
  complaint.value = "";
  note.value = "";
  customerName.value = "";
  selectedTags.value = [];
  feedbackModalVisible.value = true;
}

function openEditModal(f) {
  editingFeedbackId.value = f.id;
  editingRestaurantId.value = f.restaurant_id;
  selectedRestaurantId.value = f.restaurant_id;
  rating.value = f.rating || 5;
  comment.value = f.comment || "";
  compliment.value = f.compliment || "";
  complaint.value = f.complaint || "";
  note.value = f.note || "";
  customerName.value = f.customer_name || "";
  selectedTags.value = f.tags ? [...f.tags] : [];
  feedbackModalVisible.value = true;
}

async function handleSubmit() {
  const restaurantId = editingRestaurantId.value || selectedRestaurantId.value;
  if (!restaurantId) {
    toast.warning("Please select a restaurant");
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      restaurant_id: Number(restaurantId),
      rating: rating.value,
      comment: comment.value || null,
      compliment: compliment.value || null,
      complaint: complaint.value || null,
      note: note.value || null,
      tags: selectedTags.value.length > 0 ? selectedTags.value : null,
      customer_name: customerName.value || null,
    };

    if (editingFeedbackId.value) {
      const { data } = await customerService.updateFeedback(editingFeedbackId.value, payload);
      const updated = data?.data || data;
      const idx = feedback.value.findIndex(f => f.id === editingFeedbackId.value);
      if (idx >= 0 && updated) {
        feedback.value[idx] = { ...feedback.value[idx], ...updated };
      }
      toast.success("Feedback updated!");
    } else {
      const { data } = await customerService.submitFeedback(payload);
      const created = data?.data || data;
      if (created) {
        feedback.value.unshift(created);
      }
      toast.success("Feedback submitted!");
    }
    feedbackModalVisible.value = false;
  } catch (e) {
    const msg = e?.response?.data?.message || e?.response?.data?.errors || "Failed to submit feedback";
    toast.error(typeof msg === 'object' ? Object.values(msg).flat().join(', ') : msg);
  } finally {
    submitting.value = false;
  }
}

async function loadFeedback() {
  try {
    const res = await customerService.getMyFeedback();
    const body = res?.data;
    const items = body?.data?.data || body?.data || body;
    feedback.value = Array.isArray(items) ? items : [];
  } catch {}
}

async function loadRestaurants() {
  try {
    const res = await customerService.getRestaurants();
    const body = res?.data;
    const items = body?.data || body;
    restaurants.value = Array.isArray(items) ? items : [];
  } catch {}
}

onMounted(async () => {
  await Promise.all([loadFeedback(), loadRestaurants()]);
  loading.value = false;
});
</script>

<style scoped>
.feedback-list { display: flex; flex-direction: column; gap: var(--space-4); }

.feedback-card {
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-5);
}

.feedback-card__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-2); }
.feedback-card__restaurant { font-weight: var(--font-semibold); color: var(--color-text-primary); }
.feedback-card__actions { display: flex; align-items: center; gap: var(--space-3); }
.feedback-card__date { font-size: var(--text-xs); color: var(--color-text-muted); }
.feedback-card__edit {
  padding: 0.2rem 0.6rem; border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: 600;
  border: 1px solid var(--color-border); background: transparent; color: var(--color-text-secondary);
  cursor: pointer; transition: all var(--transition-fast);
}
.feedback-card__edit:hover { border-color: var(--color-primary); color: var(--color-primary); }

.feedback-card__stars { margin-bottom: var(--space-2); }
.star { color: var(--color-text-muted); }
.star--active { color: var(--color-warning); }

.feedback-card__compliment { margin: 0 0 var(--space-1); font-size: var(--text-sm); color: #10B981; }
.feedback-card__complaint { margin: 0 0 var(--space-1); font-size: var(--text-sm); color: #F43F5E; }
.feedback-card__note { margin: 0 0 var(--space-1); font-size: var(--text-sm); color: #06B6D4; }
.feedback-card__comment { margin: 0 0 var(--space-1); font-size: var(--text-sm); color: var(--color-text-secondary); }
.feedback-card__tags { display: flex; flex-wrap: wrap; gap: var(--space-1); margin-top: var(--space-2); }
.tag { padding: 0.2rem 0.5rem; background: rgba(6, 182, 212, 0.10); color: #06B6D4; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }

.empty-state { text-align: center; padding: var(--space-12); }
.empty-state__icon { font-size: 3rem; margin-bottom: var(--space-3); }
.empty-state__title { margin: 0 0 var(--space-2); font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--color-text-primary); }
.empty-state__text { margin: 0; color: var(--color-text-muted); }

.loading-state { padding: var(--space-8); }

.feedback-form { display: flex; flex-direction: column; gap: var(--space-4); }
.feedback-form__field { display: flex; flex-direction: column; gap: var(--space-1); }
.feedback-form__label { font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--color-text-primary); }
.feedback-form__restaurant-name { font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--color-text-primary); }

.feedback-select,
.feedback-input {
  padding: var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font-size: var(--text-sm);
}
.feedback-select:focus,
.feedback-input:focus { outline: none; border-color: var(--color-primary); }

.star-rating { display: flex; gap: var(--space-1); }
.star-btn { font-size: 1.5rem; background: none; border: none; color: var(--color-text-muted); cursor: pointer; }
.star-btn--active { color: var(--color-warning); }

.feedback-textarea {
  width: 100%; padding: var(--space-3); border: 1px solid var(--color-border);
  border-radius: var(--radius-md); background: var(--color-bg-subtle); color: var(--color-text-primary);
  font-family: var(--font-sans); font-size: var(--text-sm); resize: vertical;
}
.feedback-textarea:focus { outline: none; border-color: var(--color-primary); }

.feedback-tags { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.tag-btn {
  padding: 0.3rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;
  border: 1px solid var(--color-border); background: var(--color-bg-subtle);
  color: var(--color-text-secondary); cursor: pointer; transition: all var(--transition-fast);
}
.tag-btn--active {
  background: rgba(6, 182, 212, 0.15); color: #06B6D4; border-color: #06B6D4;
}
</style>

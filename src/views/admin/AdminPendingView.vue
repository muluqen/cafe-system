<template>
  <div class="admin-pending">
    <PageHeader title="Pending Approvals">
      <template #right>
        <BaseBadge v-if="restaurants.length" variant="warning">
          {{ restaurants.length }} pending
        </BaseBadge>
      </template>
    </PageHeader>

    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="card" :lines="3" />
    </div>

    <div v-else-if="restaurants.length === 0" class="empty-state">
      <div class="empty-state__icon">🎉</div>
      <h3 class="empty-state__title">All caught up!</h3>
      <p class="empty-state__text">No pending restaurants to review.</p>
    </div>

    <div v-else class="pending-grid">
      <TransitionGroup name="card">
        <BaseCard v-for="r in restaurants" :key="r.id" padding="md" hoverable>
          <div class="pending-card">
            <h3 class="pending-card__name">{{ r.name }}</h3>
            <div class="pending-card__details">
              <p v-if="r.users?.[0]" class="pending-card__detail">
                <span class="detail-label">Owner:</span> {{ r.users[0].name }}
              </p>
              <p v-if="r.users?.[0]?.email" class="pending-card__detail">
                <span class="detail-label">Email:</span> {{ r.users[0].email }}
              </p>
              <p class="pending-card__detail">
                <span class="detail-label">Registered:</span> {{ timeAgo(r.created_at) }}
              </p>
              <p v-if="r.description" class="pending-card__detail">
                <span class="detail-label">Description:</span> {{ r.description }}
              </p>
            </div>
            <div class="pending-card__actions">
              <BaseButton
                variant="success"
                :loading="approvingId === r.id"
                @click="handleApprove(r)"
                style="flex: 1"
              >
                ✓ Approve
              </BaseButton>
              <BaseButton
                variant="danger"
                :loading="rejectingId === r.id"
                @click="openRejectModal(r)"
                style="flex: 1"
              >
                ✗ Reject
              </BaseButton>
            </div>
          </div>
        </BaseCard>
      </TransitionGroup>
    </div>

    <BaseModal v-model="rejectModalVisible" title="Reject Restaurant" size="sm">
      <p class="modal-text">
        Are you sure you want to reject <strong>{{ rejectTarget?.name }}</strong>?
      </p>
      <BaseInput
        v-model="rejectReason"
        label="Reason"
        placeholder="Provide a reason for rejection..."
      />
      <template #footer>
        <BaseButton variant="ghost" @click="rejectModalVisible = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="rejectLoading" @click="handleReject">
          Reject
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import adminService from "../../services/adminService";
import { useToast } from "../../composables/useToast";
import PageHeader from "../../components/ui/PageHeader.vue";
import BaseCard from "../../components/ui/BaseCard.vue";
import BaseButton from "../../components/ui/BaseButton.vue";
import BaseBadge from "../../components/ui/BaseBadge.vue";
import BaseModal from "../../components/ui/BaseModal.vue";
import BaseInput from "../../components/ui/BaseInput.vue";
import SkeletonLoader from "../../components/ui/SkeletonLoader.vue";

const toast = useToast();
const restaurants = ref([]);
const loading = ref(true);
const approvingId = ref(null);
const rejectingId = ref(null);
const rejectModalVisible = ref(false);
const rejectTarget = ref(null);
const rejectReason = ref("");
const rejectLoading = ref(false);

function timeAgo(date) {
  if (!date) return "";
  const seconds = Math.floor((new Date() - new Date(date)) / 1000);
  if (seconds < 60) return "just now";
  if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
  return `${Math.floor(seconds / 86400)} days ago`;
}

async function fetchPending() {
  loading.value = true;
  try {
    const { data } = await adminService.getPendingRestaurants();
    restaurants.value = data?.data || data || [];
  } catch (e) {
    toast.error("Failed to load pending restaurants");
  } finally {
    loading.value = false;
  }
}

async function handleApprove(r) {
  approvingId.value = r.id;
  try {
    await adminService.approveRestaurant(r.id);
    restaurants.value = restaurants.value.filter(item => item.id !== r.id);
    toast.success(`${r.name} has been approved`);
  } catch (e) {
    toast.error("Failed to approve");
  } finally {
    approvingId.value = null;
  }
}

function openRejectModal(r) {
  rejectTarget.value = r;
  rejectReason.value = "";
  rejectModalVisible.value = true;
}

async function handleReject() {
  if (!rejectReason.value.trim()) {
    toast.warning("Please provide a reason");
    return;
  }
  rejectLoading.value = true;
  try {
    await adminService.rejectRestaurant(rejectTarget.value.id, rejectReason.value.trim());
    restaurants.value = restaurants.value.filter(item => item.id !== rejectTarget.value.id);
    rejectModalVisible.value = false;
    toast.success(`${rejectTarget.value.name} has been rejected`);
  } catch (e) {
    toast.error("Failed to reject");
  } finally {
    rejectLoading.value = false;
  }
}

onMounted(fetchPending);
</script>

<style scoped>
.pending-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: var(--space-4);
}

.pending-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.pending-card__name {
  margin: 0;
  font-size: var(--text-xl);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.pending-card__details {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.pending-card__detail {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.detail-label {
  color: var(--color-text-muted);
  font-weight: var(--font-medium);
}

.pending-card__actions {
  display: flex;
  gap: var(--space-2);
  margin-top: var(--space-2);
}

.empty-state {
  text-align: center;
  padding: var(--space-16) var(--space-8);
}

.empty-state__icon {
  font-size: 3rem;
  margin-bottom: var(--space-4);
}

.empty-state__title {
  margin: 0 0 var(--space-2);
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.empty-state__text {
  margin: 0;
  color: var(--color-text-muted);
}

.modal-text {
  margin: 0 0 var(--space-4);
  color: var(--color-text-secondary);
}

.loading-state {
  padding: var(--space-8);
}

.card-enter-active,
.card-leave-active {
  transition: all 0.3s ease;
}

.card-enter-from,
.card-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>

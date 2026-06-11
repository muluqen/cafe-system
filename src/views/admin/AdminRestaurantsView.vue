<template>
  <div class="admin-restaurants">
    <PageHeader title="Restaurants">
      <template #right>
        <div class="filter-tabs">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            :class="['filter-tab', { 'filter-tab--active': activeTab === tab.value }]"
            @click="filterByStatus(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>
      </template>
    </PageHeader>

    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="table" :lines="5" />
    </div>

    <BaseTable
      v-else
      :columns="columns"
      :rows="restaurants"
      emptyMessage="No restaurants found"
    >
      <template #row="{ row }">
        <td class="name-cell">{{ row.name }}</td>
        <td>{{ row.email || row.users?.[0]?.email || 'N/A' }}</td>
        <td>
          <BaseBadge :variant="statusVariant(row.status)">
            {{ row.status }}
          </BaseBadge>
        </td>
        <td>{{ row.orders_count ?? 0 }}</td>
        <td>{{ formatDate(row.created_at) }}</td>
        <td class="actions-cell">
          <div class="action-buttons">
            <BaseButton
              v-if="row.status === 'active'"
              variant="ghost"
              size="sm"
              @click="openActionModal('suspend', row)"
            >
              Suspend
            </BaseButton>
            <template v-if="row.status === 'pending'">
              <BaseButton variant="success" size="sm" @click="handleApprove(row)">
                Approve
              </BaseButton>
              <BaseButton variant="danger" size="sm" @click="openActionModal('reject', row)">
                Reject
              </BaseButton>
            </template>
            <BaseButton
              v-if="row.status === 'suspended'"
              variant="ghost"
              size="sm"
              @click="handleReactivate(row)"
            >
              Reactivate
            </BaseButton>
          </div>
        </td>
      </template>
    </BaseTable>

    <!-- Action Modal -->
    <BaseModal v-model="modalVisible" :title="modalTitle" size="sm">
      <p class="modal-text">
        Are you sure you want to {{ modalAction }}
        <strong>{{ modalTarget?.name }}</strong>?
      </p>
      <BaseInput
        v-if="modalAction === 'reject' || modalAction === 'suspend'"
        v-model="actionReason"
        label="Reason"
        placeholder="Provide a reason..."
      />
      <template #footer>
        <BaseButton variant="ghost" @click="modalVisible = false">Cancel</BaseButton>
        <BaseButton
          :variant="modalAction === 'reject' || modalAction === 'suspend' ? 'danger' : 'success'"
          :loading="actionLoading"
          @click="handleAction"
        >
          {{ modalAction === 'reject' ? 'Reject' : modalAction === 'suspend' ? 'Suspend' : 'Approve' }}
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
import BaseTable from "../../components/ui/BaseTable.vue";
import BaseButton from "../../components/ui/BaseButton.vue";
import BaseBadge from "../../components/ui/BaseBadge.vue";
import BaseModal from "../../components/ui/BaseModal.vue";
import BaseInput from "../../components/ui/BaseInput.vue";
import SkeletonLoader from "../../components/ui/SkeletonLoader.vue";

const toast = useToast();
const restaurants = ref([]);
const loading = ref(true);
const activeTab = ref("");
const modalVisible = ref(false);
const modalAction = ref("");
const modalTarget = ref(null);
const actionReason = ref("");
const actionLoading = ref(false);

const tabs = [
  { label: "All", value: "" },
  { label: "Active", value: "active" },
  { label: "Pending", value: "pending" },
  { label: "Suspended", value: "suspended" },
];

const columns = [
  { key: "name", label: "Restaurant", sortable: true },
  { key: "email", label: "Owner Email" },
  { key: "status", label: "Status", sortable: true },
  { key: "orders_count", label: "Orders", sortable: true },
  { key: "created_at", label: "Registered", sortable: true },
  { key: "actions", label: "Actions" },
];

const modalTitle = ref("");

function statusVariant(status) {
  return { active: "success", pending: "warning", suspended: "danger" }[status] || "neutral";
}

function formatDate(date) {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString();
}

async function fetchRestaurants(status = "") {
  loading.value = true;
  try {
    const { data } = await adminService.getAllRestaurants(status);
    const body = data?.data || data;
    restaurants.value = Array.isArray(body) ? body : body?.data || [];
  } catch (e) {
    toast.error("Failed to load restaurants");
  } finally {
    loading.value = false;
  }
}

function filterByStatus(status) {
  activeTab.value = status;
  fetchRestaurants(status);
}

function openActionModal(action, restaurant) {
  modalAction.value = action;
  modalTarget.value = restaurant;
  actionReason.value = "";
  modalTitle.value = `${action.charAt(0).toUpperCase() + action.slice(1)} Restaurant`;
  modalVisible.value = true;
}

async function handleApprove(restaurant) {
  try {
    await adminService.approveRestaurant(restaurant.id);
    toast.success(`${restaurant.name} approved`);
    fetchRestaurants(activeTab.value);
  } catch (e) {
    toast.error("Failed to approve");
  }
}

async function handleReactivate(restaurant) {
  try {
    await adminService.reactivateRestaurant(restaurant.id);
    toast.success(`${restaurant.name} reactivated`);
    fetchRestaurants(activeTab.value);
  } catch (e) {
    toast.error("Failed to reactivate");
  }
}

async function handleAction() {
  if ((modalAction.value === "reject" || modalAction.value === "suspend") && !actionReason.value.trim()) {
    toast.warning("Please provide a reason");
    return;
  }
  actionLoading.value = true;
  try {
    if (modalAction.value === "reject") {
      await adminService.rejectRestaurant(modalTarget.value.id, actionReason.value.trim());
    } else if (modalAction.value === "suspend") {
      await adminService.suspendRestaurant(modalTarget.value.id, actionReason.value.trim());
    }
    modalVisible.value = false;
    toast.success(`${modalTarget.value.name} ${modalAction.value}d`);
    fetchRestaurants(activeTab.value);
  } catch (e) {
    toast.error(`Failed to ${modalAction.value}`);
  } finally {
    actionLoading.value = false;
  }
}

onMounted(() => fetchRestaurants());
</script>

<style scoped>
.filter-tabs {
  display: flex;
  gap: var(--space-1);
  background: var(--color-bg-subtle);
  padding: var(--space-1);
  border-radius: var(--radius-md);
  overflow-x: auto;
  flex-shrink: 0;
}

.filter-tab {
  padding: var(--space-2) var(--space-3);
  border: none;
  border-radius: var(--radius-sm);
  background: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
  white-space: nowrap;
  flex-shrink: 0;
}

.filter-tab--active {
  background: var(--color-primary);
  color: white;
}

.filter-tab:hover:not(.filter-tab--active) {
  color: var(--color-text-primary);
  background: var(--color-bg-overlay);
}

.name-cell {
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.actions-cell {
  white-space: nowrap;
}

.action-buttons {
  display: flex;
  gap: var(--space-2);
}

.modal-text {
  margin: 0;
  color: var(--color-text-secondary);
}

.loading-state {
  padding: var(--space-8);
}
</style>

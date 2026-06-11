<template>
  <div class="admin-users">
    <PageHeader title="Users" />

    <div class="filters-row">
      <BaseInput
        v-model="search"
        placeholder="Search by name or email..."
        icon="🔍"
        style="max-width: 300px"
      />
      <select v-model="roleFilter" class="filter-select">
        <option value="">All Roles</option>
        <option value="restaurant">Restaurant Staff</option>
        <option value="customer">Customer</option>
      </select>
    </div>

    <div v-if="loading" class="loading-state">
      <SkeletonLoader type="table" :lines="5" />
    </div>

    <BaseTable
      v-else
      :columns="columns"
      :rows="filteredUsers"
      emptyMessage="No users found"
    >
      <template #row="{ row }">
        <td class="name-cell">{{ row.name }}</td>
        <td>{{ row.email }}</td>
        <td>
          <BaseBadge :variant="roleVariant(row)">
            {{ row.is_super_admin ? 'Super Admin' : row.staff_role || row.role }}
          </BaseBadge>
        </td>
        <td>{{ row.restaurant?.name || 'N/A' }}</td>
        <td>{{ formatDate(row.created_at) }}</td>
        <td>
          <BaseBadge :variant="row.is_super_admin ? 'danger' : 'neutral'" size="sm">
            {{ row.is_super_admin ? 'Admin' : 'Regular' }}
          </BaseBadge>
        </td>
      </template>
    </BaseTable>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import adminService from "../../services/adminService";
import { useToast } from "../../composables/useToast";
import PageHeader from "../../components/ui/PageHeader.vue";
import BaseTable from "../../components/ui/BaseTable.vue";
import BaseInput from "../../components/ui/BaseInput.vue";
import BaseBadge from "../../components/ui/BaseBadge.vue";
import SkeletonLoader from "../../components/ui/SkeletonLoader.vue";

const toast = useToast();
const users = ref([]);
const loading = ref(true);
const search = ref("");
const roleFilter = ref("");

const columns = [
  { key: "name", label: "Name", sortable: true },
  { key: "email", label: "Email", sortable: true },
  { key: "role", label: "Role", sortable: true },
  { key: "restaurant", label: "Restaurant" },
  { key: "created_at", label: "Joined", sortable: true },
  { key: "status", label: "Status" },
];

const filteredUsers = computed(() => {
  let result = users.value;
  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(u =>
      u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q)
    );
  }
  if (roleFilter.value) {
    result = result.filter(u => u.role === roleFilter.value);
  }
  return result;
});

function roleVariant(user) {
  if (user.is_super_admin) return "danger";
  if (user.staff_role === "manager") return "info";
  if (user.staff_role === "cashier") return "success";
  if (user.staff_role === "kitchen") return "warning";
  return "neutral";
}

function formatDate(date) {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString();
}

onMounted(async () => {
  try {
    const { data } = await adminService.getAllUsers();
    const body = data?.data || data;
    users.value = Array.isArray(body) ? body : body?.data || [];
  } catch (e) {
    toast.error("Failed to load users");
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.filters-row {
  display: flex;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
  flex-wrap: wrap;
}

.filter-select {
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font-size: var(--text-sm);
  min-width: 160px;
}

.name-cell {
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.loading-state {
  padding: var(--space-8);
}
</style>

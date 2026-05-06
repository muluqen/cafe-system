<template>
  <section class="panel">
    <header class="panel-header panel-header-rich">
      <div>
        <span class="eyebrow">{{ entity.label }}</span>
        <h1 class="panel-title">{{ entity.label }}</h1>
      </div>
      <div class="toolbar">
        <input
          v-model="search"
          class="input"
          type="search"
          placeholder="Search (uses ?search=)"
          @keyup.enter="load"
        />
        <button class="button" :disabled="store.loading" @click="load">
          {{ store.loading ? "Loading..." : "Refresh" }}
        </button>
        <button
          v-if="canCreate"
          class="button"
          :disabled="store.saving"
          @click="startCreate"
        >
          + New
        </button>
      </div>
    </header>

    <div class="content-pad">
      <div class="entity-stage">
        <div class="entity-summary">
          <span class="entity-summary-label">Records loaded</span>
          <strong>{{ rows.length }}</strong>
        </div>
        <div class="entity-summary">
          <span class="entity-summary-label">Workspace mood</span>
          <strong>Service-ready</strong>
        </div>
      </div>

      <p v-if="store.error" class="muted">{{ store.error }}</p>
      <div v-if="successMessage" class="status-banner status-banner-success entity-status-banner">
        <span class="status-banner-dot" />
        <div>
          <strong>Done</strong>
          <p>{{ successMessage }}</p>
        </div>
      </div>

      <div v-else-if="rows.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th v-for="key in columns" :key="key">
                {{ labelize(key) }}
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id || row.uuid || JSON.stringify(row)">
              <td v-for="key in columns" :key="`${row.id || row.uuid}-${key}`">
                {{ formatRowValue(key, row[key]) }}
              </td>
              <td>
                <div class="row-actions">
                  <button v-if="canEdit(row)" class="button button-soft" @click="startEdit(row)">
                    Edit
                  </button>
                  <button
                    v-if="canDelete"
                    class="button button-soft danger"
                    @click="remove(row)"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="empty">
        No data yet. Seed data in Laravel or verify API auth/permissions.
      </div>
    </div>

    <div v-if="formOpen" class="modal-backdrop" @click.self="closeForm">
      <div class="modal-card panel">
        <div class="panel-header">
          <h2 class="panel-title">{{ editId ? "Edit Record" : "Create Record" }}</h2>
          <button class="button button-soft" @click="closeForm">Close</button>
        </div>
        <div class="content-pad">
          <form class="crud-form" @submit.prevent="save">
            <label v-for="field in editableFields" :key="field">
              {{ field }}
              <select v-if="field === 'staff_role'" v-model="form[field]" class="input">
                <option value="manager">Owner</option>
                <option v-for="role in staffRoleOptions" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
              <input v-else v-model="form[field]" class="input" type="text" />
            </label>
            <button class="button" :disabled="store.saving">
              {{ store.saving ? "Saving..." : "Save" }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useEntityStore } from "../stores/entityStore";
import { useAuthStore } from "../stores/authStore";
import { getStaffRoleMeta, staffRoleOptions } from "../utils/staffRoles";

const props = defineProps({
  entity: {
    type: Object,
    required: true
  }
});

const store = useEntityStore();
const auth = useAuthStore();
const search = ref("");
const formOpen = ref(false);
const editId = ref(null);
const form = ref({});
const successMessage = ref("");

const rows = computed(() => store.byEntity[props.entity.key] || []);
const hiddenColumns = ["created_at", "updated_at", "deleted_at", "restaurant"];

const preferredFields = computed(() =>
  props.entity.fields?.length
    ? props.entity.fields
    : []
);

const columns = computed(() => {
  const first = rows.value[0];
  if (!first || typeof first !== "object") {
    return [];
  }

  const preferred = ["id", ...preferredFields.value];
  const available = Object.keys(first).filter((key) => !hiddenColumns.includes(key));
  const ordered = preferred.filter((key) => available.includes(key));
  const extras = available.filter((key) => !ordered.includes(key) && typeof first[key] !== "object");

  return [...ordered, ...extras].slice(0, 7);
});
const editableFields = computed(() =>
  props.entity.fields?.length
    ? props.entity.fields
    : columns.value.filter((field) => !["id", "created_at", "updated_at"].includes(field))
);
const canMutateForStaff = computed(() => {
  if (!auth.isRestaurant) {
    return true;
  }

  return (
    !props.entity.mutateStaffRoles ||
    props.entity.mutateStaffRoles.includes(auth.staffRole)
  );
});

const canCreate = computed(() => {
  if (auth.isCustomer && props.entity.key === "users") {
    return false;
  }

  return canMutateForStaff.value;
});

const canDelete = computed(() => {
  if (auth.isCustomer) {
    return false;
  }

  return canMutateForStaff.value;
});

function formatCell(value) {
  if (value === null || value === undefined) {
    return "-";
  }

  if (typeof value === "boolean") {
    return value ? "Yes" : "No";
  }

  if (typeof value === "string" && value.includes("T") && !Number.isNaN(Date.parse(value))) {
    return new Date(value).toLocaleString();
  }

  if (typeof value === "object") {
    return value.name || value.title || value.label || "#linked";
  }

  return String(value);
}

function formatRowValue(key, value) {
  if (key === "staff_role") {
    return getStaffRoleMeta(value || "manager").label;
  }

  return formatCell(value);
}

function labelize(value) {
  return String(value || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

function load() {
  successMessage.value = "";
  return store.fetchEntities(props.entity.key, search.value.trim());
}

function canEdit(row) {
  if (auth.isRestaurant && !canMutateForStaff.value) {
    return false;
  }
  if (auth.isRestaurant) {
    return true;
  }
  if (auth.isCustomer && props.entity.key === "users") {
    return row.id === auth.user?.id;
  }
  return true;
}

function startCreate() {
  editId.value = null;
  form.value = Object.fromEntries(editableFields.value.map((field) => [field, ""]));
  if (Object.prototype.hasOwnProperty.call(form.value, "staff_role")) {
    form.value.staff_role = "server";
  }
  formOpen.value = true;
}

function startEdit(row) {
  editId.value = row.id;
  form.value = Object.fromEntries(
    editableFields.value.map((field) => [field, row[field] === null ? "" : String(row[field])])
  );
  formOpen.value = true;
}

function closeForm() {
  formOpen.value = false;
}

function normalizePayload(source) {
  const payload = {};
  Object.keys(source).forEach((key) => {
    const value = source[key];
    if (value === "") {
      payload[key] = null;
      return;
    }

    if (value === "true") {
      payload[key] = true;
      return;
    }

    if (value === "false") {
      payload[key] = false;
      return;
    }

    if (!Number.isNaN(Number(value)) && String(value).trim() !== "") {
      payload[key] = Number(value);
      return;
    }

    payload[key] = value;
  });

  return payload;
}

async function save() {
  try {
    const payload = normalizePayload(form.value);
    const action = editId.value ? "updated" : "created";
    if (editId.value) {
      await store.updateEntity(props.entity.key, editId.value, payload);
    } else {
      await store.createEntity(props.entity.key, payload);
    }
    await load();
    successMessage.value = `${props.entity.label} ${action} successfully.`;
    closeForm();
  } catch {
    // The store already exposes the API error for the user.
  }
}

async function remove(row) {
  if (!row.id) {
    return;
  }
  await store.deleteEntity(props.entity.key, row.id);
  await load();
  successMessage.value = `${props.entity.label} removed successfully.`;
}

watch(
  () => props.entity.key,
  () => {
    search.value = "";
    load();
  }
);

onMounted(load);
</script>

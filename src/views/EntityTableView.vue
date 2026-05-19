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
          :placeholder="`Search ${entity.label.toLowerCase()}...`"
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
      <div v-if="successMessage" class="status-banner status-banner-success entity-status-banner" style="margin-bottom: 1rem;">
        <span class="status-banner-dot" />
        <div>
          <strong>Done</strong>
          <p>{{ successMessage }}</p>
        </div>
      </div>

      <div v-if="rows.length" class="table-wrap">
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
        No data yet. 
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
            <div v-if="formError" class="status-banner status-banner-error" style="margin-bottom: 1rem; color: #d32f2f; background: #ffebee;">
              <span class="status-banner-dot" style="background: #d32f2f;" />
              <div>
                <strong>Error</strong>
                <p style="color: #c62828;">{{ formError }}</p>
              </div>
            </div>

            <label v-for="field in editableFields" :key="field">
              {{ labelize(field) }}
              
              <select v-if="field === 'staff_role'" v-model="form[field]" class="input">
                <option value="manager">Owner</option>
                <option v-for="role in staffRoleOptions" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>

              <select v-else-if="field.endsWith('_id')" v-model="form[field]" class="input">
                <option value="">-- Select --</option>
                <option v-for="opt in getOptionsForField(field)" :key="opt.id" :value="opt.id">
                  {{ opt.name || opt.title || opt.label || `ID: #${opt.id}` }}
                </option>
              </select>

              <input v-else-if="field.startsWith('is_') || field.startsWith('has_')" v-model="form[field]" type="checkbox" />

              <textarea v-else-if="field === 'description' || field === 'notes' || field === 'note'" v-model="form[field]" class="input" rows="3"></textarea>

              <input v-else-if="['price', 'amount', 'total', 'subtotal', 'tax', 'quantity', 'capacity', 'guest_count'].includes(field) || field.endsWith('_minutes')" v-model="form[field]" class="input" type="number" step="any" />

              <input v-else-if="field.endsWith('_at')" v-model="form[field]" class="input" type="datetime-local" />

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
import api from "../services/api";
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
const formError = ref("");
const optionsCache = ref({});

async function loadOptionsForField(field) {
  if (!field.endsWith('_id')) return;
  
  let endpoint = field.replace('_id', 's');
  if (field === 'menu_category_id') endpoint = 'menu_categories';
  if (field === 'inventory_id') endpoint = 'inventory_transactions';
  
  if (optionsCache.value[field]) return;
  
  try {
    const res = await api.get(`/${endpoint}`, { params: { per_page: 200 } });
    optionsCache.value[field] = res.data?.data || res.data || [];
  } catch (err) {
    console.warn("Failed to load options for", field, err);
    optionsCache.value[field] = [];
  }
}

function getOptionsForField(field) {
  return optionsCache.value[field] || [];
}

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
  if (!auth.isRestaurant) return true;
  if (auth.staffRole === 'manager') return true;

  const dynamicPerm = auth.rolePermissions.find(p => p.entity_key === props.entity.key && p.staff_role === auth.staffRole);
  if (dynamicPerm) return !!dynamicPerm.can_write;

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
  formError.value = "";
  form.value = Object.fromEntries(
    editableFields.value.map((field) => {
      if (field.startsWith('is_') || field.startsWith('has_')) return [field, false];
      return [field, ""];
    })
  );
  if (Object.prototype.hasOwnProperty.call(form.value, "staff_role")) {
    form.value.staff_role = "server";
  }
  formOpen.value = true;
  editableFields.value.filter(f => f.endsWith('_id')).forEach(loadOptionsForField);
}

function startEdit(row) {
  editId.value = row.id;
  formError.value = "";
  form.value = Object.fromEntries(
    editableFields.value.map((field) => {
      if (field.startsWith('is_') || field.startsWith('has_')) {
        return [field, !!row[field]];
      }
      if (field.endsWith('_at') && row[field]) {
        return [field, String(row[field]).substring(0, 16)];
      }
      return [field, row[field] === null ? "" : String(row[field])];
    })
  );
  formOpen.value = true;
  editableFields.value.filter(f => f.endsWith('_id')).forEach(loadOptionsForField);
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

    if (value === "true" || value === true) {
      payload[key] = true;
      return;
    }

    if (value === "false" || value === false) {
      payload[key] = false;
      return;
    }

    if (['price', 'amount', 'total', 'subtotal', 'tax', 'quantity', 'capacity', 'guest_count'].includes(key) || key.endsWith('_minutes')) {
      if (value !== null && value !== "") payload[key] = Number(value);
      return;
    }
    
    if (key.endsWith('_id') && key !== 'restaurant_id') {
      if (value !== null && value !== "") payload[key] = Number(value);
      return;
    }

    payload[key] = value;
  });

  return payload;
}

let successTimeout = null;
function showSuccessMessage(msg) {
  successMessage.value = msg;
  if (successTimeout) clearTimeout(successTimeout);
  successTimeout = setTimeout(() => {
    successMessage.value = "";
  }, 3000);
}

async function save() {
  formError.value = "";
  try {
    const payload = normalizePayload(form.value);
    
    if (auth.isRestaurant && auth.user?.restaurant_id) {
      payload.restaurant_id = auth.user.restaurant_id;
    }

    const action = editId.value ? "updated" : "created";
    if (editId.value) {
      await store.updateEntity(props.entity.key, editId.value, payload);
    } else {
      await store.createEntity(props.entity.key, payload);
    }
    await load();
    showSuccessMessage(`${props.entity.label} ${action} successfully.`);
    closeForm();
  } catch (err) {
    formError.value = err?.response?.data?.message || err?.message || "An error occurred while saving. Please check your inputs.";
  }
}

async function remove(row) {
  if (!row.id) {
    return;
  }
  await store.deleteEntity(props.entity.key, row.id);
  await load();
  showSuccessMessage(`${props.entity.label} removed successfully.`);
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

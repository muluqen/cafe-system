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

      <div v-else-if="rows.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th v-for="key in columns" :key="key">
                {{ key }}
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id || row.uuid || JSON.stringify(row)">
              <td v-for="key in columns" :key="`${row.id || row.uuid}-${key}`">
                {{ formatCell(row[key]) }}
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
              <input v-model="form[field]" class="input" type="text" />
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

const rows = computed(() => store.byEntity[props.entity.key] || []);
const columns = computed(() => {
  const first = rows.value[0];
  if (!first || typeof first !== "object") {
    return [];
  }

  return Object.keys(first).slice(0, 8);
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

  if (typeof value === "object") {
    return JSON.stringify(value);
  }

  return String(value);
}

function load() {
  store.fetchEntities(props.entity.key, search.value.trim());
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
  const payload = normalizePayload(form.value);
  if (editId.value) {
    await store.updateEntity(props.entity.key, editId.value, payload);
  } else {
    await store.createEntity(props.entity.key, payload);
  }
  closeForm();
  load();
}

async function remove(row) {
  if (!row.id) {
    return;
  }
  await store.deleteEntity(props.entity.key, row.id);
  load();
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

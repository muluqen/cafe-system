<template>
  <section class="panel">
    <header class="panel-header panel-header-rich">
      <div>
        <span class="eyebrow">Step 5</span>
        <h2 class="panel-title">Add the tables customers can choose</h2>
      </div>
    </header>

    <div class="content-pad builder-stack">
      <form class="builder-form" @submit.prevent="$emit('save-table')">
        <div class="builder-split">
          <label>
            Table name
            <input :value="tableForm.name" class="input" type="text" placeholder="Table 1, Window 2..." @input="$emit('update-table-form', 'name', $event.target.value)" />
          </label>
          <label>
            Capacity
            <input :value="tableForm.capacity" class="input" type="number" min="1" placeholder="2" @input="$emit('update-table-form', 'capacity', $event.target.value)" />
          </label>
        </div>

        <label>
          Location
          <input :value="tableForm.location" class="input" type="text" placeholder="Patio, Window, Upstairs..." @input="$emit('update-table-form', 'location', $event.target.value)" />
        </label>

        <div class="builder-step-actions between">
          <button class="button button-soft" type="button" @click="$emit('back')">Back</button>
          <div class="builder-inline-actions">
            <button class="button button-soft" type="button" @click="$emit('finish')">Finish setup</button>
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? "Saving..." : "Add table" }}
            </button>
          </div>
        </div>
      </form>

      <p v-if="error" class="error-text">{{ error }}</p>

      <div class="builder-record-list">
        <article v-for="table in tables" :key="table.id" class="builder-record-card">
          <div>
            <strong>{{ table.name }}</strong>
            <p class="muted">{{ table.capacity }} seats{{ table.location ? ` • ${table.location}` : "" }}</p>
          </div>
          <button class="button button-soft danger" type="button" @click="$emit('remove-table', table.id)">
            Remove
          </button>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  tableForm: { type: Object, required: true },
  tables: { type: Array, required: true },
  saving: { type: Boolean, default: false },
  error: { type: String, default: "" }
});

defineEmits(["update-table-form", "save-table", "remove-table", "back", "finish"]);
</script>

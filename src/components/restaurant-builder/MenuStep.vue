<template>
  <section class="panel">
    <header class="panel-header panel-header-rich">
      <div>
        <span class="eyebrow">Step 3</span>
        <h2 class="panel-title">Build the menu</h2>
      </div>
    </header>

    <div class="content-pad builder-stack">
      <form class="builder-form" @submit.prevent="$emit('save-menu-item')">
        <div class="builder-split">
          <label>
            Category
            <input :value="menuForm.categoryName" class="input" type="text" placeholder="Coffee, Breakfast, Desserts..." @input="$emit('update-menu-form', 'categoryName', $event.target.value)" />
          </label>
          <label>
            Item name
            <input :value="menuForm.name" class="input" type="text" placeholder="Latte, Burger..." @input="$emit('update-menu-form', 'name', $event.target.value)" />
          </label>
        </div>

        <div class="builder-split">
          <label>
            Price
            <input :value="menuForm.price" class="input" type="number" min="0" step="0.01" placeholder="0.00" @input="$emit('update-menu-form', 'price', $event.target.value)" />
          </label>
          <label>
            Prep time (min)
            <input :value="menuForm.preparation_time_minutes" class="input" type="number" min="0" placeholder="10" @input="$emit('update-menu-form', 'preparation_time_minutes', $event.target.value)" />
          </label>
        </div>

        <label>
          Description
          <textarea :value="menuForm.description" class="input kitchen-note" rows="3" placeholder="What makes this item good?" @input="$emit('update-menu-form', 'description', $event.target.value)" />
        </label>

        <div class="builder-step-actions between">
          <button class="button button-soft" type="button" @click="$emit('back')">Back</button>
          <div class="builder-inline-actions">
            <button class="button button-soft" type="button" @click="$emit('next')">Skip for now</button>
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? "Saving..." : "Add menu item" }}
            </button>
          </div>
        </div>
      </form>

      <p v-if="error" class="error-text">{{ error }}</p>

      <div class="builder-record-list">
        <article v-for="item in menuItems" :key="item.id" class="builder-record-card">
          <div>
            <strong>{{ item.name }}</strong>
            <p class="muted">
              {{ item.menu_category?.name || item.menuCategory?.name || "Uncategorized" }} •
              ${{ Number(item.price || 0).toFixed(2) }}
            </p>
          </div>
          <button class="button button-soft danger" type="button" @click="$emit('remove-menu-item', item.id)">
            Remove
          </button>
        </article>
        <div v-if="!menuItems.length" class="empty">Start by adding at least one menu item.</div>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  menuForm: { type: Object, required: true },
  menuItems: { type: Array, required: true },
  saving: { type: Boolean, default: false },
  error: { type: String, default: "" }
});

defineEmits(["update-menu-form", "save-menu-item", "remove-menu-item", "back", "next"]);
</script>

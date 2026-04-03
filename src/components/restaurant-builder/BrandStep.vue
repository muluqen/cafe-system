<template>
  <section class="panel">
    <header class="panel-header panel-header-rich">
      <div>
        <span class="eyebrow">Step 1</span>
        <h2 class="panel-title">Build the first impression</h2>
      </div>
    </header>

    <div class="content-pad">
      <form class="builder-form" @submit.prevent="$emit('save')">
        <label>
          Restaurant name
          <input :value="form.name" class="input" type="text" required @input="$emit('update-field', 'name', $event.target.value)" />
        </label>

        <label>
          Headline
          <input
            :value="form.headline"
            class="input"
            type="text"
            placeholder="Fresh coffee, calm tables, quick ordering."
            @input="$emit('update-field', 'headline', $event.target.value)"
          />
        </label>

        <label>
          Customer message
          <textarea
            :value="form.customerMessage"
            class="input kitchen-note"
            rows="4"
            placeholder="Tell customers what they should expect from your place..."
            @input="$emit('update-field', 'customerMessage', $event.target.value)"
          />
        </label>

        <div class="builder-split">
          <label>
            New brand color
            <input :value="newColor" class="input color-input" type="color" @input="$emit('update-new-color', $event.target.value)" />
          </label>
          <label>
            Logo image URL
            <input
              :value="form.logoUrl"
              class="input"
              type="url"
              placeholder="https://.../logo.png"
              @input="$emit('update-field', 'logoUrl', $event.target.value)"
            />
          </label>
        </div>

        <div>
          <div class="builder-color-head">
            <span class="selector-label">Brand colors</span>
            <button class="button button-soft" type="button" :disabled="form.brandColors.length >= 3" @click="$emit('add-color')">
              + Add color
            </button>
          </div>
          <div class="builder-color-list">
            <button
              v-for="(color, index) in form.brandColors"
              :key="`${color}-${index}`"
              class="builder-color-chip"
              :class="{ active: form.brandColor === color }"
              :style="{ '--chip-color': color }"
              type="button"
              @click="$emit('set-primary', color)"
            >
              <span class="builder-color-chip-swatch" />
              <strong>{{ color }}</strong>
              <span class="muted">{{ index === 0 ? "Primary" : "Accent" }}</span>
            </button>
          </div>
          <div class="quick-grid">
            <button
              v-for="color in form.brandColors"
              :key="`remove-${color}`"
              class="quick-chip remove"
              type="button"
              @click="$emit('remove-color', color)"
            >
              Remove {{ color }}
            </button>
          </div>
        </div>

        <div class="builder-split">
          <label>
            Restaurant phone
            <input :value="form.phone" class="input" type="text" placeholder="+251..." @input="$emit('update-field', 'phone', $event.target.value)" />
          </label>
          <label>
            Restaurant email
            <input :value="form.email" class="input" type="email" placeholder="hello@restaurant.com" @input="$emit('update-field', 'email', $event.target.value)" />
          </label>
        </div>

        <label>
          Address
          <input :value="form.address" class="input" type="text" placeholder="Street, area, city" @input="$emit('update-field', 'address', $event.target.value)" />
        </label>

        <p v-if="message" class="muted">{{ message }}</p>
        <p v-if="error" class="error-text">{{ error }}</p>

        <div class="builder-step-actions">
          <button class="button" type="submit" :disabled="saving">
            {{ saving ? "Saving..." : "Save first page and preview" }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
defineProps({
  form: { type: Object, required: true },
  newColor: { type: String, required: true },
  saving: { type: Boolean, default: false },
  error: { type: String, default: "" },
  message: { type: String, default: "" }
});

defineEmits(["update-field", "update-new-color", "add-color", "remove-color", "set-primary", "save"]);
</script>

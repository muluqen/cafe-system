<template>
  <section class="panel builder-preview-stage">
    <header class="panel-header panel-header-rich">
      <div>
        <span class="eyebrow">Step 2</span>
        <h2 class="panel-title">See what customers will see first</h2>
      </div>
    </header>

    <div class="content-pad">
      <CustomerExperiencePreview :form="form" :menu-items="menuItems" :tables="tables" />

      <div class="builder-step-actions between">
        <button class="button button-soft" type="button" @click="$emit('back')">
          Go back and edit
        </button>
        <button class="button" type="button" @click="$emit('next')">
          This looks good, next step
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import CustomerExperiencePreview from "./CustomerExperiencePreview.vue";

defineProps({
  form: { type: Object, required: true },
  menuItems: { type: Array, required: true },
  tables: { type: Array, required: true }
});

defineEmits(["back", "next"]);
</script>

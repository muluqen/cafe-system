<template>
  <div :class="['base-input', { 'base-input--error': error, 'base-input--disabled': disabled }]">
    <label v-if="label" class="base-input__label">
      {{ label }}
      <span v-if="required" class="base-input__required">*</span>
    </label>
    <div class="base-input__wrapper">
      <span v-if="icon" class="base-input__icon">{{ icon }}</span>
      <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        class="base-input__field"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
      />
    </div>
    <p v-if="error" class="base-input__error">{{ error }}</p>
    <p v-else-if="hint" class="base-input__hint">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  type: { type: String, default: 'text' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  icon: { type: String, default: '' },
});

defineEmits(['update:modelValue', 'blur', 'focus']);
</script>

<style scoped>
.base-input {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.base-input__label {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-secondary);
}

.base-input__required { color: var(--color-danger); }

.base-input__wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.base-input__icon {
  position: absolute;
  left: var(--space-3);
  color: var(--color-text-muted);
  font-size: var(--text-lg);
  pointer-events: none;
}

.base-input__field {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-subtle);
  color: var(--color-text-primary);
  font-family: var(--font-sans);
  font-size: var(--text-base);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
  outline: none;
}

.base-input__field:has(+ .base-input__icon),
.base-input__wrapper:has(.base-input__icon) .base-input__field {
  padding-left: var(--space-10);
}

.base-input__field::placeholder { color: var(--color-text-muted); }

.base-input__field:focus {
  border-color: var(--color-warm);
  box-shadow: 0 0 0 3px var(--color-warm-glow);
}

.base-input--error .base-input__field {
  border-color: var(--color-danger);
}
.base-input--error .base-input__field:focus {
  box-shadow: 0 0 0 3px rgba(244,63,94,0.12);
}

.base-input--disabled .base-input__field {
  background: var(--color-bg-muted);
  cursor: not-allowed;
}

.base-input__error { margin: 0; font-size: var(--text-xs); color: var(--color-danger); }
.base-input__hint { margin: 0; font-size: var(--text-xs); color: var(--color-text-muted); }
</style>

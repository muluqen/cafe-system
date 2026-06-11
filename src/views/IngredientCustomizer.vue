<template>
  <div class="customizer">
    <div class="customizer__header">
      <h3 class="customizer__title">Customize {{ menuItem.name }}</h3>
      <button class="customizer__close" @click="$emit('close')">✕</button>
    </div>

    <p v-if="menuItem.description" class="customizer__desc">{{ menuItem.description }}</p>

    <!-- Nutrition summary -->
    <div class="customizer__summary">
      <div class="summary-item">
        <span class="summary-item__value">{{ totalCalories.toFixed(0) }}</span>
        <span class="summary-item__label">kcal</span>
      </div>
      <div class="summary-item">
        <span class="summary-item__value">${{ totalPrice.toFixed(2) }}</span>
        <span class="summary-item__label">Total</span>
      </div>
    </div>

    <!-- Ingredients list -->
    <div class="customizer__ingredients">
      <p v-if="ingredients.length === 0" class="customizer__empty">No ingredients available to customize.</p>
      <div v-for="ri in ingredients" :key="ri.id" class="ingredient-row">
        <div class="ingredient-row__info">
          <span class="ingredient-row__name">{{ ri.name }}</span>
          <div class="ingredient-row__meta">
            <span class="ingredient-row__cal">{{ Math.round(ingredientCalories(ri.currentQty, ri.caloriesPerUnit, ri.unit)) }} kcal</span>
            <span class="ingredient-row__cost">${{ ingredientCost(ri.currentQty, ri.costPerUnit, ri.unit).toFixed(2) }}</span>
          </div>
        </div>
        <div class="ingredient-row__controls">
          <button class="qty-btn" @click="decrement(ri)">−</button>
          <span class="qty-value">{{ ri.currentQty.toFixed(ri.isWeight ? 1 : 0) }} {{ ri.unit }}</span>
          <button class="qty-btn" @click="increment(ri)">+</button>
        </div>
      </div>
    </div>

    <!-- Custom ingredient input -->
    <div class="customizer__custom">
      <p class="customizer__custom-label">Add your own amount</p>
      <p class="customizer__custom-hint">Adjust each ingredient to your preference</p>
    </div>

    <!-- Action -->
    <button class="customizer__add-btn" @click="addToCart">
      Add to Order · ${{ totalPrice.toFixed(2) }}
    </button>
  </div>
</template>

<script setup>
import { computed, reactive } from "vue";
import { useCartStore } from "../stores/cartStore";
import { ingredientCalories } from "../services/calorieService";
import { ingredientCost } from "../services/pricingService";

const props = defineProps({
  menuItem: { type: Object, required: true },
  recipeIngredients: { type: Array, default: () => [] },
  restaurantId: { type: [Number, String], required: true },
  restaurantName: { type: String, default: "" },
});

const emit = defineEmits(["close", "added"]);

const cartStore = useCartStore();

const ingredients = reactive(
  (props.recipeIngredients || []).filter((ri) => ri && ri.ingredient).map((ri) => {
    const ing = ri.ingredient;
    const unit = ing.unit || "g";
    const isWeight = ["g", "kg", "ml", "l", "liter"].includes(unit.toLowerCase());
    const defaultQty = parseFloat(ri.quantity_required || 0);
    return {
      id: ri.id,
      ingredient_id: ing.id,
      name: ing.name,
      unit,
      isWeight,
      defaultQty,
      currentQty: defaultQty,
      costPerUnit: parseFloat(ing.cost_per_unit || 0),
      caloriesPerUnit: parseFloat(ing.calories_per_unit || 0),
    };
  })
);

const totalCalories = computed(() =>
  ingredients.reduce((sum, ri) => sum + ingredientCalories(ri.currentQty, ri.caloriesPerUnit, ri.unit), 0)
);

const totalPrice = computed(() => {
  return ingredients.reduce((sum, ri) => {
    return sum + ingredientCost(ri.currentQty, ri.costPerUnit, ri.unit);
  }, 0);
});

function increment(ri) {
  ri.currentQty += ri.isWeight ? 10 : 1;
}

function decrement(ri) {
  const step = ri.isWeight ? 10 : 1;
  if (ri.currentQty > 0) {
    ri.currentQty = Math.max(0, ri.currentQty - step);
  }
}

function addToCart() {
  const customized = ingredients.map((ri) => ({
    ingredient_id: ri.ingredient_id,
    name: ri.name,
    quantity_required: ri.currentQty,
    default_quantity: ri.defaultQty,
    unit: ri.unit,
    cost_per_unit: ri.costPerUnit,
    calories_per_unit: ri.caloriesPerUnit,
  }));

  cartStore.addItem(props.menuItem, props.restaurantId, props.restaurantName, {
    customized_ingredients: customized.length > 0 ? customized : null,
    calories_per_item: Math.round(totalCalories.value),
    unit_price: totalPrice.value,
  });

  emit("added");
  emit("close");
}
</script>

<style scoped>
.customizer {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  max-height: 80vh;
  overflow-y: auto;
}

.customizer__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.customizer__title {
  margin: 0;
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.customizer__close {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  font-size: var(--text-lg);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.customizer__desc {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.customizer__summary {
  display: flex;
  gap: var(--space-4);
  padding: var(--space-4);
  background: linear-gradient(135deg, rgba(255, 60, 172, 0.08), rgba(6, 182, 212, 0.06));
  border: 1px solid rgba(255, 60, 172, 0.2);
  border-radius: var(--radius-lg);
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.summary-item__value {
  font-size: var(--text-xl);
  font-weight: var(--font-extrabold);
  color: var(--color-text-primary);
}

.summary-item__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.customizer__ingredients {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.ingredient-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-3) var(--space-4);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.ingredient-row__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ingredient-row__name {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-primary);
}

.ingredient-row__cal {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.ingredient-row__meta {
  display: flex;
  gap: var(--space-3);
}

.ingredient-row__cost {
  font-size: var(--text-xs);
  color: #FB923C;
  font-weight: var(--font-semibold);
}

.ingredient-row__controls {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.qty-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-size: var(--text-lg);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
}

.qty-btn:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-glow);
}

.qty-value {
  min-width: 60px;
  text-align: center;
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.customizer__custom {
  text-align: center;
  padding: var(--space-2) 0;
}

.customizer__custom-label {
  margin: 0;
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--color-text-secondary);
}

.customizer__custom-hint {
  margin: var(--space-1) 0 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.customizer__add-btn {
  width: 100%;
  padding: var(--space-4);
  border: none;
  border-radius: var(--radius-lg);
  background: var(--color-primary);
  color: var(--color-text-inverse);
  font-size: var(--text-base);
  font-weight: var(--font-bold);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.customizer__add-btn:hover {
  background: var(--color-primary-light);
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(255, 60, 172, 0.3);
}
</style>

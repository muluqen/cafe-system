/**
 * Calorie Service
 *
 * Calculates calories per ingredient and meal totals.
 * Formula: base calories + Σ(added qty × calories_per_unit) - Σ(removed qty × calories_per_unit)
 */

// ── Single Ingredient ──────────────────────────────────────

/**
 * Calories for a single ingredient based on quantity.
 * @param {number} quantity - amount used
 * @param {number} caloriesPerUnit - calories per unit (per 100g/100ml)
 * @param {string} unit - unit of measurement
 * @returns {number} calories
 */
export function ingredientCalories(quantity, caloriesPerUnit, unit) {
  const qty = normalizeQuantity(quantity, unit);
  return qty * (Number(caloriesPerUnit) || 0);
}

// ── Per-Ingredient Breakdown ───────────────────────────────

/**
 * Calculate calories for each ingredient in a recipe.
 * @param {Array} recipeIngredients - array of { quantity_required, calories_per_unit, unit, name }
 * @param {Array} [removedIngredients=[]] - names of ingredients customer removed
 * @param {Array} [addedIngredients=[]] - extra ingredients [{ quantity_required, calories_per_unit, unit, name }]
 * @returns {Array} [{ name, quantity, unit, calories }]
 */
export function ingredientCalorieBreakdown(recipeIngredients, removedIngredients = [], addedIngredients = []) {
  const breakdown = [];

  for (const ri of recipeIngredients) {
    if (removedIngredients.includes(ri.name)) continue;
    const cal = ingredientCalories(ri.quantity_required, ri.calories_per_unit, ri.unit);
    breakdown.push({
      name: ri.name,
      quantity: ri.quantity_required,
      unit: ri.unit,
      calories: cal,
      type: 'default',
    });
  }

  for (const ai of addedIngredients) {
    const cal = ingredientCalories(ai.quantity_required, ai.calories_per_unit, ai.unit);
    breakdown.push({
      name: ai.name,
      quantity: ai.quantity_required,
      unit: ai.unit,
      calories: cal,
      type: 'added',
    });
  }

  return breakdown;
}

// ── Meal Calories ──────────────────────────────────────────

/**
 * Total calories for a meal including customizations.
 * @param {number} baseCalories - menu item base calories
 * @param {Object} options
 * @param {Array} [options.recipeIngredients=[]] - default recipe
 * @param {Array} [options.removedIngredients=[]] - removed ingredient names
 * @param {Array} [options.addedIngredients=[]] - extra ingredients
 * @param {number} [options.quantity=1] - number of meals
 * @returns {number} total calories
 */
export function mealCalories(baseCalories, options = {}) {
  const {
    recipeIngredients = [],
    removedIngredients = [],
    addedIngredients = [],
    quantity = 1,
  } = options;

  const breakdown = ingredientCalorieBreakdown(recipeIngredients, removedIngredients, addedIngredients);
  const ingredientDelta = breakdown.reduce((sum, item) => sum + item.calories, 0);

  return (Number(baseCalories) + ingredientDelta) * quantity;
}

// ── Cart Totals ────────────────────────────────────────────

/**
 * Total calories for a cart of items.
 * Each item: { calories_per_item, quantity, customized_ingredients }
 * @param {Array} items
 * @returns {number}
 */
export function cartCalories(items) {
  return items.reduce((sum, item) => {
    const customized = item.customized_ingredients || [];
    const extraCal = customized.reduce((s, ci) => {
      return s + ingredientCalories(ci.quantity_required, ci.calories_per_unit, ci.unit);
    }, 0);
    const baseCal = Number(item.calories_per_item) || 0;
    return sum + (baseCal + extraCal) * item.quantity;
  }, 0);
}

// ── Build Your Own ─────────────────────────────────────────

/**
 * Total calories for build-your-own items.
 * @param {Array} allIngredients - array of { id, calories_per_unit, unit }
 * @param {Object} qtyMap - { ingredientId: quantity }
 * @returns {number}
 */
export function buildOwnCalories(allIngredients, qtyMap) {
  return allIngredients.reduce((sum, ing) => {
    const qty = qtyMap[ing.id] || 0;
    return sum + ingredientCalories(qty, ing.calories_per_unit, ing.unit);
  }, 0);
}

// ── Helpers ────────────────────────────────────────────────

function normalizeQuantity(quantity, unit) {
  const weightUnits = ['g', 'kg', 'ml', 'l', 'liter'];
  const isWeight = weightUnits.includes((unit || '').toLowerCase());
  return isWeight ? (Number(quantity) || 0) / 100 : (Number(quantity) || 0);
}

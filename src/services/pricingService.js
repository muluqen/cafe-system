/**
 * Pricing Service
 *
 * Calculates ingredient costs and meal totals.
 * Formula: base price + Σ(added qty × cost_per_unit) - Σ(removed qty × cost_per_unit)
 */

// ── Single Ingredient ──────────────────────────────────────

/**
 * Cost of a single ingredient based on quantity and unit cost.
 * @param {number} quantity - amount used
 * @param {number} costPerUnit - price per unit (e.g. per gram, per ml)
 * @param {string} unit - unit of measurement
 * @returns {number} cost
 */
export function ingredientCost(quantity, costPerUnit, unit) {
  const qty = normalizeQuantity(quantity, unit);
  return qty * (Number(costPerUnit) || 0);
}

// ── Per-Ingredient Breakdown ───────────────────────────────

/**
 * Calculate cost for each ingredient in a recipe.
 * @param {Array} recipeIngredients - array of { quantity_required, cost_per_unit, unit, name }
 * @param {Array} [removedIngredients=[]] - names of ingredients customer removed
 * @param {Array} [addedIngredients=[]] - array of { quantity_required, cost_per_unit, unit, name }
 * @returns {Array} [{ name, quantity, unit, cost }]
 */
export function ingredientCostBreakdown(recipeIngredients, removedIngredients = [], addedIngredients = []) {
  const breakdown = [];

  // Default recipe ingredients (minus removed)
  for (const ri of recipeIngredients) {
    if (removedIngredients.includes(ri.name)) continue;
    const cost = ingredientCost(ri.quantity_required, ri.cost_per_unit, ri.unit);
    breakdown.push({
      name: ri.name,
      quantity: ri.quantity_required,
      unit: ri.unit,
      cost,
      type: 'default',
    });
  }

  // Added/extra ingredients
  for (const ai of addedIngredients) {
    const cost = ingredientCost(ai.quantity_required, ai.cost_per_unit, ai.unit);
    breakdown.push({
      name: ai.name,
      quantity: ai.quantity_required,
      unit: ai.unit,
      cost,
      type: 'added',
    });
  }

  return breakdown;
}

// ── Meal Price ─────────────────────────────────────────────

/**
 * Total price of a meal including customizations.
 * @param {number} basePrice - menu item base price
 * @param {Object} options
 * @param {Array} [options.recipeIngredients=[]] - default recipe [{ quantity_required, cost_per_unit, unit, name }]
 * @param {Array} [options.removedIngredients=[]] - names of removed ingredients
 * @param {Array} [options.addedIngredients=[]] - extra ingredients [{ quantity_required, cost_per_unit, unit, name }]
 * @param {number} [options.upcharge=0] - manual upcharge
 * @param {number} [options.quantity=1] - number of meals
 * @returns {number} total price
 */
export function mealPrice(basePrice, options = {}) {
  const {
    recipeIngredients = [],
    removedIngredients = [],
    addedIngredients = [],
    upcharge = 0,
    quantity = 1,
  } = options;

  const breakdown = ingredientCostBreakdown(recipeIngredients, removedIngredients, addedIngredients);
  const ingredientDelta = breakdown.reduce((sum, item) => sum + item.cost, 0);

  return (Number(basePrice) + ingredientDelta + upcharge) * quantity;
}

// ── Cart Totals ────────────────────────────────────────────

/**
 * Subtotal for a cart of items.
 * Each item: { price, quantity, customized_ingredients, upcharge }
 * @param {Array} items
 * @returns {number}
 */
export function cartSubtotal(items) {
  return items.reduce((sum, item) => {
    return sum + (Number(item.price) || 0) * item.quantity;
  }, 0);
}

/**
 * Tax amount for a cart.
 * @param {Array} items
 * @param {number} [taxRate=0.10]
 * @returns {number}
 */
export function cartTax(items, taxRate = 0.10) {
  return cartSubtotal(items) * taxRate;
}

/**
 * Grand total for a cart.
 * @param {Array} items
 * @param {number} [taxRate=0.10]
 * @returns {number}
 */
export function cartGrandTotal(items, taxRate = 0.10) {
  const sub = cartSubtotal(items);
  return sub + sub * taxRate;
}

// ── Build Your Own ─────────────────────────────────────────

/**
 * Total price for build-your-own items.
 * @param {Array} allIngredients - array of { id, cost_per_unit, unit }
 * @param {Object} qtyMap - { ingredientId: quantity }
 * @returns {number}
 */
export function buildOwnPrice(allIngredients, qtyMap) {
  return allIngredients.reduce((sum, ing) => {
    const qty = qtyMap[ing.id] || 0;
    return sum + ingredientCost(qty, ing.cost_per_unit, ing.unit);
  }, 0);
}

/**
 * Count of selected ingredients in build-your-own.
 * @param {Array} allIngredients
 * @param {Object} qtyMap
 * @returns {number}
 */
export function buildOwnSelectedCount(allIngredients, qtyMap) {
  return allIngredients.filter((ing) => (qtyMap[ing.id] || 0) > 0).length;
}

/**
 * Selected ingredients formatted for order.
 * @param {Array} allIngredients - array of { id, name, cost_per_unit, calories_per_unit, unit }
 * @param {Object} qtyMap - { ingredientId: quantity }
 * @returns {Array} [{ ingredient_id, name, quantity_required, unit, cost_per_unit, calories_per_unit }]
 */
export function buildOwnSelectedIngredients(allIngredients, qtyMap) {
  return allIngredients
    .filter((ing) => (qtyMap[ing.id] || 0) > 0)
    .map((ing) => ({
      ingredient_id: ing.id,
      name: ing.name,
      quantity_required: qtyMap[ing.id],
      unit: ing.unit,
      cost_per_unit: ing.cost_per_unit || 0,
      calories_per_unit: ing.calories_per_unit || 0,
    }));
}

// ── Helpers ────────────────────────────────────────────────

function normalizeQuantity(quantity, unit) {
  const weightUnits = ['g', 'kg', 'ml', 'l', 'liter'];
  const isWeight = weightUnits.includes((unit || '').toLowerCase());
  return isWeight ? (Number(quantity) || 0) / 100 : (Number(quantity) || 0);
}

const TAX_RATE = 0.10;

const WEIGHT_UNITS = ["g", "kg", "ml", "l", "liter"];

function isWeightOrVolume(unit) {
  return WEIGHT_UNITS.includes((unit || "").toLowerCase());
}

// ── Single Ingredient ──────────────────────────────────────
// Both calories and cost: stored per 100g/100ml (USDA convention) → normalize for weight/volume

export function ingredientCalories(quantity, caloriesPerUnit, unit) {
  const qty = isWeightOrVolume(unit) ? (Number(quantity) || 0) / 100 : (Number(quantity) || 0);
  return qty * (Number(caloriesPerUnit) || 0);
}

export function ingredientCost(quantity, costPerUnit, unit) {
  const qty = isWeightOrVolume(unit) ? (Number(quantity) || 0) / 100 : (Number(quantity) || 0);
  return qty * (Number(costPerUnit) || 0);
}

// ── Customized Ingredients (array) ─────────────────────────

export function totalCustomizedCalories(customizedIngredients) {
  return (customizedIngredients || []).reduce(
    (sum, ci) => {
      const defaultQty = ci.default_quantity != null ? Number(ci.default_quantity) : null;
      const qty = defaultQty != null
        ? (Number(ci.quantity_required) || 0) - defaultQty
        : (Number(ci.quantity_required) || 0);
      return sum + ingredientCalories(qty, ci.calories_per_unit, ci.unit);
    },
    0
  );
}

export function totalCustomizedCost(customizedIngredients) {
  return (customizedIngredients || []).reduce(
    (sum, ci) => {
      const defaultQty = ci.default_quantity != null ? Number(ci.default_quantity) : null;
      const qty = defaultQty != null
        ? (Number(ci.quantity_required) || 0) - defaultQty
        : (Number(ci.quantity_required) || 0);
      return sum + ingredientCost(qty, ci.cost_per_unit, ci.unit);
    },
    0
  );
}

// ── Menu Item Price ────────────────────────────────────────

export function menuItemCalories(baseCalories, customizedIngredients) {
  const delta = totalCustomizedCalories(customizedIngredients);
  return (Number(baseCalories) || 0) + delta;
}

export function menuItemTotalPrice(basePrice, customizedIngredients, quantity = 1) {
  const base = Number(basePrice) || 0;
  const extra = totalCustomizedCost(customizedIngredients);
  return (base + extra) * quantity;
}

// ── Build Your Own ─────────────────────────────────────────

export function buildOwnCalories(allIngredients, qtyMap) {
  return allIngredients.reduce((sum, ing) => {
    const qty = qtyMap[ing.id] || 0;
    return sum + ingredientCalories(qty, ing.calories_per_unit, ing.unit);
  }, 0);
}

export function buildOwnPrice(allIngredients, qtyMap) {
  return allIngredients.reduce((sum, ing) => {
    const qty = qtyMap[ing.id] || 0;
    return sum + ingredientCost(qty, ing.cost_per_unit, ing.unit);
  }, 0);
}

export function buildOwnSelectedCount(allIngredients, qtyMap) {
  return allIngredients.filter((ing) => (qtyMap[ing.id] || 0) > 0).length;
}

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

// ── Cart Totals ────────────────────────────────────────────

export function cartSubtotal(items) {
  return items.reduce((sum, item) => {
    return sum + menuItemTotalPrice(item.price, item.customized_ingredients, item.quantity);
  }, 0);
}

export function cartCalories(items) {
  return items.reduce((sum, item) => {
    return sum + (Number(item.calories_per_item) || 0) * item.quantity;
  }, 0);
}

export function cartTax(items) {
  return cartSubtotal(items) * TAX_RATE;
}

export function cartGrandTotal(items) {
  const sub = cartSubtotal(items);
  return sub + sub * TAX_RATE;
}

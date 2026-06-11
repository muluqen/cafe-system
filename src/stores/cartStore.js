import { defineStore } from "pinia";
import {
  cartSubtotal,
  cartTax,
  cartGrandTotal,
} from "../services/pricingService";
import {
  cartCalories,
} from "../services/calorieService";

const CART_KEY = "tavliq_cart";

function loadCart() {
  try {
    const raw = localStorage.getItem(CART_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      return {
        items: Array.isArray(parsed.items) ? parsed.items : [],
        restaurant_id: parsed.restaurant_id || null,
        restaurant_name: parsed.restaurant_name || "",
        order_type: parsed.order_type || "dine_in",
        table_id: parsed.table_id || null,
      };
    }
  } catch {}
  return { items: [], restaurant_id: null, restaurant_name: "", order_type: "dine_in", table_id: null };
}

export const useCartStore = defineStore("cartStore", {
  state: () => ({
    ...loadCart(),
  }),

  getters: {
    totalItems: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
    totalPrice: (state) => cartSubtotal(state.items),
    totalCalories: (state) => cartCalories(state.items),
    isEmpty: (state) => state.items.length === 0,
    taxAmount: (state) => cartTax(state.items),
    grandTotal: (state) => cartGrandTotal(state.items),
  },

  actions: {
    _persist() {
      localStorage.setItem(
        CART_KEY,
        JSON.stringify({
          items: this.items,
          restaurant_id: this.restaurant_id,
          restaurant_name: this.restaurant_name,
          order_type: this.order_type,
          table_id: this.table_id,
        })
      );
    },

    itemCount(menuItemId) {
      const item = this.items.find((i) => i.menu_item_id === menuItemId);
      return item ? item.quantity : 0;
    },

    addItem(menuItem, restaurantId, restaurantName, options = {}) {
      const normalizedRestaurantId = restaurantId != null ? Number(restaurantId) : null;
      if (this.restaurant_id && normalizedRestaurantId && this.restaurant_id !== normalizedRestaurantId) {
        this.clearCart();
      }

      this.restaurant_id = normalizedRestaurantId;
      if (restaurantName) this.restaurant_name = restaurantName;

      const customizedIngredients = options.customized_ingredients || null;
      const caloriesPerItem = options.calories_per_item || 0;
      const unitPrice = options.unit_price ?? parseFloat(menuItem.price);

      if (customizedIngredients) {
        this.items.push({
          menu_item_id: menuItem.id,
          name: menuItem.name,
          price: unitPrice,
          quantity: 1,
          restaurant_id: normalizedRestaurantId,
          customized_ingredients: customizedIngredients,
          calories_per_item: caloriesPerItem,
        });
      } else {
        const existing = menuItem.id != null
          ? this.items.find((i) => i.menu_item_id === menuItem.id && !i.customized_ingredients)
          : null;
        if (existing) {
          existing.quantity++;
        } else {
          this.items.push({
            menu_item_id: menuItem.id,
            name: menuItem.name,
            price: unitPrice,
            quantity: 1,
            restaurant_id: normalizedRestaurantId,
            customized_ingredients: null,
            calories_per_item: caloriesPerItem,
          });
        }
      }
      this._persist();
    },

    removeItem(menuItemId) {
      this.items = this.items.filter((i) => i.menu_item_id !== menuItemId);
      if (this.items.length === 0) {
        this.restaurant_id = null;
        this.restaurant_name = "";
        this.table_id = null;
      }
      this._persist();
    },

    incrementItem(menuItemId) {
      const item = this.items.find((i) => i.menu_item_id === menuItemId);
      if (item) {
        item.quantity++;
        this._persist();
      }
    },

    decrementItem(menuItemId) {
      const item = this.items.find((i) => i.menu_item_id === menuItemId);
      if (item) {
        item.quantity--;
        if (item.quantity <= 0) this.removeItem(menuItemId);
        else this._persist();
      }
    },

    setOrderType(type) {
      this.order_type = type;
      this._persist();
    },

    setTable(tableId) {
      this.table_id = tableId;
      this._persist();
    },

    setRestaurantId(id) {
      this.restaurant_id = id;
      this._persist();
    },

    clearCart() {
      this.items = [];
      this.restaurant_id = null;
      this.restaurant_name = "";
      this.order_type = "dine_in";
      this.table_id = null;
      this._persist();
    },
  },
});

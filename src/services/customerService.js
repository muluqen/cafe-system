import api from "./api";

export default {
  // ── Restaurants ──────────────────────────────────────────
  getRestaurants(filters = {}) {
    return api.get("/restaurants", { params: { status: "active", ...filters } });
  },

  getRestaurant(id) {
    return api.get(`/public/restaurants/${id}`);
  },

  // ── Menu ─────────────────────────────────────────────────
  getMenuItems(restaurantId) {
    return api.get(`/public/restaurants/${restaurantId}/menu_items`);
  },

  getMenuCategories(restaurantId) {
    return api.get(`/public/restaurants/${restaurantId}/menu_categories`);
  },

  // ── Ingredients (for customization) ──────────────────────
  getIngredients(restaurantId) {
    return api.get(`/public/restaurants/${restaurantId}/ingredients`);
  },

  // ── Restaurant Settings (public) ─────────────────────────
  getRestaurantSettings(restaurantId) {
    return api.get(`/public/restaurants/${restaurantId}/settings`);
  },

  // ── Orders ───────────────────────────────────────────────
  placeOrder(orderData) {
    return api.post("/checkout/process", orderData);
  },

  getMyOrders() {
    return api.get("/orders");
  },

  getOrder(id) {
    return api.get(`/orders/${id}`);
  },

  // ── Feedback ─────────────────────────────────────────────
  submitFeedback(data) {
    return api.post("/feedback", data);
  },

  updateFeedback(id, data) {
    return api.put(`/feedback/${id}`, data);
  },

  getMyFeedback() {
    return api.get("/feedback");
  },

  getRestaurantFeedback(restaurantId) {
    return api.get("/feedback", { params: { restaurant_id: restaurantId } });
  },
};

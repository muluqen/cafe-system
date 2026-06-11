import api from "./api";

export default {
  // ── Stats ────────────────────────────────────────────────
  getStats() {
    return api.get("/admin/stats");
  },

  // ── Restaurants ──────────────────────────────────────────
  getPendingRestaurants() {
    return api.get("/admin/restaurants/pending");
  },

  getAllRestaurants(status = "") {
    const params = {};
    if (status) params.status = status;
    return api.get("/admin/restaurants", { params });
  },

  approveRestaurant(id) {
    return api.post(`/admin/restaurants/${id}/approve`);
  },

  rejectRestaurant(id, reason) {
    return api.post(`/admin/restaurants/${id}/reject`, { reason });
  },

  suspendRestaurant(id, reason) {
    return api.post(`/admin/restaurants/${id}/suspend`, { reason });
  },

  reactivateRestaurant(id) {
    return api.post(`/admin/restaurants/${id}/reactivate`);
  },

  // ── Users ────────────────────────────────────────────────
  getAllUsers(filters = {}) {
    return api.get("/admin/users", { params: filters });
  },

  // ── Notifications ────────────────────────────────────────
  getNotifications() {
    return api.get("/admin/notifications");
  },

  markNotificationRead(id) {
    return api.post(`/admin/notifications/${id}/read`);
  },

  // ── Analytics ────────────────────────────────────────────
  getPlatformAnalytics(period = "week") {
    return api.get("/admin/analytics", { params: { period } });
  },
};

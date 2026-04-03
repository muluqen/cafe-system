import { defineStore } from "pinia";
import api from "../services/api";

const TOKEN_KEY = "cafe_auth_token";
const USER_KEY = "cafe_auth_user";
const RESTAURANT_KEY = "cafe_selected_restaurant_id";

export const useAuthStore = defineStore("authStore", {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY) || "",
    user: JSON.parse(localStorage.getItem(USER_KEY) || "null"),
    selectedRestaurantId: localStorage.getItem(RESTAURANT_KEY) || "",
    loading: false,
    error: "",
    publicRestaurants: []
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role || "",
    staffRole: (state) => state.user?.staff_role || "",
    isRestaurant: (state) => state.user?.role === "restaurant",
    isCustomer: (state) => state.user?.role === "customer"
  },
  actions: {
    persist() {
      if (this.token) {
        localStorage.setItem(TOKEN_KEY, this.token);
      } else {
        localStorage.removeItem(TOKEN_KEY);
      }

      if (this.user) {
        localStorage.setItem(USER_KEY, JSON.stringify(this.user));
      } else {
        localStorage.removeItem(USER_KEY);
      }

      if (this.selectedRestaurantId) {
        localStorage.setItem(RESTAURANT_KEY, String(this.selectedRestaurantId));
      } else {
        localStorage.removeItem(RESTAURANT_KEY);
      }
    },
    async loadPublicRestaurants() {
      const { data } = await api.get("/auth/restaurants");
      this.publicRestaurants = Array.isArray(data) ? data : [];
    },
    async login(payload) {
      return this.submitAuth("/auth/login", payload);
    },
    async register(payload) {
      return this.submitAuth("/auth/register", payload);
    },
    async registerRestaurant(payload) {
      return this.submitAuth("/auth/register-restaurant", payload);
    },
    async submitAuth(endpoint, payload) {
      this.loading = true;
      this.error = "";

      try {
        const { data } = await api.post(endpoint, payload);
        this.token = data.token;
        this.user = data.user;

        if (this.user?.role === "restaurant") {
          this.selectedRestaurantId = String(this.user.restaurant_id || "");
        }

        this.persist();
        return data;
      } catch (error) {
        const errors = error?.response?.data?.errors;
        this.error =
          errors
            ? Object.values(errors).flat().join(" ")
            : error?.response?.data?.message || "Authentication failed";
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async fetchMe() {
      if (!this.token) {
        return;
      }

      try {
        const { data } = await api.get("/auth/me");
        this.user = data;
        this.persist();
      } catch {
        this.logoutLocal();
      }
    },
    setSelectedRestaurant(id) {
      this.selectedRestaurantId = String(id || "");
      this.persist();
    },
    async logout() {
      try {
        if (this.token) {
          await api.post("/auth/logout");
        }
      } finally {
        this.logoutLocal();
      }
    },
    logoutLocal() {
      this.token = "";
      this.user = null;
      this.selectedRestaurantId = "";
      this.error = "";
      this.persist();
    }
  }
});

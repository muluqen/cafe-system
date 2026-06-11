import { defineStore } from "pinia";
import api from "../services/api";
import router from "../router";

const TOKEN_KEY = "tavliq_token";
const USER_KEY = "cafe_auth_user";
const RESTAURANT_KEY = "cafe_selected_restaurant_id";
const RESTAURANT_WELCOME_KEY = "cafe_restaurant_welcome_notice";

export const useAuthStore = defineStore("authStore", {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY) || "",
    user: JSON.parse(localStorage.getItem(USER_KEY) || "null"),
    selectedRestaurantId: localStorage.getItem(RESTAURANT_KEY) || "",
    restaurantWelcomeNotice: sessionStorage.getItem(RESTAURANT_WELCOME_KEY) || "",
    loading: false,
    error: "",
    publicRestaurants: [],
    justLoggedIn: false,
    permissions: JSON.parse(localStorage.getItem("tavliq_permissions") || "{}"),
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role || "",
    staffRole: (state) => state.user?.staff_role || "",
    isRestaurant: (state) => state.user?.role === "restaurant",
    isCustomer: (state) => state.user?.role === "customer",
    isSuperAdmin: (state) => state.user?.is_super_admin === true,
    isStaff: (state) => state.user?.role === "restaurant" && ["manager", "floor_manager", "cashier", "server", "kitchen", "barista", "host", "inventory"].includes(state.user?.staff_role),
    canRead: (state) => (entityKey) => {
      if (state.user?.is_super_admin) return true;
      const keys = Object.keys(state.permissions);
      if (keys.length === 0) return false;
      if (entityKey === 'restaurant_settings') {
        const subKeys = ['restaurant_settings_branding','restaurant_settings_special','restaurant_settings_announcements','restaurant_settings_contact','restaurant_settings_hours'];
        return subKeys.some(k => state.permissions[k]?.can_read);
      }
      const mapped = entityKey === 'tables' ? 'dining_tables' : entityKey;
      const p = state.permissions[mapped] || state.permissions[entityKey];
      return p ? !!p.can_read : false;
    },
    canWrite: (state) => (entityKey) => {
      if (state.user?.is_super_admin) return true;
      const keys = Object.keys(state.permissions);
      if (keys.length === 0) return false;
      if (entityKey === 'restaurant_settings') {
        const subKeys = ['restaurant_settings_branding','restaurant_settings_special','restaurant_settings_announcements','restaurant_settings_contact','restaurant_settings_hours'];
        return subKeys.some(k => state.permissions[k]?.can_write);
      }
      const mapped = entityKey === 'tables' ? 'dining_tables' : entityKey;
      const p = state.permissions[mapped] || state.permissions[entityKey];
      return p ? !!p.can_write : false;
    },
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

      if (this.restaurantWelcomeNotice) {
        sessionStorage.setItem(RESTAURANT_WELCOME_KEY, this.restaurantWelcomeNotice);
      } else {
        sessionStorage.removeItem(RESTAURANT_WELCOME_KEY);
      }

      if (this.permissions && Object.keys(this.permissions).length > 0) {
        localStorage.setItem("tavliq_permissions", JSON.stringify(this.permissions));
      } else {
        localStorage.removeItem("tavliq_permissions");
      }
    },
    async loadPublicRestaurants() {
      const { data } = await api.get("/auth/restaurants");
      const raw = data?.data ?? data;
      this.publicRestaurants = Array.isArray(raw) ? raw : [];
    },
    async login(payload, { redirect = true } = {}) {
      return this.submitAuth("/auth/login", payload, { redirect });
    },
    async register(payload) {
      return this.submitAuth("/auth/register", payload);
    },
    async registerRestaurant(payload) {
      return this.submitAuth("/auth/register-restaurant", payload);
    },
    async submitAuth(endpoint, payload, { redirect = true } = {}) {
      this.loading = true;
      this.error = "";

      try {
        const { data } = await api.post(endpoint, payload);
        // API wraps in { success, data: { token, user, permissions } }
        const payload_data = data.data || data;
        this.token = payload_data.token;
        this.user = payload_data.user;
        this.justLoggedIn = true;

        if (payload_data.permissions) {
          this.permissions = payload_data.permissions;
        }

        if (this.user?.role === "restaurant") {
          this.selectedRestaurantId = String(this.user.restaurant_id || "");
        }

        this.persist();

        if (this.isRestaurant && this.user.restaurant_id && !payload_data.permissions) {
          try {
            const permRes = await api.get('/rbac/my-permissions');
            this.permissions = permRes.data?.data?.permissions || permRes.data?.permissions || {};
            this.persist();
          } catch(e) {
            console.error("Failed to load RBAC permissions");
          }
        }

        // Redirect based on role after successful login/register
        if (redirect) {
          if (this.user?.is_super_admin === true) {
            router.push("/admin/dashboard");
          } else if (this.user?.staff_role) {
            const role = this.user.staff_role;
            if (["manager", "floor_manager"].includes(role)) {
              router.push("/app/dashboard");
            } else if (["cashier", "server", "host"].includes(role)) {
              router.push("/app/pos");
            } else if (["kitchen", "barista"].includes(role)) {
              router.push("/app/kitchen");
            } else if (role === "inventory") {
              router.push("/app/manage/ingredients");
            } else {
              router.push("/app/dashboard");
            }
          } else {
            router.push("/discover");
          }
        }

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
        // API wraps in { success, data: { user: {...}, permissions: {...} } }
        const userData = data.data?.user || data.data || data;
        this.user = userData;

        const permData = data.data?.permissions || data.permissions;
        if (permData) {
          this.permissions = permData;
        }
        
        if (this.isRestaurant && this.user.restaurant_id && !permData) {
          try {
            const permRes = await api.get('/rbac/my-permissions');
            this.permissions = permRes.data?.data?.permissions || permRes.data?.permissions || {};
          } catch(e) {
            console.error("Failed to load RBAC permissions");
          }
        }

        this.persist();
      } catch (e) {
        console.error("fetchMe failed:", e);
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
      this.restaurantWelcomeNotice = "";
      this.error = "";
      this.justLoggedIn = false;
      this.permissions = {};
      localStorage.removeItem("tavliq_permissions");
      this.persist();
    },
    setRestaurantWelcomeNotice(message) {
      this.restaurantWelcomeNotice = message || "";
      this.persist();
    },
    consumeRestaurantWelcomeNotice() {
      const message = this.restaurantWelcomeNotice;
      this.restaurantWelcomeNotice = "";
      this.persist();
      return message;
    },
    consumeLoginState() {
      this.justLoggedIn = false;
    }
  }
});

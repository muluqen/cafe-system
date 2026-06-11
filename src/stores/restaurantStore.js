import { defineStore } from "pinia";
import api from "../services/api";

export const useRestaurantStore = defineStore("restaurantStore", {
  state: () => ({
    restaurants: [],
    loading: false,
    error: "",
  }),
  getters: {
    filteredRestaurants: (state) => {
      return (query) => {
        if (!query || !query.trim()) return state.restaurants;
        const q = query.toLowerCase().trim();
        return state.restaurants.filter(
          (r) =>
            r.name.toLowerCase().includes(q) ||
            (r.cuisine_type && r.cuisine_type.toLowerCase().includes(q)) ||
            (r.location && r.location.toLowerCase().includes(q))
        );
      };
    },
  },
  actions: {
    async fetchRestaurants() {
      this.loading = true;
      this.error = "";
      try {
        const { data } = await api.get("/restaurants");
        this.restaurants = Array.isArray(data) ? data : data?.data || [];
      } catch (err) {
        this.error = err?.response?.data?.message || "Unable to load restaurants.";
      } finally {
        this.loading = false;
      }
    },
  },
});

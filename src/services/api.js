import axios from "axios";
import { useCartStore } from "../stores/cartStore";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api/v1",
  headers: {
    Accept: "application/json"
  }
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("tavliq_token");
  const selectedRestaurantId = localStorage.getItem("cafe_selected_restaurant_id");
  const url = config.url || ""
  const isPublicEndpoint = url.startsWith("/public/") || url.startsWith("/auth/login") || url.startsWith("/auth/register")

  if (token && !isPublicEndpoint) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Prefer cart store restaurant_id (customer flow) over localStorage (staff flow)
  try {
    const cartStore = useCartStore();
    if (cartStore.restaurant_id) {
      config.headers["X-Restaurant-Id"] = cartStore.restaurant_id;
    } else if (selectedRestaurantId) {
      config.headers["X-Restaurant-Id"] = selectedRestaurantId;
    }
  } catch {
    if (selectedRestaurantId) {
      config.headers["X-Restaurant-Id"] = selectedRestaurantId;
    }
  }

  return config;
});

// Response interceptor: on 401 → clear auth, redirect to /login
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const url = error.config?.url || ""
    const isPublicEndpoint = url.startsWith("/public/") || url.startsWith("/auth/login") || url.startsWith("/auth/register")

    if (error.response?.status === 401 && !isPublicEndpoint) {
      localStorage.removeItem("tavliq_token");
      localStorage.removeItem("cafe_auth_user");
      window.location.href = "/login";
    }
    return Promise.reject(error);
  }
);

export default api;

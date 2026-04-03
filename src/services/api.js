import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api",
  headers: {
    Accept: "application/json"
  }
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("cafe_auth_token");
  const selectedRestaurantId = localStorage.getItem("cafe_selected_restaurant_id");

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  if (selectedRestaurantId) {
    config.headers["X-Restaurant-Id"] = selectedRestaurantId;
  }

  return config;
});

export default api;

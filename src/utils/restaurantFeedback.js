const CUSTOMER_FEEDBACK_KEY = "cafe_customer_feedback";

export function customerFeedbackHistoryKey(userId, restaurantId) {
  return `${userId || "guest"}:${restaurantId || "none"}`;
}

function readFeedbackStore() {
  try {
    return JSON.parse(localStorage.getItem(CUSTOMER_FEEDBACK_KEY) || "{}");
  } catch {
    return {};
  }
}

function writeFeedbackStore(store) {
  localStorage.setItem(CUSTOMER_FEEDBACK_KEY, JSON.stringify(store));
}

export function getCustomerFeedbackHistory(userId, restaurantId) {
  const store = readFeedbackStore();
  return store[customerFeedbackHistoryKey(userId, restaurantId)] || [];
}

export function saveCustomerFeedbackHistory(userId, restaurantId, entries) {
  const store = readFeedbackStore();
  store[customerFeedbackHistoryKey(userId, restaurantId)] = entries;
  writeFeedbackStore(store);
}

export function getRestaurantFeedbackFeed(restaurantId) {
  const store = readFeedbackStore();
  return Object.values(store)
    .flatMap((entries) => (Array.isArray(entries) ? entries : []))
    .filter((entry) => String(entry.restaurantId || "") === String(restaurantId || ""))
    .sort((left, right) => new Date(right.createdAt) - new Date(left.createdAt));
}

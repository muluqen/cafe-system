<template>
  <div class="discover">
    <!-- Hero Search Bar -->
    <section class="discover-hero">
      <h1 class="discover-hero__title">Find your next meal</h1>
      <p class="discover-hero__subtitle">Discover restaurants near you</p>
      <div class="discover-search">
        <span class="discover-search__icon">🔍</span>
        <input
          v-model="searchQuery"
          class="discover-search__input"
          type="text"
          placeholder="Search restaurants..."
        />
      </div>
    </section>

    <!-- Cuisine Filter Chips -->
    <div class="discover-filters">
      <button
        v-for="chip in cuisineChips"
        :key="chip"
        :class="['chip', { 'chip--active': selectedCuisine === chip }]"
        @click="selectedCuisine = chip"
      >
        {{ chip }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="discover-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card">
        <SkeletonLoader type="card" :lines="2" />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredRestaurants.length === 0" class="discover-empty">
      <div class="discover-empty__icon">🍽</div>
      <h3 class="discover-empty__title">No restaurants found</h3>
      <p class="discover-empty__text">Try a different search or filter</p>
    </div>

    <!-- Restaurant Grid -->
    <div v-else class="discover-grid stagger-children">
      <RouterLink
        v-for="r in filteredRestaurants"
        :key="r.id"
        :to="`/restaurant/${r.id}`"
        class="restaurant-card animate-on-scroll"
        :style="cardStyle(r)"
      >
        <div class="restaurant-card__banner" :style="bannerStyle(r)">
          <img
            v-if="r.settings?.logo_path"
            :src="r.settings.logo_path"
            :alt="r.name + ' logo'"
            class="restaurant-card__logo"
          />
          <span v-if="r.settings?.motto" class="restaurant-card__motto">{{ r.settings.motto }}</span>
        </div>
        <div class="restaurant-card__body">
          <h3 class="restaurant-card__name">{{ r.name }}</h3>
          <span v-if="r.cuisine_type" class="restaurant-card__cuisine" :style="cuisineStyle(r)">{{ r.cuisine_type }}</span>
          <p v-if="r.description" class="restaurant-card__desc">{{ r.description }}</p>
          <div class="restaurant-card__footer">
            <span v-if="r.rating" class="restaurant-card__rating">⭐ {{ Number(r.rating).toFixed(1) }}</span>
            <span v-if="r.location" class="restaurant-card__location">{{ r.location }}</span>
          </div>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import customerService from "../services/customerService";
import SkeletonLoader from "../components/ui/SkeletonLoader.vue";

const restaurants = ref([]);
const loading = ref(true);
const searchQuery = ref("");
const selectedCuisine = ref("All");

const cuisineChips = [
  "All", "Coffee & Cafe", "Fast Food", "Fine Dining", "Pizza",
  "Asian", "Middle Eastern", "Ethiopian", "Burgers", "Desserts",
];

const filteredRestaurants = computed(() => {
  let result = restaurants.value;

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(
      (r) =>
        r.name?.toLowerCase().includes(q) ||
        r.cuisine_type?.toLowerCase().includes(q) ||
        r.location?.toLowerCase().includes(q)
    );
  }

  if (selectedCuisine.value !== "All") {
    result = result.filter(
      (r) => r.cuisine_type?.toLowerCase().includes(selectedCuisine.value.toLowerCase())
    );
  }

  return result;
});

function cardStyle(r) {
  const colors = r.settings?.brand_colors;
  if (!colors?.primary) return {};
  return {
    '--card-primary': colors.primary,
    '--card-secondary': colors.secondary || colors.primary,
    '--card-accent': colors.accent || colors.primary,
  };
}

function bannerStyle(r) {
  const colors = r.settings?.brand_colors;
  if (!colors?.primary) {
    return { background: 'linear-gradient(135deg, #F97316, #EA580C)' };
  }
  return {
    background: `linear-gradient(135deg, ${colors.primary}, ${colors.secondary || colors.accent || colors.primary})`,
  };
}

function cuisineStyle(r) {
  const colors = r.settings?.brand_colors;
  if (!colors?.primary) {
    return { background: 'rgba(249,115,22,0.12)', color: '#F97316' };
  }
  return {
    background: colors.primary + '20',
    color: colors.primary,
  };
}

let debounceTimer = null;
watch(searchQuery, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {}, 400);
});

onMounted(async () => {
  try {
    const { data } = await customerService.getRestaurants();
    restaurants.value = data?.data || data || [];
  } catch (e) {
    console.error("Failed to load restaurants", e);
  } finally {
    loading.value = false;
  }

  await nextTick();

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.05 })
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el)
  })
});
</script>

<style scoped>
.discover {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--space-6);
}

/* ── Hero ── */
.discover-hero {
  text-align: center;
  padding: var(--space-12) var(--space-4) var(--space-8);
  background: linear-gradient(180deg, var(--color-bg-elevated), var(--color-bg));
  border-radius: var(--radius-2xl);
  margin-bottom: var(--space-6);
}

.discover-hero__title {
  margin: 0 0 var(--space-2);
  font-size: var(--text-3xl);
  font-weight: var(--font-extrabold);
  color: var(--color-text-primary);
}

.discover-hero__subtitle {
  margin: 0 0 var(--space-6);
  color: var(--color-text-secondary);
}

.discover-search {
  position: relative;
  max-width: 600px;
  margin: 0 auto;
}

.discover-search__icon {
  position: absolute;
  left: var(--space-4);
  top: 50%;
  transform: translateY(-50%);
  font-size: var(--text-lg);
  pointer-events: none;
}

.discover-search__input {
  width: 100%;
  padding: var(--space-4) var(--space-4) var(--space-4) var(--space-12);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  background: var(--color-bg-elevated);
  color: var(--color-text-primary);
  font-size: var(--text-base);
  outline: none;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.discover-search__input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-glow);
}

.discover-search__input::placeholder {
  color: var(--color-text-muted);
}

/* ── Filters ── */
.discover-filters {
  display: flex;
  gap: var(--space-2);
  overflow-x: auto;
  padding-bottom: var(--space-4);
  margin-bottom: var(--space-6);
  -webkit-overflow-scrolling: touch;
}

.chip {
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  white-space: nowrap;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.chip:hover {
  border-color: var(--color-primary);
  color: var(--color-text-primary);
}

.chip--active {
  background: rgba(255,60,172,0.15);
  border-color: var(--color-primary);
  color: var(--color-primary-light);
}

/* ── Grid ── */
.discover-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-6);
}

.skeleton-card {
  border-radius: var(--radius-xl);
  overflow: hidden;
}

/* ── Restaurant Card ── */
.restaurant-card {
  display: flex;
  flex-direction: column;
  border-radius: var(--radius-xl);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: all var(--transition-base);
}

.restaurant-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px var(--card-primary-glow, rgba(255,60,172,0.15));
  border-color: var(--card-primary-border, rgba(255,60,172,0.3));
}

.restaurant-card__banner {
  height: 120px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  padding: 0 var(--space-4);
  gap: var(--space-3);
}

.restaurant-card__logo {
  width: 44px;
  height: 44px;
  object-fit: contain;
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.95);
  padding: 3px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  flex-shrink: 0;
}

.restaurant-card__motto {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: rgba(255,255,255,0.9);
  text-shadow: 0 1px 4px rgba(0,0,0,0.4);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.restaurant-card__gradient {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
  opacity: 0.8;
}

.restaurant-card__body {
  padding: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  flex: 1;
}

.restaurant-card__name {
  margin: 0;
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.restaurant-card__cuisine {
  display: inline-block;
  padding: 2px 10px;
  border-radius: var(--radius-full);
  background: rgba(6, 182, 212, 0.15);
  color: var(--color-accent);
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  width: fit-content;
}

.restaurant-card__desc {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.restaurant-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: var(--space-2);
}

.restaurant-card__rating {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.restaurant-card__location {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

/* ── Empty ── */
.discover-empty {
  text-align: center;
  padding: var(--space-16) var(--space-4);
}

.discover-empty__icon {
  font-size: 3rem;
  margin-bottom: var(--space-4);
}

.discover-empty__title {
  margin: 0 0 var(--space-2);
  font-size: var(--text-xl);
  font-weight: var(--font-bold);
  color: var(--color-text-primary);
}

.discover-empty__text {
  margin: 0;
  color: var(--color-text-muted);
}

@media (max-width: 1024px) {
  .discover-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  .discover-grid { grid-template-columns: 1fr; }
  .discover-hero__title { font-size: var(--text-2xl); }
}
</style>

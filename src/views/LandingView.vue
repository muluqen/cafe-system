<template>
  <div class="landing-page">
    <!-- ═══════════ NAVBAR ═══════════ -->
    <header class="navbar">
      <div class="navbar__inner">
        <RouterLink to="/" class="navbar__brand">🍽 <span class="navbar__glow">Tavliq</span></RouterLink>
        <nav class="navbar__nav">
          <RouterLink to="/login" class="navbar__link">Login</RouterLink>
        </nav>
      </div>
    </header>

    <!-- ═══════════ HERO ═══════════ -->
    <section class="hero">
      <div class="particles-container">
        <span v-for="p in particles" :key="p.emoji" class="particle" :style="{ left: p.x + '%', animationDelay: p.delay + 's', animationDuration: p.duration + 's' }">{{ p.emoji }}</span>
      </div>

      <div class="hero__grid">
        <div class="hero__left">
          <span class="hero__pill">Your table is always ready</span>
          <h1 class="hero__title">
            <span class="hero-line hero-line--1">Food made</span>
            <span class="hero-line hero-line--2">exactly how</span>
            <span class="hero-line hero-line--3 hero-line--gradient">you want it.</span>
          </h1>
          <p class="hero__subtitle">Customize your meals ingredient by ingredient. Track your calories. Discover restaurants that cook the way you like, and they'll remember you every time.</p>
          <div class="hero__benefits stagger-children">
            <span class="hero__benefit animate-on-scroll">🥗 Choose your ingredients</span>
            <span class="hero__benefit animate-on-scroll">🔥 Track your calories</span>
            <span class="hero__benefit animate-on-scroll">⭐ Earn loyalty rewards</span>
          </div>
          <div class="hero__cta">
            <RouterLink to="/discover" class="hero__btn hero__btn--amber animate-on-scroll">Start Ordering</RouterLink>
            <RouterLink to="/login" class="hero__btn hero__btn--ghost animate-on-scroll">I have an account</RouterLink>
          </div>
        </div>

        <div class="hero__right">
          <div class="card-glow-wrapper animate-on-scroll">
            <div class="meal-card">
              <div class="meal-card__header"><span class="meal-card__title">🍝 Build Your Meal</span><span class="meal-card__badge">Tavliq</span></div>
              <div class="meal-card__dish"><h3 class="meal-card__dish-name">Grilled Chicken Bowl</h3><span class="meal-card__dish-style">Mediterranean Style</span></div>
              <div class="meal-card__section">
                <span class="meal-card__label">Ingredients</span>
                <div class="meal-card__ingredients">
                  <div class="meal-card__ingredient"><div class="meal-card__ingredient-left"><span class="meal-dot meal-dot--green"></span><span class="meal-card__ingredient-name">Grilled Chicken</span></div><span class="meal-tag meal-tag--green">Added ✓</span></div>
                  <div class="meal-card__ingredient"><div class="meal-card__ingredient-left"><span class="meal-dot meal-dot--amber"></span><span class="meal-card__ingredient-name">Cherry Tomatoes</span></div><span class="meal-tag meal-tag--green">Added ✓</span></div>
                  <div class="meal-card__ingredient"><div class="meal-card__ingredient-left"><span class="meal-dot meal-dot--red"></span><span class="meal-card__ingredient-name">Red Onions</span></div><span class="meal-tag meal-tag--red">Remove</span></div>
                </div>
              </div>
              <div class="meal-card__section">
                <span class="meal-card__label">Calories</span>
                <div class="meal-card__calorie-bar"><div class="meal-card__calorie-fill"></div></div>
                <div class="meal-card__calorie-info"><span class="meal-card__calorie-value">487 kcal</span><span class="meal-card__calorie-goal">750 kcal goal</span></div>
              </div>
              <div class="meal-card__bottom">
                <div class="meal-card__loyalty"><span class="meal-card__loyalty-text">You're family here ✦</span><span class="meal-card__stars"><span class="star" style="animation-delay:0s">⭐</span><span class="star" style="animation-delay:0.1s">⭐</span><span class="star" style="animation-delay:0.2s">⭐</span><span class="star" style="animation-delay:0.3s">⭐</span><span class="star" style="animation-delay:0.4s">⭐</span></span></div>
                <RouterLink to="/discover" class="meal-card__order-btn">Order Now</RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="section-fade" style="height:80px;background:linear-gradient(180deg,#09090B 0%,#09090B 100%);margin:-1px 0"></div>

    <!-- ═══════════ WE REMEMBER YOU ═══════════ -->
    <section class="remember">
      <div class="remember__pulse-line"></div>
      <div class="remember__grid">
        <div class="remember__left animate-on-scroll">
          <h2 class="remember__heading">You're not just a customer.<br><span class="remember__heading-gradient">You're family.</span></h2>
          <p class="remember__text">Every order you place, every meal you customize, Tavliq remembers. Your preferences, your favorites, your dietary needs. Walk in and it's like they already know what you want.</p>
          <div class="remember__features stagger-children">
            <div class="remember__feature remember__feature--violet animate-on-scroll"><span class="remember__feature-icon">🧠</span><div><span class="remember__feature-title">Smart Memory</span><span class="remember__feature-desc">We remember your orders, allergies, and favorites across every visit.</span></div></div>
            <div class="remember__feature remember__feature--amber animate-on-scroll"><span class="remember__feature-icon">🔄</span><div><span class="remember__feature-title">One-Tap Reorder</span><span class="remember__feature-desc">Your last meal is one tap away. No scrolling, no searching.</span></div></div>
            <div class="remember__feature remember__feature--cyan animate-on-scroll"><span class="remember__feature-icon">🎯</span><div><span class="remember__feature-title">Personal Picks</span><span class="remember__feature-desc">Restaurants curate suggestions based on what you love.</span></div></div>
          </div>
        </div>
        <div class="remember__right animate-on-scroll">
          <div class="profile-card">
            <div class="profile-card__accent-line"></div>
            <div class="profile-card__avatar">S</div>
            <div class="profile-card__name">Sarah's Profile</div>
            <div class="profile-card__tier">⭐ Gold Member</div>
            <div class="profile-card__stats">
              <div class="profile-card__stat"><span class="profile-card__stat-value">47</span><span class="profile-card__stat-label">Orders</span></div>
              <div class="profile-card__stat"><span class="profile-card__stat-value">1,240</span><span class="profile-card__stat-label">Points</span></div>
              <div class="profile-card__stat"><span class="profile-card__stat-value">12</span><span class="profile-card__stat-label">Favorites</span></div>
            </div>
            <div class="profile-card__section"><span class="profile-card__section-label">Your Favorites</span><div class="profile-card__favorites"><span class="profile-card__fav">🍝 Truffle Pasta</span><span class="profile-card__fav">🥗 Caesar Salad</span><span class="profile-card__fav">☕ Oat Latte</span></div></div>
            <div class="profile-card__section"><span class="profile-card__section-label">Dietary Preferences</span><div class="profile-card__prefs"><span class="profile-card__pref">No onions</span><span class="profile-card__pref">Extra protein</span><span class="profile-card__pref">Gluten-free</span></div></div>
          </div>
        </div>
      </div>
    </section>

    <div class="section-fade" style="height:80px;background:linear-gradient(180deg,#09090B 0%,#111113 100%);margin:-1px 0"></div>

    <!-- ═══════════ SAAS FEATURES ═══════════ -->
    <section class="saas-features">
      <div class="saas-features__orb"></div>
      <div class="section-inner">
        <span class="section-label section-label--cyan">FOR RESTAURANTS</span>
        <h2 class="section-title">Where restaurants run smarter</h2>
        <p class="section-subtitle">One platform for your POS, kitchen, inventory, staff and analytics</p>
        <div class="features-grid stagger-children">
          <div v-for="(f, i) in saasFeatures" :key="f.title" :class="['feature-card', 'feature-card--' + f.color, 'animate-on-scroll']">
            <div class="feature-card__icon-wrap"><span>{{ f.icon }}</span></div>
            <h3 class="feature-card__title">{{ f.title }}</h3>
            <p class="feature-card__desc">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <div class="section-fade" style="height:80px;background:linear-gradient(180deg,#111113 0%,#111113 100%);margin:-1px 0"></div>

    <!-- ═══════════ HOW IT WORKS ═══════════ -->
    <section class="how-it-works">
      <div class="section-inner">
        <span class="section-label section-label--cyan">HOW IT WORKS</span>
        <h2 class="section-title">Up and running in minutes</h2>
        <div class="steps stagger-children">
          <div v-for="(s, i) in steps" :key="i" class="step animate-on-scroll">
            <div :class="['step__number', 'step__number--' + i]">{{ i + 1 }}</div>
            <h3 class="step__title">{{ s.title }}</h3>
            <p class="step__desc">{{ s.desc }}</p>
          </div>
          <div class="steps__line"><div class="steps__line-glow"></div></div>
        </div>
      </div>
    </section>

    <div class="section-fade" style="height:80px;background:linear-gradient(180deg,#111113 0%,#09090B 100%);margin:-1px 0"></div>

    <!-- ═══════════ FINAL CTA ═══════════ -->
    <section class="final-cta">
      <div class="final-cta__orb final-cta__orb--1"></div>
      <div class="final-cta__orb final-cta__orb--2"></div>
      <div class="final-cta__content animate-on-scroll">
        <h2 class="final-cta__title">Ready to run smarter?</h2>
        <p class="final-cta__subtitle">Join Tavliq today and transform how your restaurant operates.</p>
        <div class="final-cta__buttons">
          <RouterLink to="/register" class="final-cta__btn final-cta__btn--primary">Register Your Restaurant</RouterLink>
          <RouterLink to="/discover" class="final-cta__btn final-cta__btn--ghost">Explore as Customer</RouterLink>
        </div>
      </div>
    </section>

    <!-- ═══════════ FOOTER ═══════════ -->
    <footer class="footer">
      <div class="footer__inner">
        <div class="footer__top">
          <div class="footer__brand-block"><span class="footer__brand">🍽 <span class="footer__glow">Tavliq</span></span><span class="footer__tagline">Where restaurants run smarter</span></div>
          <div class="footer__links"><RouterLink to="/staff/login" class="footer__link">Staff Login</RouterLink><RouterLink to="/admin/login" class="footer__link">Admin</RouterLink></div>
        </div>
        <div class="footer__bottom">© 2026 Tavliq. Built for modern restaurants.</div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { RouterLink } from "vue-router";

const particles = [
  { emoji: '☕', x: 8, delay: 0, duration: 12 },
  { emoji: '🍕', x: 15, delay: 2, duration: 15 },
  { emoji: '🥗', x: 72, delay: 1, duration: 13 },
  { emoji: '🍜', x: 85, delay: 3, duration: 11 },
  { emoji: '🍰', x: 25, delay: 4, duration: 14 },
  { emoji: '🥩', x: 55, delay: 1.5, duration: 16 },
  { emoji: '🍋', x: 40, delay: 5, duration: 12 },
  { emoji: '🧁', x: 92, delay: 2.5, duration: 15 },
  { emoji: '🫐', x: 62, delay: 0.5, duration: 13 },
  { emoji: '🥑', x: 33, delay: 3.5, duration: 14 },
];

const saasFeatures = [
  { icon: "⚡", title: "Lightning POS", desc: "Process orders in seconds with a cashier-friendly interface built for speed.", color: "cyan" },
  { icon: "📊", title: "Live Analytics", desc: "Real-time revenue, peak hours, and performance dashboards.", color: "violet" },
  { icon: "🍳", title: "Kitchen Display", desc: "Orders route automatically to kitchen and bar stations.", color: "amber" },
  { icon: "📦", title: "Smart Inventory", desc: "Ingredients auto-deduct when orders are placed. Never run out.", color: "green" },
  { icon: "👥", title: "Staff Management", desc: "Role-based access. Every employee sees exactly what they need.", color: "cyan" },
  { icon: "🔌", title: "IoT Ready", desc: "ESP32 sensor integration for temperature and stock monitoring.", color: "violet" },
];

const steps = [
  { title: "Register", desc: "Submit your restaurant details for approval." },
  { title: "Set Up", desc: "Add your menu, ingredients, and invite your team." },
  { title: "Go Live", desc: "Start taking orders with POS, KDS, and analytics." },
];

let scrollObserver = null;

const observeElements = () => {
  scrollObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        scrollObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.05 });

  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    scrollObserver.observe(el);
  });
};

onMounted(() => { observeElements(); });
onUnmounted(() => { if (scrollObserver) scrollObserver.disconnect(); });
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════
   FORCE DARK — overrides theme for this page only
   ═══════════════════════════════════════════════════════ */
.landing-page {
  --color-bg: #09090B;
  --color-bg-elevated: #111113;
  --color-bg-overlay: #18181B;
  --color-bg-subtle: #1C1C1F;
  --color-border: #27272A;
  --color-text-primary: #FAFAFA;
  --color-text-secondary: #A1A1AA;
  --color-text-muted: #71717A;
  min-height: 100vh;
  background: var(--color-bg);
  scroll-behavior: smooth;
}

/* ═══════════════════════════════════════════════════════
   SCROLL ANIMATIONS
   ═══════════════════════════════════════════════════════ */
.animate-on-scroll {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.animate-on-scroll.is-visible {
  opacity: 1;
  transform: translateY(0);
}
.stagger-children .animate-on-scroll:nth-child(1) { transition-delay: 0ms; }
.stagger-children .animate-on-scroll:nth-child(2) { transition-delay: 100ms; }
.stagger-children .animate-on-scroll:nth-child(3) { transition-delay: 200ms; }
.stagger-children .animate-on-scroll:nth-child(4) { transition-delay: 300ms; }
.stagger-children .animate-on-scroll:nth-child(5) { transition-delay: 400ms; }
.stagger-children .animate-on-scroll:nth-child(6) { transition-delay: 500ms; }

/* ═══════════════════════════════════════════════════════
   NAVBAR
   ═══════════════════════════════════════════════════════ */
.navbar {
  position: fixed; top: 0; left: 0; right: 0;
  z-index: 200; height: 64px;
  background: rgba(9,9,11,0.85);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.navbar__inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 2rem; }
.navbar__brand { font-size: 1.25rem; font-weight: 800; color: #FAFAFA; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; }
.navbar__glow { text-shadow: 0 0 20px rgba(249,194,46,0.4); }
.navbar__nav { display: flex; align-items: center; gap: 0.75rem; }
.navbar__link { color: var(--color-cool); }
.navbar__link:hover { color: var(--color-cool-light); }

/* ═══════════════════════════════════════════════════════
   HERO
   ═══════════════════════════════════════════════════════ */
.hero {
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding: 0 4rem;
  padding-top: 64px; /* navbar height */
  gap: 2rem;
  overflow: hidden;
  background: #09090B;
}
.hero__grid { position: relative; z-index: 1; display: flex; align-items: center; max-width: 1200px; margin: 0 auto; width: 100%; }
.hero__left { flex: 1; max-width: 50%; display: flex; flex-direction: column; position: relative; z-index: 1; }
.hero__right { flex: 1; display: flex; align-items: center; justify-content: center; min-width: 50%; position: relative; z-index: 1; }

/* Pill */
.hero__pill {
  display: inline-block; width: fit-content; padding: 0.3rem 1rem;
  border: 1px solid rgba(249,115,22,0.3); border-radius: 9999px;
  background: rgba(249,115,22,0.08); color: var(--color-warm);
  font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em;
  animation: fadeInUp 0.5s ease 0.1s both;
}
.hero__pill::before {
  content: ''; display: inline-block; width: 6px; height: 6px;
  background: var(--color-warm); border-radius: 50%; margin-right: 8px;
  animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.6); } }

/* Title */
.hero__title { font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 800; line-height: 1.15; margin: 0.5rem 0 0; }
.hero-line { display: block; color: #FAFAFA; }
.hero-line--1 { animation: slideInLeft 0.6s ease 0.1s both; }
.hero-line--2 { animation: slideInLeft 0.6s ease 0.3s both; }
.hero-line--3 { animation: slideInLeft 0.6s ease 0.5s both; }
.hero-line--gradient { color: var(--color-warm); }
@keyframes slideInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }

/* Subtitle */
.hero__subtitle { font-size: 1rem; color: #A1A1AA; line-height: 1.6; max-width: 480px; margin: 1rem 0 0; animation: fadeInUp 0.5s ease 0.5s both; }

/* Benefits */
.hero__benefits { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.5rem; }
.hero__benefit {
  display: inline-flex; align-items: center; padding: 0.4rem 1rem;
  border: 1px solid rgba(255,255,255,0.08); border-radius: 9999px;
  background: rgba(9,9,11,0.8); backdrop-filter: blur(10px);
  font-size: 0.875rem; color: #A1A1AA; transition: all 200ms ease;
  animation: fadeInUp 0.5s ease both;
}
.hero__benefit:hover { border-color: rgba(249,194,46,0.4); color: #FBD462; transform: translateY(-2px); }
.stagger-children .hero__benefit:nth-child(1) { animation-delay: 0.6s; }
.stagger-children .hero__benefit:nth-child(2) { animation-delay: 0.7s; }
.stagger-children .hero__benefit:nth-child(3) { animation-delay: 0.8s; }

/* CTA */
.hero__cta { display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap; }
.hero__btn {
  display: inline-flex; align-items: center; padding: 0.9rem 2.2rem;
  border-radius: 9999px; font-size: 1rem; font-weight: 700;
  text-decoration: none; transition: all 250ms ease;
  animation: fadeInUp 0.5s ease both;
}
.hero__btn--amber {
  background: var(--color-warm); color: #09090B; font-weight: 800;
  box-shadow: 0 0 30px rgba(249,115,22,0.35);
  animation-delay: 0.9s;
}
.hero__btn--amber:hover { box-shadow: 0 0 50px rgba(249,115,22,0.5); transform: scale(1.02); }
.hero__btn--ghost {
  background: transparent; border: 1px solid rgba(255,255,255,0.12); color: #A1A1AA;
  animation-delay: 1s;
}
.hero__btn--ghost:hover { border-color: rgba(255,255,255,0.25); color: #FAFAFA; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

/* Particles */
.particles-container { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
.particle { position: absolute; bottom: -50px; font-size: 1.5rem; opacity: 0; animation: rise linear infinite; filter: blur(0.5px); }
@keyframes rise { 0% { transform: translateY(0) rotate(0deg) scale(0.8); opacity: 0; } 10% { opacity: 0.08; } 90% { opacity: 0.06; } 100% { transform: translateY(-100vh) rotate(360deg) scale(1.2); opacity: 0; } }

/* Meal Card — with glow ring */
.card-glow-wrapper { position: relative; width: 100%; max-width: 420px; }
.card-glow-wrapper::before {
  content: ''; position: absolute; inset: -2px;
  border-radius: calc(1.5rem + 2px);
  background: transparent;
  z-index: -1;
}
@keyframes rotate-glow { 0% { filter: hue-rotate(0deg); } 100% { filter: hue-rotate(360deg); } }

.meal-card {
  width: 100%;
  background: #111113;
  border: 1px solid rgba(249,115,22,0.25);
  border-radius: 1.5rem;
  padding: 1.5rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.8),
              0 0 60px rgba(249,115,22,0.12);
  transform: rotate(-2deg);
  transition: transform 400ms ease, box-shadow 400ms ease;
}
.meal-card:hover {
  transform: rotate(0deg) scale(1.02);
  box-shadow: 0 20px 60px rgba(0,0,0,0.8),
              0 0 80px rgba(249,115,22,0.22);
}
.meal-card__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.meal-card__title { font-size: 1rem; font-weight: 700; color: #FAFAFA; }
.meal-card__badge { padding: 2px 10px; border-radius: 9999px; background: var(--color-warm-subtle); color: var(--color-warm); font-size: 0.65rem; font-weight: 700; border: 1px solid rgba(249,115,22,0.3); }
.meal-card__dish { margin-bottom: 1rem; }
.meal-card__dish-name { margin: 0; font-size: 1.125rem; font-weight: 700; color: #FAFAFA; }
.meal-card__dish-style { font-size: 0.75rem; color: #71717A; }
.meal-card__section { margin-top: 1rem; }
.meal-card__label { display: block; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #71717A; margin-bottom: 0.5rem; }
.meal-card__ingredients { display: flex; flex-direction: column; }
.meal-card__ingredient { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.meal-card__ingredient:last-child { border-bottom: none; }
.meal-card__ingredient-left { display: flex; align-items: center; gap: 0.5rem; }
.meal-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.meal-dot--green { background: #10B981; }
.meal-dot--amber { background: #F9C22E; }
.meal-dot--red { background: #F43F5E; }
.meal-card__ingredient-name { font-size: 0.875rem; color: #A1A1AA; }
.meal-tag { padding: 2px 8px; border-radius: 9999px; font-size: 0.6rem; font-weight: 600; }
.meal-tag--green { background: rgba(45,106,79,0.15); color: #52B788; border: 1px solid rgba(45,106,79,0.3); }
.meal-tag--red { background: rgba(244,63,94,0.10); color: #F43F5E; border: 1px solid rgba(244,63,94,0.25); }
.meal-card__calorie-bar { height: 10px; background: rgba(255,255,255,0.06); border-radius: 9999px; overflow: hidden; margin: 0.75rem 0; }
.meal-card__calorie-fill { height: 100%; width: 0%; background: linear-gradient(90deg, #2D6A4F, #52B788, #74C69D); border-radius: 9999px; animation: fill-bar 1.5s ease 1.2s forwards; }
@keyframes fill-bar { to { width: 65%; } }
.meal-card__calorie-info { display: flex; justify-content: space-between; }
.meal-card__calorie-value { font-size: 0.875rem; font-weight: 700; color: var(--color-warm); }
.meal-card__calorie-goal { font-size: 0.75rem; color: #71717A; }
.meal-card__bottom { display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); }
.meal-card__loyalty { display: flex; flex-direction: column; }
.meal-card__loyalty-text { font-size: 0.75rem; font-style: italic; color: #A1A1AA; }
.meal-card__stars { font-size: 0.75rem; letter-spacing: 1px; }
.star { display: inline-block; animation: star-shimmer 2s ease-in-out infinite; }
@keyframes star-shimmer { 0%,100% { opacity: 1; } 50% { opacity: 0.6; } }
.meal-card__order-btn { padding: 0.5rem 1.2rem; border-radius: 9999px; background: var(--color-warm); color: #09090B; font-size: 0.75rem; font-weight: 700; text-decoration: none; box-shadow: 0 0 15px rgba(249,115,22,0.3); transition: all 200ms ease; }
.meal-card__order-btn:hover { box-shadow: 0 0 25px rgba(249,115,22,0.5); }

/* ═══════════════════════════════════════════════════════
   WE REMEMBER YOU
   ═══════════════════════════════════════════════════════ */
.remember { position: relative; background: #09090B; padding: 5rem 4rem; }
.remember__pulse-line { position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: rgba(249,115,22,0.4); }
@keyframes line-pulse { 0%,100% { opacity: 0.4; } 50% { opacity: 1; } }
.remember__grid { display: grid; grid-template-columns: 50% 50%; gap: 4rem; align-items: center; max-width: 1200px; margin: 0 auto; }
.remember__heading { font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 800; line-height: 1.2; color: #FAFAFA; margin: 0; }
.remember__heading-gradient { color: var(--color-warm); }
.remember__text { font-size: 1.125rem; color: #A1A1AA; line-height: 1.8; margin: 1.25rem 0 0; max-width: 480px; }
.remember__features { display: flex; flex-direction: column; gap: 0; margin-top: 2rem; }
.remember__feature { display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem; background: #111113; border-left: 3px solid; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 200ms ease; }
.remember__feature:hover { background: #18181B; transform: translateX(4px); }
.remember__feature--violet { border-left-color: rgba(249,115,22,0.4); }
.remember__feature--violet:hover { box-shadow: -4px 0 15px rgba(249,115,22,0.2); }
.remember__feature--amber { border-left-color: rgba(249,115,22,0.4); }
.remember__feature--amber:hover { box-shadow: -4px 0 15px rgba(249,115,22,0.2); }
.remember__feature--cyan { border-left-color: rgba(6,182,212,0.4); }
.remember__feature--cyan:hover { box-shadow: -4px 0 15px rgba(6,182,212,0.2); }
.remember__feature-icon { font-size: 1.5rem; margin-top: 2px; flex-shrink: 0; }
.remember__feature-title { display: block; font-weight: 700; color: #FAFAFA; font-size: 1rem; }
.remember__feature-desc { display: block; font-size: 0.875rem; color: #A1A1AA; margin-top: 2px; }
.remember__right { display: flex; justify-content: center; }
.profile-card {
  position: relative; width: 100%; max-width: 380px;
  background: #111113; border: 1px solid rgba(255,255,255,0.08);
  border-radius: 1.5rem; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.6);
  overflow: hidden;
}
.profile-card__accent-line { position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--color-warm); border-radius: 1.5rem 1.5rem 0 0; }
.profile-card__avatar { width: 64px; height: 64px; border-radius: 50%; background: var(--color-warm); color: #09090B; font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
.profile-card__name { font-size: 1.125rem; font-weight: 700; color: #FAFAFA; }
.profile-card__tier { font-size: 0.875rem; color: var(--color-warm); margin-bottom: 1.25rem; }
.profile-card__stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1.25rem; }
.profile-card__stat { text-align: center; padding: 0.75rem; background: rgba(0,0,0,0.3); border-radius: 1rem; }
.profile-card__stat-value { display: block; font-size: 1.125rem; font-weight: 800; color: #FAFAFA; }
.profile-card__stat-label { font-size: 0.75rem; color: #71717A; }
.profile-card__section { margin-bottom: 1rem; }
.profile-card__section-label { display: block; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #71717A; margin-bottom: 0.5rem; }
.profile-card__favorites, .profile-card__prefs { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.profile-card__fav, .profile-card__pref { padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.08); }
.profile-card__fav { background: rgba(255,255,255,0.04); color: #A1A1AA; }
.profile-card__pref { background: rgba(249,115,22,0.08); border-color: rgba(249,115,22,0.2); color: var(--color-warm); }

/* ═══════════════════════════════════════════════════════
   SAAS FEATURES
   ═══════════════════════════════════════════════════════ */
.saas-features { background: #111113; padding: 5rem 2rem; position: relative; overflow: hidden; }
.saas-features__orb { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 600px; height: 600px; background: radial-gradient(circle, rgba(249,115,22,0.06), transparent 70%); pointer-events: none; z-index: 0; }
.section-inner { max-width: 1200px; margin: 0 auto; position: relative; z-index: 1; }
.section-label { display: block; font-size: 0.875rem; font-weight: 700; letter-spacing: 0.1em; margin-bottom: 0.75rem; }
.section-label--cyan { color: #06B6D4; }
.section-title { font-size: 2.25rem; font-weight: 800; color: #FAFAFA; margin: 0 0 3rem; line-height: 1.2; }
.section-subtitle { font-size: 1.125rem; color: #A1A1AA; margin: 0 0 3rem; }
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.feature-card {
  padding: 1.5rem; background: #111113; border: 1px solid rgba(255,255,255,0.06);
  border-radius: 1.25rem; transition: all 200ms ease;
}
.feature-card:hover { transform: translateY(-4px); }
.feature-card--cyan:hover { border-color: rgba(6,182,212,0.4); box-shadow: 0 0 30px rgba(6,182,212,0.15); }
.feature-card--violet:hover { border-color: rgba(249,115,22,0.4); box-shadow: 0 0 30px rgba(249,115,22,0.15); }
.feature-card--amber:hover { border-color: rgba(249,115,22,0.4); box-shadow: 0 0 30px rgba(249,115,22,0.15); }
.feature-card--green:hover { border-color: rgba(249,115,22,0.4); box-shadow: 0 0 30px rgba(249,115,22,0.15); }
.feature-card__icon-wrap { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1rem; background: var(--color-warm-subtle); color: var(--color-warm); }
.feature-card__title { margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 700; color: #FAFAFA; }
.feature-card__desc { margin: 0; font-size: 0.875rem; color: #A1A1AA; line-height: 1.6; }

/* ═══════════════════════════════════════════════════════
   HOW IT WORKS
   ═══════════════════════════════════════════════════════ */
.how-it-works { background: #111113; padding: 5rem 2rem; }
.steps { display: flex; justify-content: center; gap: 4rem; position: relative; z-index: 2; }
.steps__line { position: absolute; top: 28px; left: calc(50% - 180px); width: 360px; height: 2px; background: var(--color-warm); background-size: 200% 100%; z-index: 0; }
.steps__line-glow { position: absolute; inset: -4px 0; background: inherit; filter: blur(6px); opacity: 0.5; border-radius: 9999px; }
@keyframes shimmer-line { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.step { text-align: center; position: relative; z-index: 1; flex: 1; max-width: 240px; }
.step__number { width: 56px; height: 56px; border-radius: 50%; font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.step__number--0 { background: #1C1C1F; border: 1px solid #27272A; color: white; }
.step__number--1 { background: #1C1C1F; border: 1px solid #27272A; color: white; }
.step__number--2 { background: var(--color-warm); color: #09090B; box-shadow: 0 0 20px rgba(249,115,22,0.4); }
.step__title { margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 700; color: #FAFAFA; }
.step__desc { margin: 0; font-size: 0.875rem; color: #A1A1AA; line-height: 1.5; }

/* ═══════════════════════════════════════════════════════
   FINAL CTA
   ═══════════════════════════════════════════════════════ */
.final-cta { background: #09090B; padding: 5rem 2rem; position: relative; overflow: hidden; }
.final-cta__orb { position: absolute; border-radius: 50%; pointer-events: none; }
.final-cta__orb--1 { width: 500px; height: 500px; top: -100px; left: -100px; background: radial-gradient(circle, rgba(249,115,22,0.06), transparent 70%); filter: blur(120px); }
.final-cta__orb--2 { width: 400px; height: 400px; bottom: -100px; right: -50px; background: radial-gradient(circle, rgba(255,255,255,0.03), transparent 70%); filter: blur(100px); }
.final-cta__content { max-width: 700px; margin: 0 auto; text-align: center; position: relative; z-index: 1; }
.final-cta__title { font-size: 2.25rem; font-weight: 800; color: #FAFAFA; margin: 0 0 1rem; }
.final-cta__subtitle { font-size: 1.125rem; color: #A1A1AA; margin: 0 0 2rem; }
.final-cta__buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.final-cta__btn { display: inline-flex; align-items: center; padding: 14px 32px; border-radius: 0.625rem; font-size: 1rem; font-weight: 700; text-decoration: none; transition: all 200ms ease; }
.final-cta__btn--primary { background: white; color: #09090B; font-weight: 700; }
.final-cta__btn--primary:hover { background: var(--color-warm); color: #09090B; }
.final-cta__btn--ghost { border: 1px solid rgba(255,255,255,0.15); color: #A1A1AA; background: transparent; }
.final-cta__btn--ghost:hover { border-color: rgba(255,255,255,0.3); color: #FAFAFA; }

/* ═══════════════════════════════════════════════════════
   FOOTER
   ═══════════════════════════════════════════════════════ */
.footer { background: #09090B; border-top: 1px solid rgba(255,255,255,0.06); padding: 2rem; }
.footer__inner { max-width: 1200px; margin: 0 auto; }
.footer__top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.footer__brand-block { display: flex; flex-direction: column; gap: 0.25rem; }
.footer__brand { font-size: 1.25rem; font-weight: 800; color: #FAFAFA; }
.footer__glow { color: #FAFAFA; }
.footer__tagline { font-size: 0.875rem; color: #71717A; }
.footer__links { display: flex; gap: 1rem; }
.footer__link { font-size: 0.875rem; color: #52525B; text-decoration: none; transition: color 150ms ease; }
.footer__link:hover { color: var(--color-warm); }
.footer__bottom { text-align: center; font-size: 0.75rem; color: #71717A; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); }

/* ═══════════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .hero__grid { grid-template-columns: 1fr; gap: 2rem; }
  .hero__right { order: -1; }
  .meal-card { max-width: 360px; transform: rotate(0); }
  .remember__grid { grid-template-columns: 1fr; gap: 2rem; }
  .features-grid { grid-template-columns: repeat(2, 1fr); }
  .steps__line { display: none; }
}
@media (max-width: 768px) {
  .navbar__inner { padding: 0 1rem; }
  .hero { padding: 80px 1.5rem 2rem; min-height: auto; }
  .hero__title { font-size: clamp(2rem, 8vw, 3rem); }
  .remember { padding: 3rem 1.5rem; }
  .remember__pulse-line { display: none; }
  .section-title { font-size: 1.875rem; }
  .steps { flex-direction: column; align-items: center; gap: 2rem; }
  .final-cta__title { font-size: 1.875rem; }
  .footer__top { flex-direction: column; gap: 1rem; }
}
@media (max-width: 640px) {
  .features-grid { grid-template-columns: 1fr; }
  .hero__cta { flex-direction: column; align-items: flex-start; }
  .final-cta__buttons { flex-direction: column; align-items: center; }
}
</style>

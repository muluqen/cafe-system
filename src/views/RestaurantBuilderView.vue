<template>
  <section class="dashboard-stack">
    <section class="panel builder-wizard-shell">
      <div class="content-pad builder-wizard-top">
        <div>
          <span class="eyebrow">Owner Setup</span>
          <h1 class="panel-title">{{ currentStep.title }}</h1>
          <p class="muted">
            We’ll build your restaurant space one part at a time. Save each step, preview it, then move to the next.
          </p>
        </div>

        <div class="builder-stepper">
          <button
            v-for="(step, index) in steps"
            :key="step.key"
            class="builder-step-pill"
            :class="{ active: index === currentStepIndex, done: index < currentStepIndex }"
            type="button"
            @click="jumpToStep(index)"
          >
            <span>{{ index + 1 }}</span>
            <strong>{{ step.label }}</strong>
          </button>
        </div>

        <div v-if="message" class="status-banner status-banner-success">
          <span class="status-banner-dot" />
          <div>
            <strong>Done</strong>
            <p>{{ message }}</p>
          </div>
        </div>
      </div>
    </section>

    <BrandStep
      v-if="currentStep.key === 'brand'"
      :form="brandForm"
      :new-color="newColor"
      :saving="saving"
      :error="error"
      :message="message"
      @update-field="updateBrandField"
      @update-new-color="newColor = $event"
      @add-color="addBrandColor"
      @remove-color="removeBrandColor"
      @set-primary="setPrimaryColor"
      @save="saveBrandAndPreview"
    />

    <PreviewStep
      v-else-if="currentStep.key === 'preview'"
      :form="brandForm"
      :menu-items="menuItems"
      :tables="tables"
      @back="goToPreviousStep"
      @next="goToNextStep"
    />

    <MenuStep
      v-else-if="currentStep.key === 'menu'"
      :menu-form="menuForm"
      :menu-items="menuItems"
      :saving="saving"
      :error="error"
      @update-menu-form="updateMenuField"
      @save-menu-item="saveMenuItem"
      @remove-menu-item="removeMenuItem"
      @back="goToPreviousStep"
      @next="goToNextStep"
    />

    <TeamStep
      v-else-if="currentStep.key === 'team'"
      :staff-form="staffForm"
      :staff-members="staffMembers"
      :owner-id="auth.user?.id"
      :saving="saving"
      :error="error"
      @update-staff-form="updateStaffField"
      @save-staff-member="saveStaffMember"
      @update-staff-role="updateStaffRole"
      @remove-staff-member="removeStaffMember"
      @back="goToPreviousStep"
      @next="goToNextStep"
    />

    <TablesStep
      v-else
      :table-form="tableForm"
      :tables="tables"
      :saving="saving"
      :error="error"
      @update-table-form="updateTableField"
      @save-table="saveTable"
      @remove-table="removeTable"
      @back="goToPreviousStep"
      @finish="finishSetup"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import BrandStep from "../components/restaurant-builder/BrandStep.vue";
import MenuStep from "../components/restaurant-builder/MenuStep.vue";
import PreviewStep from "../components/restaurant-builder/PreviewStep.vue";
import TablesStep from "../components/restaurant-builder/TablesStep.vue";
import TeamStep from "../components/restaurant-builder/TeamStep.vue";
import api from "../services/api";
import { useAuthStore } from "../stores/authStore";
import { restaurantBuilderSteps } from "../utils/restaurantBuilderSteps";
import { getRestaurantBranding, saveRestaurantBranding } from "../utils/restaurantBranding";

const auth = useAuthStore();
const router = useRouter();
const steps = restaurantBuilderSteps;
const currentStepIndex = ref(0);
const saving = ref(false);
const error = ref("");
const message = ref("");
const newColor = ref("#2ECC71");

const brandForm = reactive({
  id: null,
  slug: "",
  name: "",
  phone: "",
  email: "",
  address: "",
  isActive: false,
  headline: "",
  customerMessage: "",
  brandColor: "#1A2A40",
  brandColors: ["#1A2A40", "#FF6B35", "#2ECC71"],
  logoUrl: ""
});

const menuForm = reactive({
  categoryName: "",
  name: "",
  price: "",
  description: "",
  preparation_time_minutes: ""
});

const staffForm = reactive({
  name: "",
  email: "",
  password: "",
  staff_role: "server"
});

const tableForm = reactive({
  name: "",
  capacity: "2",
  location: ""
});

const menuCategories = ref([]);
const menuItems = ref([]);
const staffMembers = ref([]);
const tables = ref([]);

const currentStep = computed(() => steps[currentStepIndex.value]);

function updateBrandField(field, value) {
  brandForm[field] = value;
}

function updateMenuField(field, value) {
  menuForm[field] = value;
}

function updateStaffField(field, value) {
  staffForm[field] = value;
}

function updateTableField(field, value) {
  tableForm[field] = value;
}

function jumpToStep(index) {
  if (index > currentStepIndex.value + 1) {
    return;
  }
  currentStepIndex.value = index;
  error.value = "";
  message.value = "";
}

function goToNextStep() {
  currentStepIndex.value = Math.min(steps.length - 1, currentStepIndex.value + 1);
  error.value = "";
  message.value = "";
}

function goToPreviousStep() {
  currentStepIndex.value = Math.max(0, currentStepIndex.value - 1);
  error.value = "";
  message.value = "";
}

function setPrimaryColor(color) {
  brandForm.brandColor = color;
  brandForm.brandColors = [color, ...brandForm.brandColors.filter((entry) => entry !== color)];
}

function addBrandColor() {
  if (brandForm.brandColors.length >= 3 || brandForm.brandColors.includes(newColor.value)) {
    return;
  }
  brandForm.brandColors = [...brandForm.brandColors, newColor.value];
}

function removeBrandColor(color) {
  if (brandForm.brandColors.length <= 1) {
    return;
  }
  brandForm.brandColors = brandForm.brandColors.filter((entry) => entry !== color);
  if (brandForm.brandColor === color) {
    brandForm.brandColor = brandForm.brandColors[0];
  }
}

async function loadRestaurant() {
  if (!auth.user?.restaurant_id) {
    return;
  }

  const { data } = await api.get(`/restaurants/${auth.user.restaurant_id}`);
  const branding = getRestaurantBranding(auth.user.restaurant_id);
  brandForm.id = data.id;
  brandForm.slug = data.slug || "";
  brandForm.name = data.name || "";
  brandForm.phone = data.phone || "";
  brandForm.email = data.email || "";
  brandForm.address = data.address || "";
  brandForm.isActive = Boolean(data.is_active);
  brandForm.headline = branding.headline || "";
  brandForm.customerMessage = branding.customerMessage || "";
  brandForm.brandColor = branding.brandColor || "#1A2A40";
  brandForm.brandColors = branding.brandColors?.length ? branding.brandColors : ["#1A2A40", "#FF6B35", "#2ECC71"];
  brandForm.logoUrl = branding.logoUrl || "";
  newColor.value = brandForm.brandColors[brandForm.brandColors.length - 1] || "#2ECC71";
}

async function loadSetupLists() {
  const [categoriesRes, itemsRes, usersRes, tablesRes] = await Promise.all([
    api.get("/menu_categories", { params: { per_page: 200 } }),
    api.get("/menu_items", { params: { per_page: 200 } }),
    api.get("/users", { params: { per_page: 200 } }),
    api.get("/tables", { params: { per_page: 200 } })
  ]);

  menuCategories.value = categoriesRes.data?.data || [];
  menuItems.value = itemsRes.data?.data || [];
  staffMembers.value = usersRes.data?.data || [];
  tables.value = tablesRes.data?.data || [];
}

async function persistBrand(activate = brandForm.isActive) {
  const restaurantId = brandForm.id || auth.user?.restaurant_id;

  if (!restaurantId) {
    throw new Error("Restaurant profile is not ready yet. Please refresh and try again.");
  }

  await api.put(`/restaurants/${restaurantId}`, {
    name: brandForm.name,
    slug: brandForm.slug || null,
    phone: brandForm.phone || null,
    email: brandForm.email || null,
    address: brandForm.address || null,
    is_active: activate
  });

  brandForm.id = restaurantId;
  brandForm.isActive = activate;

  saveRestaurantBranding(auth.user.restaurant_id, {
    headline: brandForm.headline,
    customerMessage: brandForm.customerMessage,
    brandColor: brandForm.brandColor,
    brandColors: brandForm.brandColors,
    logoUrl: brandForm.logoUrl
  });
}

async function saveBrandAndPreview() {
  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    await persistBrand(false);
    message.value = "First page saved. Now check the preview.";
    currentStepIndex.value = 1;
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to save the first page.";
  } finally {
    saving.value = false;
  }
}

async function ensureCategory() {
  const categoryName = menuForm.categoryName.trim();
  if (!categoryName) {
    return null;
  }

  const existing = menuCategories.value.find(
    (category) => category.name.toLowerCase() === categoryName.toLowerCase()
  );

  if (existing) {
    return existing.id;
  }

  const { data } = await api.post("/menu_categories", {
    restaurant_id: auth.user.restaurant_id,
    name: categoryName,
    display_order: menuCategories.value.length,
    is_active: true
  });

  menuCategories.value = [...menuCategories.value, data];
  return data.id;
}

async function saveMenuItem() {
  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    const menuCategoryId = await ensureCategory();
    await api.post("/menu_items", {
      restaurant_id: auth.user.restaurant_id,
      menu_category_id: menuCategoryId,
      name: menuForm.name,
      price: Number(menuForm.price || 0),
      description: menuForm.description || null,
      is_available: true,
      preparation_time_minutes: menuForm.preparation_time_minutes ? Number(menuForm.preparation_time_minutes) : null
    });
    Object.assign(menuForm, {
      categoryName: "",
      name: "",
      price: "",
      description: "",
      preparation_time_minutes: ""
    });
    await loadSetupLists();
    message.value = "Menu item added.";
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to add the menu item.";
  } finally {
    saving.value = false;
  }
}

async function removeMenuItem(id) {
  message.value = "";
  await api.delete(`/menu_items/${id}`);
  await loadSetupLists();
  message.value = "Menu item removed successfully.";
}

async function saveStaffMember() {
  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    await api.post("/users", {
      name: staffForm.name,
      email: staffForm.email,
      password: staffForm.password,
      staff_role: staffForm.staff_role
    });
    Object.assign(staffForm, {
      name: "",
      email: "",
      password: "",
      staff_role: "server"
    });
    await loadSetupLists();
    message.value = "Staff member added.";
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to add the staff member.";
  } finally {
    saving.value = false;
  }
}

async function updateStaffRole(member, role) {
  if (member.id === auth.user?.id) {
    return;
  }

  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    await api.put(`/users/${member.id}`, {
      name: member.name,
      email: member.email,
      staff_role: role
    });
    await loadSetupLists();
    message.value = `${member.name}'s staff role was updated successfully.`;
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to update the staff role.";
  } finally {
    saving.value = false;
  }
}

async function removeStaffMember(id) {
  message.value = "";
  await api.delete(`/users/${id}`);
  await loadSetupLists();
  message.value = "Staff member removed successfully.";
}

async function saveTable() {
  saving.value = true;
  error.value = "";
  message.value = "";
  try {
    await api.post("/tables", {
      restaurant_id: auth.user.restaurant_id,
      name: tableForm.name,
      capacity: Number(tableForm.capacity || 2),
      location: tableForm.location || null,
      status: "available",
      is_active: true
    });
    Object.assign(tableForm, {
      name: "",
      capacity: "2",
      location: ""
    });
    await loadSetupLists();
    message.value = "Table added.";
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to add the table.";
  } finally {
    saving.value = false;
  }
}

async function removeTable(id) {
  message.value = "";
  await api.delete(`/tables/${id}`);
  await loadSetupLists();
  message.value = "Table removed successfully.";
}

async function finishSetup() {
  saving.value = true;
  error.value = "";

  try {
    await persistBrand(true);
    await auth.loadPublicRestaurants();
    message.value = "Setup finished. Your restaurant is now visible to customers.";
    await router.push({ name: "dashboard" });
  } catch (requestError) {
    error.value = requestError?.response?.data?.message || "Unable to finish the setup.";
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  const welcomeNotice = auth.consumeRestaurantWelcomeNotice();
  if (welcomeNotice) {
    message.value = welcomeNotice;
  }
  await loadRestaurant();
  await loadSetupLists();
});
</script>

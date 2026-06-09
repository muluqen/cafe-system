<template>
  <section class="panel">
    <header class="panel-header panel-header-rich">
      <div>
        <span class="eyebrow">Step 4</span>
        <h2 class="panel-title">Add staff and assign roles</h2>
        <p class="muted team-step-copy">
          Give each person a clear job-based role so they open the right tools the moment they sign in.
        </p>
      </div>
    </header>

    <div class="content-pad builder-stack">
      <section class="team-role-guide">
        <article v-for="role in roleOptions" :key="role.value" class="team-role-guide-card">
          <strong>{{ role.label }}</strong>
          <p class="muted">{{ role.description }}</p>
        </article>
      </section>

      <form class="builder-form" @submit.prevent="$emit('save-staff-member')">
        <div class="builder-split">
          <label>
            Full name
            <input :value="staffForm.name" class="input" type="text" placeholder="Staff member name" @input="$emit('update-staff-form', 'name', $event.target.value)" />
          </label>
          <label>
            Work email
            <input :value="staffForm.email" class="input" type="email" placeholder="staff@restaurant.com" @input="$emit('update-staff-form', 'email', $event.target.value)" />
          </label>
        </div>

        <div class="builder-split">
          <label>
            Temporary password
            <input :value="staffForm.password" class="input" type="text" placeholder="At least 8 characters" @input="$emit('update-staff-form', 'password', $event.target.value)" />
          </label>
          <label>
            Role
            <select :value="staffForm.staff_role" class="input" @change="$emit('update-staff-form', 'staff_role', $event.target.value)">
              <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </label>
        </div>

        <p class="muted team-role-selected">{{ selectedRoleMeta.description }}</p>

        <div class="builder-step-actions between">
          <button class="button button-soft" type="button" @click="$emit('back')">Back</button>
          <div class="builder-inline-actions">
            <button class="button button-soft" type="button" @click="$emit('next')">Skip for now</button>
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? "Saving..." : "Add staff member" }}
            </button>
          </div>
        </div>
      </form>

      <p v-if="error" class="error-text">{{ error }}</p>

      <div class="builder-record-list">
        <article v-for="member in staffMembers" :key="member.id" class="builder-record-card">
          <div>
            <strong>{{ member.name }}</strong>
            <p class="muted">{{ member.email }} | {{ memberRole(member).label }}</p>
            <p class="muted team-member-note">{{ memberRole(member).description }}</p>
          </div>
          <div class="builder-inline-actions">
            <select
              class="input compact-input"
              :value="member.staff_role || 'manager'"
              :disabled="member.id === ownerId"
              @change="$emit('update-staff-role', member, $event.target.value)"
            >
              <option value="manager">Owner</option>
              <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
            <button
              v-if="member.id !== ownerId"
              class="button button-soft danger"
              type="button"
              @click="$emit('remove-staff-member', member.id)"
            >
              Remove
            </button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { getStaffRoleMeta, staffRoleOptions } from "../../utils/staffRoles";

const roleOptions = staffRoleOptions;

const props = defineProps({
  staffForm: { type: Object, required: true },
  staffMembers: { type: Array, required: true },
  ownerId: { type: [Number, String], default: null },
  saving: { type: Boolean, default: false },
  error: { type: String, default: "" }
});

const selectedRoleMeta = computed(() => getStaffRoleMeta(props.staffForm.staff_role));

function memberRole(member) {
  return getStaffRoleMeta(member.staff_role || "manager");
}

defineEmits([
  "update-staff-form",
  "save-staff-member",
  "update-staff-role",
  "remove-staff-member",
  "back",
  "next"
]);
</script>

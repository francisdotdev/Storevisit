<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md">
      <!-- Card Header -->
      <div class="text-center mb-6">
        <div class="inline-flex w-9 h-9 rounded bg-neutral-900 items-center justify-center text-white font-bold text-xs tracking-wider mb-3 shadow-xs">
          LCC
        </div>
        <h1 class="text-lg font-semibold tracking-tight text-neutral-900">
          Account Registration
        </h1>
        <p class="mt-0.5 text-xs text-neutral-500">
          Register to access the LCC Store Visit Audit Portal
        </p>
      </div>

      <!-- Main Box -->
      <div class="bg-white rounded-lg border border-neutral-200 p-6 shadow-xs">
        <!-- Success State Banner -->
        <div v-if="successMessage" class="mb-4 p-4 rounded-md bg-neutral-50 border border-neutral-200 text-neutral-900 text-xs">
          <h3 class="font-semibold text-neutral-900">Registration Submitted</h3>
          <p class="mt-1 text-neutral-600 leading-relaxed">
            {{ successMessage }}
          </p>
          <div class="mt-3">
            <button
              type="button"
              @click="$emit('switch-view', 'login')"
              class="px-3.5 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-md text-xs font-medium transition-colors"
            >
              Return to Sign In
            </button>
          </div>
        </div>

        <form v-else @submit.prevent="handleSubmit" class="space-y-3.5 text-xs">
          <!-- Notice Banner -->
          <div class="p-2.5 rounded-md bg-neutral-50 border border-neutral-200 text-neutral-600 text-xs flex items-center space-x-2">
            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
            <span>Accounts start in <strong class="text-neutral-900">Pending</strong> status awaiting Admin approval.</span>
          </div>

          <!-- General Error Banner -->
          <div v-if="errorMessage" class="p-2.5 rounded-md bg-neutral-50 border border-neutral-200 text-rose-600 text-xs">
            {{ errorMessage }}
          </div>

          <!-- Employee ID -->
          <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
              Employee ID <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="form.employee_id"
              type="text"
              required
              placeholder="e.g. EMP00123"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="errors.employee_id" class="mt-1 text-[11px] text-rose-600">{{ errors.employee_id[0] }}</p>
          </div>

          <!-- Name Row -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
                First Name <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="form.first_name"
                type="text"
                required
                placeholder="Juan"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="errors.first_name" class="mt-1 text-[11px] text-rose-600">{{ errors.first_name[0] }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
                Last Name <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="form.last_name"
                type="text"
                required
                placeholder="Dela Cruz"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="errors.last_name" class="mt-1 text-[11px] text-rose-600">{{ errors.last_name[0] }}</p>
            </div>
          </div>

          <!-- Email & Branch Code -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
                Email Address <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="form.email"
                type="email"
                required
                placeholder="juan@lcc.com.ph"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="errors.email" class="mt-1 text-[11px] text-rose-600">{{ errors.email[0] }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
                Branch Code
              </label>
              <input
                v-model.trim="form.branch_code"
                type="text"
                placeholder="Optional"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
            </div>
          </div>

          <!-- Role Selection (From DB) -->
          <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
              Select Position <span class="text-neutral-400">*</span>
            </label>
            <select
              v-model="form.role_id"
              required
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
            >
              <option value="" disabled>Choose assigned position...</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">
                {{ r.display_name }}
              </option>
            </select>
            <p v-if="errors.role_id" class="mt-1 text-[11px] text-rose-600">{{ errors.role_id[0] }}</p>
          </div>

          <!-- Passwords -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
                Password <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                required
                placeholder="Min. 8 characters"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="errors.password" class="mt-1 text-[11px] text-rose-600">{{ errors.password[0] }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
                Confirm <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                placeholder="Re-type password"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full mt-2 py-2 px-3 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white font-medium text-xs transition-colors disabled:opacity-60 flex items-center justify-center space-x-1.5"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isSubmitting ? 'Submitting...' : 'Register Account' }}</span>
          </button>
        </form>
      </div>

      <!-- Footer navigation -->
      <div class="mt-4 text-center text-xs text-neutral-500">
        Already registered?
        <button
          type="button"
          @click="$emit('switch-view', 'login')"
          class="font-medium text-neutral-900 hover:underline ml-1"
        >
          Sign In
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { api } from '../services/api';
import { useAuth } from '../composables/useAuth';

const emit = defineEmits(['switch-view', 'registered']);

const { register } = useAuth();

const roles = ref([]);
const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const errors = ref({});

const form = reactive({
  employee_id: '',
  first_name: '',
  last_name: '',
  email: '',
  branch_code: '',
  role_id: '',
  password: '',
  password_confirmation: '',
});

async function loadRoles() {
  try {
    roles.value = await api.get('/api/auth/roles');
    if (roles.value.length > 0 && !form.role_id) {
      // Find a default supervisor role or first non-admin role
      const defaultRole = roles.value.find(r => r.name.includes('supervisor')) || roles.value[1] || roles.value[0];
      if (defaultRole) {
        form.role_id = defaultRole.id;
      }
    }
  } catch (err) {
    console.error('Failed to load roles', err);
  }
}

onMounted(() => {
  loadRoles();
});

async function handleSubmit() {
  isSubmitting.value = true;
  successMessage.value = '';
  errorMessage.value = '';
  errors.value = {};

  try {
    const res = await register(form);
    successMessage.value = res.message || 'Registration submitted successfully. Your account is pending Administrator approval before you can log in.';
    emit('registered', res.user);
  } catch (err) {
    if (err.status === 422) {
      errors.value = err.errors || {};
      errorMessage.value = 'Please correct the errors in the form below.';
    } else {
      errorMessage.value = err.message || 'Failed to submit registration. Please try again.';
    }
  } finally {
    isSubmitting.value = false;
  }
}
</script>


<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md">
      <!-- Card Header -->
      <div class="text-center mb-6">
        <div class="inline-flex w-10 h-10 rounded-xl bg-brand-600 items-center justify-center text-white font-bold text-sm tracking-wider mb-3 shadow-md shadow-brand-500/20">
          LCC
        </div>
        <h1 class="text-xl font-bold tracking-tight text-neutral-900">
          Account Registration
        </h1>
        <p class="mt-1 text-xs text-neutral-500">
          Register to access the LCC Store Visit Audit Portal
        </p>
      </div>

      <!-- Main Box -->
      <div class="bg-white rounded-xl border border-neutral-200/80 p-5 sm:p-6 shadow-xs">
        <!-- Success State Banner -->
        <div v-if="successMessage" class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-neutral-900 text-xs">
          <h3 class="font-semibold text-emerald-900 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Registration Submitted
          </h3>
          <p class="mt-1.5 text-emerald-800 leading-relaxed">
            {{ successMessage }}
          </p>
          <div class="mt-4">
            <button
              type="button"
              @click="$emit('switch-view', 'login')"
              class="min-h-[44px] px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs"
            >
              Return to Sign In
            </button>
          </div>
        </div>

        <form v-else @submit.prevent="handleSubmit" class="space-y-4 text-xs">
          <!-- Notice Banner -->
          <div class="p-3 rounded-lg bg-brand-50 border border-brand-200 text-brand-900 text-xs flex items-start gap-2">
            <span class="w-2 h-2 rounded-full bg-brand-600 mt-1 shrink-0"></span>
            <span class="leading-relaxed">Accounts start in <strong class="font-semibold">Pending</strong> status awaiting Administrator approval.</span>
          </div>

          <!-- General Error Banner -->
          <div v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
            {{ errorMessage }}
          </div>

          <!-- Employee ID -->
          <div>
            <label class="block text-[11px] font-medium text-neutral-700 mb-1">
              Employee ID <span class="text-rose-500">*</span>
            </label>
            <input
              v-model.trim="form.employee_id"
              type="text"
              required
              placeholder="e.g. EMP00123"
              class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
            />
            <p v-if="errors.employee_id" class="mt-1 text-[11px] text-rose-600 font-medium">{{ errors.employee_id[0] }}</p>
          </div>

          <!-- Name Row -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-neutral-700 mb-1">
                First Name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.trim="form.first_name"
                type="text"
                required
                placeholder="Juan"
                class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
              />
              <p v-if="errors.first_name" class="mt-1 text-[11px] text-rose-600 font-medium">{{ errors.first_name[0] }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-neutral-700 mb-1">
                Last Name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.trim="form.last_name"
                type="text"
                required
                placeholder="Dela Cruz"
                class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
              />
              <p v-if="errors.last_name" class="mt-1 text-[11px] text-rose-600 font-medium">{{ errors.last_name[0] }}</p>
            </div>
          </div>

          <!-- Email & Branch Code -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-neutral-700 mb-1">
                Email Address <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.trim="form.email"
                type="email"
                required
                placeholder="juan@lcc.com.ph"
                class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
              />
              <p v-if="errors.email" class="mt-1 text-[11px] text-rose-600 font-medium">{{ errors.email[0] }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-neutral-700 mb-1">
                Branch Code
              </label>
              <input
                v-model.trim="form.branch_code"
                type="text"
                placeholder="Optional"
                class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
              />
            </div>
          </div>

          <!-- Role Selection (From DB) -->
          <div>
            <label class="block text-[11px] font-medium text-neutral-700 mb-1">
              Select Position <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="form.role_id"
              required
              class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 bg-white text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
            >
              <option value="" disabled>Choose assigned position...</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">
                {{ r.display_name }}
              </option>
            </select>
            <p v-if="errors.role_id" class="mt-1 text-[11px] text-rose-600 font-medium">{{ errors.role_id[0] }}</p>
          </div>

          <!-- Passwords -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-neutral-700 mb-1">
                Password <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                required
                placeholder="Min. 8 characters"
                class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
              />
              <p v-if="errors.password" class="mt-1 text-[11px] text-rose-600 font-medium">{{ errors.password[0] }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-neutral-700 mb-1">
                Confirm Password <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                placeholder="Re-type password"
                class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"
              />
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full min-h-[44px] mt-3 py-2.5 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white font-semibold text-xs tracking-wide transition-colors shadow-xs disabled:opacity-60 flex items-center justify-center space-x-2"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isSubmitting ? 'Submitting Registration...' : 'Register Account' }}</span>
          </button>
        </form>
      </div>

      <!-- Footer navigation -->
      <div class="mt-4 text-center text-xs text-neutral-500">
        Already have an account?
        <button
          type="button"
          @click="$emit('switch-view', 'login')"
          class="font-semibold text-brand-600 hover:text-brand-700 hover:underline ml-1 p-2 inline-flex items-center min-h-[44px]"
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


<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-sm">
      <!-- Brand Card Header -->
      <div class="text-center mb-6">
        <div class="inline-flex w-9 h-9 rounded bg-neutral-900 items-center justify-center text-white font-bold text-xs tracking-wider mb-3 shadow-xs">
          LCC
        </div>
        <h1 class="text-lg font-semibold tracking-tight text-neutral-900">
          Sign In
        </h1>
        <p class="mt-0.5 text-xs text-neutral-500">
          Store Visit Audit &amp; Compliance Portal
        </p>
      </div>

      <!-- Main Login Box -->
      <div class="bg-white rounded-lg border border-neutral-200 p-6 shadow-xs">
        <!-- Status / Error Alerts -->
        <div v-if="alertMessage" :class="alertClasses" class="mb-4 p-3 rounded-md flex items-start space-x-2 text-xs border">
          <div class="flex-1">
            <span class="font-medium block mb-0.5">{{ alertTitle }}</span>
            <p>{{ alertMessage }}</p>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-3.5 text-xs">
          <!-- Login (Employee ID or Email) -->
          <div>
            <label for="login" class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
              Employee ID or Email
            </label>
            <input
              id="login"
              v-model.trim="form.login"
              type="text"
              required
              autocomplete="username"
              placeholder="e.g. admin@lcc.com.ph"
              :class="[
                'w-full px-3 py-1.5 rounded-md border text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-neutral-900 focus:ring-0 transition-colors',
                errors.login ? 'border-rose-300 bg-rose-50/30' : 'border-neutral-200 hover:border-neutral-300'
              ]"
            />
            <p v-if="errors.login" class="mt-1 text-[11px] text-rose-600">
              {{ errors.login[0] }}
            </p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-[10px] font-mono uppercase tracking-wider text-neutral-500 mb-1">
              Password
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                :class="[
                  'w-full px-3 py-1.5 pr-8 rounded-md border text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-neutral-900 focus:ring-0 transition-colors',
                  errors.password ? 'border-rose-300 bg-rose-50/30' : 'border-neutral-200 hover:border-neutral-300'
                ]"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-neutral-400 hover:text-neutral-600"
              >
                <span class="text-[10px] font-mono">{{ showPassword ? 'Hide' : 'Show' }}</span>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-[11px] text-rose-600">
              {{ errors.password[0] }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between pt-0.5">
            <label class="flex items-center space-x-2 cursor-pointer select-none">
              <input
                v-model="form.remember"
                type="checkbox"
                class="w-3.5 h-3.5 rounded border-neutral-300 text-neutral-900 focus:ring-0"
              />
              <span class="text-xs text-neutral-600">Remember session</span>
            </label>
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
            <span>{{ isSubmitting ? 'Authenticating...' : 'Sign In' }}</span>
          </button>
        </form>

        <!-- Pre-fill Quick Test Link -->
        <div class="mt-5 pt-4 border-t border-neutral-100 flex items-center justify-between text-[11px]">
          <span class="text-neutral-400">Admin Demo:</span>
          <button
            type="button"
            @click="fillAdminCredentials"
            class="font-mono text-neutral-700 hover:text-neutral-900 hover:underline"
          >
            admin@lcc.com.ph
          </button>
        </div>
      </div>

      <!-- Registration link footer -->
      <div class="mt-4 text-center text-xs text-neutral-500">
        Don't have an account yet?
        <button
          type="button"
          @click="$emit('switch-view', 'register')"
          class="font-medium text-neutral-900 hover:underline ml-1"
        >
          Register
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useAuth } from '../composables/useAuth';

const emit = defineEmits(['login-success', 'switch-view']);

const { login } = useAuth();

const form = reactive({
  login: '',
  password: '',
  remember: false,
});

const showPassword = ref(false);
const isSubmitting = ref(false);
const alertMessage = ref('');
const alertTitle = ref('');
const alertType = ref('error'); // 'error' or 'warning'
const errors = ref({});

const alertClasses = computed(() => {
  if (alertType.value === 'warning') {
    return 'bg-amber-50 border border-amber-200 text-amber-800';
  }
  return 'bg-rose-50 border border-rose-200 text-rose-800';
});

function fillAdminCredentials() {
  form.login = 'admin@lcc.com.ph';
  form.password = 'password';
  form.remember = true;
  alertMessage.value = '';
}

async function handleSubmit() {
  isSubmitting.value = true;
  alertMessage.value = '';
  alertTitle.value = '';
  errors.value = {};

  try {
    const res = await login({
      login: form.login,
      password: form.password,
      remember: form.remember,
    });
    emit('login-success', res.user);
  } catch (err) {
    if (err.status === 403) {
      // Pending or Disabled status handling per BRD
      if (err.data?.status === 'pending') {
        alertType.value = 'warning';
        alertTitle.value = 'Account Pending Approval';
        alertMessage.value = err.message || 'Your account registration is awaiting Administrator approval.';
      } else {
        alertType.value = 'error';
        alertTitle.value = 'Account Disabled';
        alertMessage.value = err.message || 'Your account is disabled. Portal access has been revoked.';
      }
    } else if (err.status === 422) {
      errors.value = err.errors || {};
      alertType.value = 'error';
      alertTitle.value = 'Invalid Credentials';
      alertMessage.value = err.message || 'The credentials you entered do not match our records.';
    } else {
      alertType.value = 'error';
      alertTitle.value = 'Authentication Failed';
      alertMessage.value = err.message || 'Unable to log in. Please try again.';
    }
  } finally {
    isSubmitting.value = false;
  }
}
</script>


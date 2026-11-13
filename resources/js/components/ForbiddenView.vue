<template>
  <div class="min-h-[calc(100vh-8rem)] flex items-center justify-center p-4">
    <div class="max-w-sm w-full bg-white rounded-lg border border-neutral-200 p-6 text-center shadow-xs">
      <div class="w-10 h-10 rounded-md bg-neutral-100 text-neutral-800 flex items-center justify-center mx-auto mb-3">
        <svg class="w-5 h-5 text-neutral-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>

      <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 mb-1">
        HTTP 403 Forbidden
      </div>

      <h2 class="text-base font-semibold text-neutral-900">
        Access Denied
      </h2>

      <p class="text-xs text-neutral-500 mt-1.5 leading-relaxed">
        Your role (<span class="font-medium text-neutral-800">{{ roleDisplayName }}</span>) does not have authorization to access this resource.
      </p>

      <div class="mt-5 pt-4 border-t border-neutral-100 flex items-center justify-center space-x-2">
        <button
          type="button"
          @click="$emit('navigate', 'dashboard')"
          class="px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-md text-xs font-medium transition-colors"
        >
          Return to Dashboard
        </button>
        <button
          type="button"
          @click="$emit('navigate', 'rbac')"
          class="px-3 py-1.5 bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-md text-xs font-medium transition-colors"
        >
          RBAC Matrix
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '../composables/useAuth';

defineEmits(['navigate']);

const { roleDisplayName } = useAuth();
</script>

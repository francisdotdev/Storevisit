<template>
  <div class="py-5 sm:py-7">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
      <!-- Welcome Header -->
      <div class="bg-white p-5 sm:p-6 rounded-xl border border-neutral-200/80 shadow-xs relative overflow-hidden">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-brand-50 rounded-full blur-2xl pointer-events-none opacity-60"></div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
          <div>
            <div class="flex items-center flex-wrap gap-2 text-[11px] font-mono text-neutral-400 uppercase tracking-wider">
              <span class="text-neutral-500 font-medium">Department:</span>
              <span class="text-brand-700 font-semibold bg-brand-50 px-2 py-0.5 rounded">{{ departmentName }}</span>
              <span>&bull;</span>
              <span class="text-neutral-500 font-medium">Scope:</span>
              <span class="text-neutral-700 font-semibold">{{ scopeName }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-2">
              Welcome back, {{ user?.first_name }}
            </h1>
            <p class="text-xs text-neutral-600 mt-1 max-w-2xl leading-relaxed">
              {{ roleDescription }}
            </p>
          </div>

          <!-- Role Badge -->
          <div class="inline-flex flex-col sm:items-end justify-center bg-brand-50/60 border border-brand-100 px-4 py-2.5 rounded-xl shadow-xs shrink-0">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-brand-600">Assigned Role</span>
            <span class="text-sm font-bold text-neutral-900 mt-0.5">{{ roleDisplayName }}</span>
            <span class="text-[11px] font-mono text-neutral-500">{{ user?.employee_id }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Action / Module Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <!-- Store Visit Calendar Card -->
        <div
          v-if="hasPermission('calendar.view') || hasPermission('calendar.view_own')"
          @click="$emit('navigate', 'calendar')"
          class="bg-white p-5 rounded-xl border border-neutral-200/80 hover:border-brand-400 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-3 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-neutral-900 group-hover:text-brand-600 transition-colors">
              Visit Calendar
            </h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">
              Plan visits, review store schedules, and conduct store audit executions in the field.
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs font-semibold text-brand-600 min-h-[44px]">
            <span>Open Calendar</span>
            <span class="text-neutral-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all">&rarr;</span>
          </div>
        </div>

        <!-- Checklist Templates (Managers / Admin) -->
        <div
          v-if="hasPermission('checklist.manage.treasury') || hasPermission('checklist.manage.cashier') || isAdmin"
          @click="$emit('navigate', 'checklists')"
          class="bg-white p-5 rounded-xl border border-neutral-200/80 hover:border-brand-400 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-3 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-neutral-900 group-hover:text-brand-600 transition-colors">
              Checklist Templates
            </h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">
              Configure audit categories, questions, and weighted scoring criteria for evaluations.
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs font-semibold text-brand-600 min-h-[44px]">
            <span>Manage Checklists</span>
            <span class="text-neutral-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all">&rarr;</span>
          </div>
        </div>

        <!-- Observations Card -->
        <div
          v-if="hasPermission('observation.view') || hasPermission('observation.create') || hasPermission('observations.view_all')"
          @click="$emit('navigate', 'observations')"
          class="bg-white p-5 rounded-xl border border-neutral-200/80 hover:border-brand-400 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-3 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-neutral-900 group-hover:text-brand-600 transition-colors">
              Audit Observations
            </h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">
              Log store findings, assign corrective actions, and track resolutions across stores.
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs font-semibold text-brand-600 min-h-[44px]">
            <span>View Observations</span>
            <span class="text-neutral-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all">&rarr;</span>
          </div>
        </div>

        <!-- Ratings Matrix Card -->
        <div
          v-if="hasPermission('rating.view_monitoring') || hasPermission('rating.view') || hasPermission('ratings.view_all')"
          @click="$emit('navigate', 'ratings')"
          class="bg-white p-5 rounded-xl border border-neutral-200/80 hover:border-brand-400 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-3 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-neutral-900 group-hover:text-brand-600 transition-colors">
              Ratings Monitoring
            </h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">
              Review weekly W1–W4 compliance scores and performance rankings across store branches.
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs font-semibold text-brand-600 min-h-[44px]">
            <span>Open Matrix</span>
            <span class="text-neutral-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all">&rarr;</span>
          </div>
        </div>

        <!-- Executive Reports Card -->
        <div
          v-if="hasPermission('reports.view') || hasPermission('reports.export') || hasPermission('reports.export_own')"
          @click="$emit('navigate', 'reports')"
          class="bg-white p-5 rounded-xl border border-neutral-200/80 hover:border-brand-400 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-3 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-neutral-900 group-hover:text-brand-600 transition-colors">
              Executive Reports
            </h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">
              Export comprehensive visit logs and observation records to CSV, Excel, and PDF formats.
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs font-semibold text-brand-600 min-h-[44px]">
            <span>Generate Reports</span>
            <span class="text-neutral-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all">&rarr;</span>
          </div>
        </div>

        <!-- Store & Area Management Card -->
        <div
          v-if="hasPermission('stores.view') || hasPermission('stores.view_assigned')"
          @click="$emit('navigate', 'stores')"
          class="bg-white p-5 rounded-xl border border-neutral-200/80 hover:border-brand-400 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-3 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-neutral-900 group-hover:text-brand-600 transition-colors">
              Stores &amp; Areas
            </h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">
              Area locations, store directory, and supervisor store assignment configurations.
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs font-semibold text-brand-600 min-h-[44px]">
            <span>Manage Stores</span>
            <span class="text-neutral-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all">&rarr;</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuth } from '../composables/useAuth';

const emit = defineEmits(['navigate']);
const { user, isAdmin, roleDisplayName, hasPermission } = useAuth();

const departmentName = computed(() => {
  const roleName = user.value?.role?.name;
  if (roleName === 'admin') return 'System Administration';
  if (roleName === 'manager') return 'Operations Management';
  if (roleName?.includes('treasury')) return 'Treasury Department';
  if (roleName?.includes('cashier')) return 'Cashiering Department';
  return 'General Operations';
});

const scopeName = computed(() => {
  const roleName = user.value?.role?.name;
  if (roleName === 'admin') return 'Full System Access';
  if (roleName === 'manager') return 'Cross-Department Executive Oversight';
  if (roleName?.includes('manager')) return 'Department Administrator';
  if (roleName?.includes('supervisor')) return 'Field Auditor (Assigned Stores)';
  return 'Authenticated User';
});

const roleDescription = computed(() => {
  const roleName = user.value?.role?.name;
  if (roleName === 'admin') {
    return 'Full administrative access to manage users, branch codes, area locations, and access security.';
  }
  if (roleName === 'manager') {
    return 'Executive access to review multi-store audit visits, monitor performance ratings, and download executive reports.';
  }
  if (roleName === 'asst_treasury_manager') {
    return 'Manage Treasury area assignments, store visit planning, and checklist template allocations.';
  }
  if (roleName === 'asst_cashier_manager') {
    return 'Manage Cashier area assignments, store visit planning, and checklist template allocations.';
  }
  if (roleName === 'area_treasury_supervisor') {
    return 'Conduct Treasury store audit checklists, inspect cash operations, and log improvement observations.';
  }
  if (roleName === 'area_cashier_supervisor') {
    return 'Conduct Cashier store audit checklists, inspect register operations, and log improvement observations.';
  }
  return 'Access granted per approved system role.';
});
</script>

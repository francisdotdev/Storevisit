<template>
  <div>
    <!-- Top Mobile / Desktop Header -->
    <header class="bg-white/95 backdrop-blur-sm border-b border-neutral-200 sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
          <!-- Left: Mobile Hamburger Toggle + Brand for Mobile -->
          <div class="flex items-center space-x-3">
            <button
              v-if="user"
              type="button"
              @click="$emit('toggle-sidebar')"
              class="md:hidden p-2 -ml-2 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition-colors tap-target flex items-center justify-center"
              aria-label="Open navigation menu"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>

            <!-- Brand Logo (Visible on mobile; on desktop sidebar has brand) -->
            <div class="flex items-center space-x-2.5 cursor-pointer md:hidden" @click="$emit('navigate', 'dashboard')">
              <div class="w-7 h-7 rounded-md bg-brand-600 flex items-center justify-center text-white font-bold text-xs tracking-wider shadow-xs">
                LCC
              </div>
              <span class="text-sm font-bold text-neutral-900 tracking-tight">
                Store Visit Portal
              </span>
            </div>

            <!-- Desktop View Breadcrumb / Current Location -->
            <div class="hidden md:flex items-center space-x-2 text-xs">
              <span class="font-semibold text-neutral-900">{{ currentViewTitle }}</span>
              <span class="text-neutral-300">&bull;</span>
              <span class="text-[11px] font-mono text-neutral-400 uppercase tracking-wider">
                {{ departmentName }}
              </span>
            </div>
          </div>

          <!-- Right: Role & Profile Info -->
          <div v-if="user" class="flex items-center space-x-2.5">
            <!-- Role Badge -->
            <div class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full bg-brand-50 border border-brand-200 text-brand-700 text-[10px] font-mono uppercase tracking-wider font-semibold">
              {{ roleDisplayName }}
            </div>

            <!-- Quick Action or Profile Pill -->
            <div class="flex items-center space-x-2">
              <div class="w-7 h-7 rounded-full bg-brand-600 text-white font-semibold text-xs flex items-center justify-center shadow-xs">
                {{ userInitials }}
              </div>
            </div>
          </div>

          <!-- Unauthenticated: Sign In / Register toggle -->
          <div v-else class="flex items-center space-x-2">
            <button
              v-if="currentView === 'register'"
              @click="$emit('navigate', 'login')"
              class="px-3.5 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-100 rounded-md transition-colors tap-target flex items-center"
            >
              Sign In
            </button>
            <button
              v-else
              @click="$emit('navigate', 'register')"
              class="px-3.5 py-2 text-xs font-medium bg-brand-600 hover:bg-brand-700 text-white rounded-md transition-colors tap-target flex items-center shadow-xs"
            >
              Register Account
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Bottom Navigation Bar (Fixed for Field Supervisors & Users, md:hidden) -->
    <!-- Hidden during active audit execution to leave full room for sticky progress & save/submit bar -->
    <nav
      v-if="user && currentView !== 'audit'"
      class="md:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur-md border-t border-neutral-200 shadow-lg px-2 py-1.5"
    >
      <div class="grid grid-cols-4 gap-1">
        <!-- 1. Today (Dashboard) -->
        <button
          type="button"
          @click="$emit('navigate', 'dashboard')"
          :class="[
            'flex flex-col items-center justify-center py-1.5 px-1 rounded-lg tap-target transition-colors',
            currentView === 'dashboard' ? 'text-brand-600 font-semibold' : 'text-neutral-500 hover:text-neutral-900'
          ]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span class="text-[10px] tracking-tight mt-0.5">Today</span>
        </button>

        <!-- 2. Calendar -->
        <button
          v-if="hasPermission('calendar.view') || hasPermission('calendar.view_own')"
          type="button"
          @click="$emit('navigate', 'calendar')"
          :class="[
            'flex flex-col items-center justify-center py-1.5 px-1 rounded-lg tap-target transition-colors',
            currentView === 'calendar' ? 'text-brand-600 font-semibold' : 'text-neutral-500 hover:text-neutral-900'
          ]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="text-[10px] tracking-tight mt-0.5">Calendar</span>
        </button>

        <!-- 3. Observations -->
        <button
          v-if="hasPermission('observation.view') || hasPermission('observation.create') || hasPermission('observations.view_all')"
          type="button"
          @click="$emit('navigate', 'observations')"
          :class="[
            'flex flex-col items-center justify-center py-1.5 px-1 rounded-lg tap-target transition-colors',
            currentView === 'observations' ? 'text-brand-600 font-semibold' : 'text-neutral-500 hover:text-neutral-900'
          ]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <span class="text-[10px] tracking-tight mt-0.5">Observations</span>
        </button>

        <!-- 4. Menu (Opens Sidebar Drawer) -->
        <button
          type="button"
          @click="$emit('toggle-sidebar')"
          class="flex flex-col items-center justify-center py-1.5 px-1 rounded-lg tap-target text-neutral-500 hover:text-neutral-900 transition-colors"
        >
          <div class="relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span
              v-if="pendingCount > 0"
              class="absolute -top-1 -right-1.5 w-2 h-2 rounded-full bg-brand-600 ring-2 ring-white"
            ></span>
          </div>
          <span class="text-[10px] tracking-tight mt-0.5">More</span>
        </button>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuth } from '../composables/useAuth';

const props = defineProps({
  currentView: {
    type: String,
    required: true,
  },
  pendingCount: {
    type: Number,
    default: 0,
  },
});

defineEmits(['navigate', 'toggle-sidebar']);

const { user, roleDisplayName, hasPermission } = useAuth();

const userInitials = computed(() => {
  if (!user.value) return 'LC';
  const f = user.value.first_name?.[0] || '';
  const l = user.value.last_name?.[0] || '';
  return (f + l).toUpperCase() || 'U';
});

const currentViewTitle = computed(() => {
  const titles = {
    dashboard: 'Dashboard Overview',
    calendar: 'Store Visit Calendar',
    audit: 'Audit Execution Workspace',
    checklists: 'Checklist Templates',
    observations: 'Audit Observations',
    ratings: 'Ratings Monitoring Matrix',
    reports: 'Executive Reports & Export',
    stores: 'Store & Area Directory',
    users: 'User Account Management',
  };
  return titles[props.currentView] || 'Portal';
});

const departmentName = computed(() => {
  const roleName = user.value?.role?.name;
  if (roleName === 'admin') return 'System Administration';
  if (roleName === 'manager') return 'Operations Management';
  if (roleName?.includes('treasury')) return 'Treasury Department';
  if (roleName?.includes('cashier')) return 'Cashiering Department';
  return 'LCC Operations';
});
</script>

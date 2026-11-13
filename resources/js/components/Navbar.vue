<template>
  <header class="bg-white/95 backdrop-blur-sm border-b border-neutral-200 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-14">
        <!-- Brand / Logo -->
        <div class="flex items-center space-x-2.5 cursor-pointer" @click="$emit('navigate', 'dashboard')">
          <div class="w-7 h-7 rounded bg-neutral-900 flex items-center justify-center text-white font-bold text-xs tracking-wider shadow-xs">
            LCC
          </div>
          <div class="flex items-baseline space-x-2">
            <span class="text-sm font-semibold text-neutral-900 tracking-tight">
              Store Visit
            </span>
            <span class="text-[11px] text-neutral-400 font-mono hidden sm:inline">
              Audit Portal
            </span>
          </div>
        </div>

        <!-- Navigation Tabs (Enforced via RBAC Permissions) -->
        <nav v-if="user" class="hidden md:flex items-center space-x-1">
          <button
            type="button"
            @click="$emit('navigate', 'dashboard')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'dashboard'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Dashboard
          </button>

          <!-- Store Visit Calendar -->
          <button
            v-if="hasPermission('calendar.view') || hasPermission('calendar.view_own')"
            type="button"
            @click="$emit('navigate', 'calendar')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'calendar'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Calendar
          </button>

          <!-- Checklist Templates (Managers / Admin) -->
          <button
            v-if="hasPermission('checklist.manage.treasury') || hasPermission('checklist.manage.cashier') || isAdmin"
            type="button"
            @click="$emit('navigate', 'checklists')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'checklists'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Checklists
          </button>

          <!-- Observations -->
          <button
            v-if="hasPermission('observation.view') || hasPermission('observation.create') || hasPermission('observations.view_all')"
            type="button"
            @click="$emit('navigate', 'observations')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'observations'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Observations
          </button>

          <!-- Ratings Matrix -->
          <button
            v-if="hasPermission('rating.view_monitoring') || hasPermission('rating.view') || hasPermission('ratings.view_all')"
            type="button"
            @click="$emit('navigate', 'ratings')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'ratings'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Ratings
          </button>

          <!-- Reports -->
          <button
            v-if="hasPermission('reports.view') || hasPermission('reports.export') || hasPermission('reports.export_own')"
            type="button"
            @click="$emit('navigate', 'reports')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'reports'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Reports
          </button>

          <!-- Stores & Areas -->
          <button
            v-if="hasPermission('stores.view') || hasPermission('stores.view_assigned')"
            type="button"
            @click="$emit('navigate', 'stores')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors',
              currentView === 'stores'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            Stores &amp; Areas
          </button>

          <!-- User Management (Admin only) -->
          <button
            v-if="hasPermission('users.view')"
            type="button"
            @click="$emit('navigate', 'users')"
            :class="[
              'px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center space-x-1.5',
              currentView === 'users'
                ? 'bg-neutral-100 text-neutral-900 font-semibold'
                : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50'
            ]"
          >
            <span>Users</span>
            <span
              v-if="pendingCount > 0"
              class="px-1.5 py-0.2 text-[10px] font-mono rounded bg-neutral-900 text-white font-medium"
            >
              {{ pendingCount }}
            </span>
          </button>
        </nav>

        <!-- User Profile & Logout -->
        <div v-if="user" class="flex items-center space-x-3">
          <div class="hidden sm:flex flex-col items-end leading-tight">
            <span class="text-xs font-medium text-neutral-800">
              {{ user.first_name }} {{ user.last_name }}
            </span>
            <div class="flex items-center space-x-1.5 mt-0.5">
              <span class="text-[10px] font-mono text-neutral-400">{{ user.employee_id }}</span>
              <span class="text-neutral-300">&bull;</span>
              <span class="text-[10px] font-mono uppercase text-neutral-500 bg-neutral-100 px-1.5 py-0.5 rounded border border-neutral-200">
                {{ roleDisplayName }}
              </span>
            </div>
          </div>

          <button
            type="button"
            @click="handleLogout"
            class="inline-flex items-center space-x-1.5 px-2.5 py-1 text-xs text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 rounded-md transition-colors border border-neutral-200"
            title="Log out from portal"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden sm:inline">Logout</span>
          </button>
        </div>

        <!-- Logged Out view switch -->
        <div v-else class="flex items-center space-x-2">
          <button
            v-if="currentView === 'register'"
            @click="$emit('navigate', 'login')"
            class="px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-100 rounded-md transition-colors"
          >
            Sign In
          </button>
          <button
            v-else
            @click="$emit('navigate', 'register')"
            class="px-3 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors"
          >
            Register Account
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
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

const emit = defineEmits(['navigate', 'logout']);

const { user, isAdmin, isManager, isAsstManager, roleDisplayName, hasPermission, logout } = useAuth();

async function handleLogout() {
  if (confirm('Are you sure you want to log out of the LCC Store Visit Portal?')) {
    await logout();
    emit('logout');
  }
}
</script>

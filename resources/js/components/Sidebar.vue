<template>
  <div>
    <!-- Mobile Drawer Backdrop Overlay -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-40 bg-neutral-900/60 backdrop-blur-xs md:hidden transition-opacity"
      @click="$emit('close')"
      aria-hidden="true"
    ></div>

    <!-- Sidebar Container (Fixed desktop / Slide-out mobile) -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex flex-col w-64 lg:w-72 bg-white border-r border-neutral-200/80 transition-transform duration-300 ease-in-out md:translate-x-0',
        isOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full md:shadow-none'
      ]"
    >
      <!-- Brand Header -->
      <div class="h-16 flex items-center justify-between px-5 border-b border-neutral-100 bg-white">
        <div class="flex items-center space-x-3 cursor-pointer" @click="handleNav('dashboard')">
          <div class="w-9 h-9 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold text-sm tracking-wider shadow-sm">
            LCC
          </div>
          <div class="flex flex-col">
            <span class="text-sm font-bold text-neutral-900 tracking-tight leading-tight">
              Store Visit
            </span>
            <span class="text-[11px] text-brand-600 font-medium">
              Audit &amp; Compliance Portal
            </span>
          </div>
        </div>

        <!-- Mobile Close Button -->
        <button
          type="button"
          @click="$emit('close')"
          class="md:hidden p-2 -mr-2 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors tap-target flex items-center justify-center"
          aria-label="Close menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation Links (Scrollable) -->
      <nav class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">
        <!-- Section: Overview -->
        <div>
          <div class="px-2.5 mb-1.5 text-[10px] font-mono uppercase tracking-wider text-neutral-400 font-semibold">
            Overview
          </div>
          <div class="space-y-1">
            <button
              type="button"
              @click="handleNav('dashboard')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'dashboard'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'dashboard' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <span>Dashboard</span>
            </button>
          </div>
        </div>

        <!-- Section: Field Auditing -->
        <div>
          <div class="px-2.5 mb-1.5 text-[10px] font-mono uppercase tracking-wider text-neutral-400 font-semibold">
            Field Operations
          </div>
          <div class="space-y-1">
            <!-- Calendar -->
            <button
              v-if="hasPermission('calendar.view') || hasPermission('calendar.view_own')"
              type="button"
              @click="handleNav('calendar')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'calendar' || currentView === 'audit'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'calendar' || currentView === 'audit' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Visit Calendar</span>
            </button>

            <!-- Observations -->
            <button
              v-if="hasPermission('observation.view') || hasPermission('observation.create') || hasPermission('observations.view_all')"
              type="button"
              @click="handleNav('observations')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'observations'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'observations' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <span>Audit Observations</span>
            </button>
          </div>
        </div>

        <!-- Section: Analytics & Governance -->
        <div v-if="hasAny(['rating.view_monitoring', 'rating.view', 'reports.view', 'checklist.manage.treasury', 'checklist.manage.cashier'])">
          <div class="px-2.5 mb-1.5 text-[10px] font-mono uppercase tracking-wider text-neutral-400 font-semibold">
            Compliance &amp; Reports
          </div>
          <div class="space-y-1">
            <!-- Ratings Matrix -->
            <button
              v-if="hasPermission('rating.view_monitoring') || hasPermission('rating.view') || hasPermission('ratings.view_all')"
              type="button"
              @click="handleNav('ratings')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'ratings'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'ratings' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span>Ratings Matrix</span>
            </button>

            <!-- Checklist Templates -->
            <button
              v-if="hasPermission('checklist.manage.treasury') || hasPermission('checklist.manage.cashier') || isAdmin"
              type="button"
              @click="handleNav('checklists')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'checklists'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'checklists' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              <span>Checklists</span>
            </button>

            <!-- Reports -->
            <button
              v-if="hasPermission('reports.view') || hasPermission('reports.export') || hasPermission('reports.export_own')"
              type="button"
              @click="handleNav('reports')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'reports'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'reports' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>Executive Reports</span>
            </button>
          </div>
        </div>

        <!-- Section: Administration -->
        <div v-if="hasAny(['stores.view', 'stores.view_assigned', 'users.view'])">
          <div class="px-2.5 mb-1.5 text-[10px] font-mono uppercase tracking-wider text-neutral-400 font-semibold">
            Administration
          </div>
          <div class="space-y-1">
            <!-- Stores & Areas -->
            <button
              v-if="hasPermission('stores.view') || hasPermission('stores.view_assigned')"
              type="button"
              @click="handleNav('stores')"
              :class="[
                'w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'stores'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <svg :class="['w-4 h-4 shrink-0', currentView === 'stores' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <span>Stores &amp; Areas</span>
            </button>

            <!-- User Management (Admin Only) -->
            <button
              v-if="hasPermission('users.view')"
              type="button"
              @click="handleNav('users')"
              :class="[
                'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition-all tap-target text-left',
                currentView === 'users'
                  ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs border-l-3 border-brand-600'
                  : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
              ]"
            >
              <div class="flex items-center space-x-3">
                <svg :class="['w-4 h-4 shrink-0', currentView === 'users' ? 'text-brand-600' : 'text-neutral-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Users</span>
              </div>
              <span
                v-if="pendingCount > 0"
                class="px-2 py-0.5 text-[10px] font-mono rounded-full bg-brand-600 text-white font-semibold"
              >
                {{ pendingCount }}
              </span>
            </button>
          </div>
        </div>
      </nav>

      <!-- User Profile & Session Footer -->
      <div v-if="user" class="p-3.5 border-t border-neutral-100 bg-neutral-50/70">
        <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-white border border-neutral-200/80 shadow-xs">
          <!-- User Details -->
          <div class="flex items-center space-x-2.5 min-w-0">
            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 font-semibold text-xs flex items-center justify-center shrink-0">
              {{ userInitials }}
            </div>
            <div class="min-w-0 leading-tight">
              <p class="text-xs font-semibold text-neutral-900 truncate">
                {{ user.first_name }} {{ user.last_name }}
              </p>
              <p class="text-[10px] font-mono text-neutral-400 truncate">
                {{ user.employee_id }} &bull; {{ roleDisplayName }}
              </p>
            </div>
          </div>

          <!-- Logout Button -->
          <button
            type="button"
            @click="handleLogout"
            class="p-2 text-neutral-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors tap-target flex items-center justify-center"
            title="Log out of portal"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>
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
  isOpen: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['navigate', 'close', 'logout']);

const { user, isAdmin, roleDisplayName, hasPermission, logout } = useAuth();

const userInitials = computed(() => {
  if (!user.value) return 'LC';
  const f = user.value.first_name?.[0] || '';
  const l = user.value.last_name?.[0] || '';
  return (f + l).toUpperCase() || 'U';
});

function hasAny(permissions) {
  return permissions.some(p => hasPermission(p));
}

function handleNav(view) {
  emit('navigate', view);
  emit('close');
}

async function handleLogout() {
  if (confirm('Are you sure you want to log out of the LCC Store Visit Portal?')) {
    await logout();
    emit('logout');
  }
}
</script>


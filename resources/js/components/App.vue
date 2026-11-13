<template>
  <div class="min-h-screen flex flex-col bg-neutral-50/80 text-neutral-900 antialiased font-sans">
    <!-- Initial Session Loader -->
    <div v-if="loading" class="flex-1 flex items-center justify-center min-h-screen">
      <div class="text-center space-y-3">
        <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-sm tracking-wider mx-auto shadow-md animate-pulse">
          LCC
        </div>
        <div class="text-xs font-medium text-neutral-500">Loading portal workspace...</div>
      </div>
    </div>

    <template v-else>
      <!-- Authenticated Layout with Sidebar -->
      <template v-if="user">
        <!-- Modern Left Sidebar Component (Desktop sticky + Mobile slide-out drawer) -->
        <Sidebar
          :current-view="currentView"
          :pending-count="pendingCount"
          :is-open="sidebarOpen"
          @navigate="handleNavigate"
          @close="sidebarOpen = false"
          @logout="handleLogout"
        />

        <!-- Main Wrapper with desktop padding-left matching sidebar width -->
        <div class="flex-1 flex flex-col md:pl-64 lg:pl-72 min-w-0 transition-all">
          <!-- Top App Bar & Mobile Bottom Nav -->
          <Navbar
            :current-view="currentView"
            :pending-count="pendingCount"
            @toggle-sidebar="sidebarOpen = !sidebarOpen"
            @navigate="handleNavigate"
          />

          <!-- Main View Container (with bottom padding on mobile for bottom nav) -->
          <main :class="['flex-1 min-w-0', currentView !== 'audit' ? 'pb-20 md:pb-8' : 'pb-6']">
            <!-- Store Visit Calendar -->
            <CalendarView
              v-if="currentView === 'calendar'"
              @start-audit="handleStartAudit"
            />

            <!-- Audit Execution View -->
            <AuditExecutionView
              v-else-if="currentView === 'audit' && selectedScheduleId"
              :schedule-id="selectedScheduleId"
              @navigate="handleNavigate"
            />

            <!-- Checklist Templates Management -->
            <ChecklistTemplateView
              v-else-if="currentView === 'checklists'"
            />

            <!-- Audit Observations -->
            <ObservationListView
              v-else-if="currentView === 'observations'"
            />

            <!-- Ratings Monitoring Matrix -->
            <RatingsMonitoringView
              v-else-if="currentView === 'ratings'"
            />

            <!-- Executive Reports -->
            <ReportsView
              v-else-if="currentView === 'reports'"
            />

            <!-- Store & Area Management -->
            <StoreManagementView
              v-else-if="currentView === 'stores'"
            />

            <!-- User Management (Admin Only) -->
            <UserManagementView
              v-else-if="currentView === 'users' && hasPermission('users.view')"
            />

            <!-- Unauthorized Attempt -->
            <ForbiddenView
              v-else-if="currentView === 'users' && !hasPermission('users.view')"
              @navigate="handleNavigate"
            />

            <!-- Role-Based Dashboard (Default) -->
            <DashboardView
              v-else
              @navigate="handleNavigate"
            />
          </main>

          <!-- Portal Footer -->
          <footer class="bg-white border-t border-neutral-200/80 py-3.5 px-4 text-xs text-neutral-400">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
              <span class="flex items-center space-x-1.5">
                <span class="font-semibold text-neutral-700">LCC Supermarket &amp; Department Store</span>
                <span>&bull;</span>
                <span>Store Visit Audit Portal</span>
              </span>
              <span class="text-neutral-500 font-mono text-[11px]">TR1001 / Field Ready</span>
            </div>
          </footer>
        </div>
      </template>

      <!-- Unauthenticated Layout (Centered Login / Register) -->
      <template v-else>
        <!-- Top Auth Navbar -->
        <Navbar
          :current-view="currentView"
          :pending-count="0"
          @navigate="handleNavigate"
        />

        <main class="flex-1 flex items-center justify-center p-4">
          <LoginView
            v-if="currentView === 'login'"
            @login-success="onLoginSuccess"
            @switch-view="currentView = $event"
          />

          <RegisterView
            v-else-if="currentView === 'register'"
            @switch-view="currentView = $event"
            @registered="onRegistered"
          />
        </main>
      </template>

      <!-- Session Expiry Re-Login Modal (Preserves in-memory state during audits) -->
      <div
        v-if="showReloginModal"
        class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/60 backdrop-blur-xs flex items-center justify-center p-4"
      >
        <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-2xl border border-neutral-200 text-xs space-y-4">
          <div class="flex items-center space-x-3 text-amber-600">
            <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-neutral-900">Session Expired</h3>
              <p class="text-[11px] text-neutral-500">Your answers are safe in memory.</p>
            </div>
          </div>

          <p class="text-neutral-600 leading-relaxed">
            Please enter your password to re-authenticate and resume your audit without losing any input.
          </p>

          <form @submit.prevent="handleRelogin" class="space-y-3">
            <div>
              <label class="block text-[10px] font-mono uppercase text-neutral-400 mb-1">Password</label>
              <input
                v-model="reloginPassword"
                type="password"
                required
                placeholder="••••••••"
                class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200 focus:border-brand-600 focus:ring-1 focus:ring-brand-600"
              />
            </div>

            <div v-if="reloginError" class="text-rose-600 text-xs font-medium">
              {{ reloginError }}
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
              <button
                type="submit"
                :disabled="isReloggingIn"
                class="w-full py-2.5 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-medium text-xs shadow-xs tap-target flex items-center justify-center"
              >
                {{ isReloggingIn ? 'Re-authenticating...' : 'Resume Session' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent } from 'vue';
import { useAuth } from '../composables/useAuth';
import Navbar from './Navbar.vue';
import Sidebar from './Sidebar.vue';
import LoginView from './LoginView.vue';
import RegisterView from './RegisterView.vue';
import DashboardView from './DashboardView.vue';
import CalendarView from './CalendarView.vue';
import AuditExecutionView from './AuditExecutionView.vue';

// Lazy-loaded routes for performance & slow 4G optimization (reducing initial JS bundle)
const ChecklistTemplateView = defineAsyncComponent(() => import('./ChecklistTemplateView.vue'));
const ObservationListView = defineAsyncComponent(() => import('./ObservationListView.vue'));
const RatingsMonitoringView = defineAsyncComponent(() => import('./RatingsMonitoringView.vue'));
const ReportsView = defineAsyncComponent(() => import('./ReportsView.vue'));
const StoreManagementView = defineAsyncComponent(() => import('./StoreManagementView.vue'));
const UserManagementView = defineAsyncComponent(() => import('./UserManagementView.vue'));
const ForbiddenView = defineAsyncComponent(() => import('./ForbiddenView.vue'));

const { user, loading, hasPermission, checkAuth, login } = useAuth();

const currentView = ref('login');
const selectedScheduleId = ref(null);
const pendingCount = ref(0);
const sidebarOpen = ref(false);

// Session re-login state
const showReloginModal = ref(false);
const reloginPassword = ref('');
const reloginError = ref('');
const isReloggingIn = ref(false);

function handleNavigate(view, payload = null) {
  if (view === 'audit' && payload) {
    selectedScheduleId.value = payload;
  }
  currentView.value = view;
  sidebarOpen.value = false;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleStartAudit(scheduleId) {
  selectedScheduleId.value = scheduleId;
  currentView.value = 'audit';
  sidebarOpen.value = false;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function onLoginSuccess() {
  currentView.value = 'dashboard';
  sidebarOpen.value = false;
}

function onRegistered() {
  currentView.value = 'login';
}

function handleLogout() {
  selectedScheduleId.value = null;
  sidebarOpen.value = false;
  currentView.value = 'login';
}

async function handleRelogin() {
  if (!user.value || !reloginPassword.value) return;
  isReloggingIn.value = true;
  reloginError.value = '';
  try {
    await login(user.value.employee_id || user.value.email, reloginPassword.value);
    showReloginModal.value = false;
    reloginPassword.value = '';
    window.dispatchEvent(new CustomEvent('session-resumed'));
  } catch (err) {
    reloginError.value = err.message || 'Invalid password.';
  } finally {
    isReloggingIn.value = false;
  }
}

onMounted(async () => {
  window.addEventListener('session-expired', () => {
    showReloginModal.value = true;
  });

  await checkAuth();
  if (user.value) {
    currentView.value = 'dashboard';
  } else {
    currentView.value = 'login';
  }
});
</script>

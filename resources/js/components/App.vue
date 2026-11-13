<template>
  <div class="min-h-screen flex flex-col bg-neutral-50 text-neutral-900 antialiased">
    <!-- Initial Session Loader -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="text-center space-y-3">
        <div class="w-9 h-9 rounded-md bg-neutral-900 text-white flex items-center justify-center font-bold text-xs tracking-wider mx-auto shadow-xs">
          LCC
        </div>
        <div class="text-xs font-medium text-neutral-500">Loading portal...</div>
      </div>
    </div>

    <template v-else>
      <!-- Navigation Header -->
      <Navbar
        :current-view="currentView"
        :pending-count="pendingCount"
        @navigate="handleNavigate"
        @logout="handleLogout"
      />

      <!-- Main Content Area -->
      <main class="flex-1">
        <!-- Not Authenticated: Login -->
        <LoginView
          v-if="!user && currentView === 'login'"
          @login-success="onLoginSuccess"
          @switch-view="currentView = $event"
        />

        <!-- Not Authenticated: Register -->
        <RegisterView
          v-else-if="!user && currentView === 'register'"
          @switch-view="currentView = $event"
          @registered="onRegistered"
        />

        <!-- Authenticated: Store Visit Calendar -->
        <CalendarView
          v-else-if="user && currentView === 'calendar'"
          @start-audit="handleStartAudit"
        />

        <!-- Authenticated: Audit Execution View -->
        <AuditExecutionView
          v-else-if="user && currentView === 'audit' && selectedScheduleId"
          :schedule-id="selectedScheduleId"
          @navigate="handleNavigate"
        />

        <!-- Authenticated: Checklist Templates Management -->
        <ChecklistTemplateView
          v-else-if="user && currentView === 'checklists'"
        />

        <!-- Authenticated: Audit Observations -->
        <ObservationListView
          v-else-if="user && currentView === 'observations'"
        />

        <!-- Authenticated: Ratings Monitoring Matrix -->
        <RatingsMonitoringView
          v-else-if="user && currentView === 'ratings'"
        />

        <!-- Authenticated: Executive Reports -->
        <ReportsView
          v-else-if="user && currentView === 'reports'"
        />

        <!-- Authenticated: Store & Area Management -->
        <StoreManagementView
          v-else-if="user && currentView === 'stores'"
        />

        <!-- Authenticated: User Management (Admin Only) -->
        <UserManagementView
          v-else-if="user && currentView === 'users' && hasPermission('users.view')"
        />

        <!-- Authenticated: Unauthorized Attempt -->
        <ForbiddenView
          v-else-if="user && currentView === 'users' && !hasPermission('users.view')"
          @navigate="handleNavigate"
        />

        <!-- Authenticated: Dashboard (Default) -->
        <DashboardView
          v-else-if="user"
          @navigate="handleNavigate"
        />
      </main>

      <!-- Portal Footer -->
      <footer class="bg-white border-t border-neutral-200 py-3.5 px-4 text-xs text-neutral-400">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>&copy; {{ new Date().getFullYear() }} LCC Supermarket &amp; Department Store &bull; Store Visit Audit Portal</span>
          <span class="text-neutral-500 font-mono text-[11px]">Audit System</span>
        </div>
      </footer>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import Navbar from './Navbar.vue';
import LoginView from './LoginView.vue';
import RegisterView from './RegisterView.vue';
import DashboardView from './DashboardView.vue';
import UserManagementView from './UserManagementView.vue';
import StoreManagementView from './StoreManagementView.vue';
import CalendarView from './CalendarView.vue';
import ChecklistTemplateView from './ChecklistTemplateView.vue';
import AuditExecutionView from './AuditExecutionView.vue';
import ObservationListView from './ObservationListView.vue';
import RatingsMonitoringView from './RatingsMonitoringView.vue';
import ReportsView from './ReportsView.vue';
import ForbiddenView from './ForbiddenView.vue';

const { user, loading, hasPermission, checkAuth } = useAuth();

const currentView = ref('login');
const selectedScheduleId = ref(null);
const pendingCount = ref(0);

function handleNavigate(view, payload = null) {
  if (view === 'audit' && payload) {
    selectedScheduleId.value = payload;
  }
  currentView.value = view;
}

function handleStartAudit(scheduleId) {
  selectedScheduleId.value = scheduleId;
  currentView.value = 'audit';
}

function onLoginSuccess() {
  currentView.value = 'dashboard';
}

function onRegistered() {
  currentView.value = 'login';
}

function handleLogout() {
  selectedScheduleId.value = null;
  currentView.value = 'login';
}

onMounted(async () => {
  await checkAuth();
  if (user.value) {
    currentView.value = 'dashboard';
  } else {
    currentView.value = 'login';
  }
});
</script>

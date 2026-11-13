<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Access Control &bull; Step 5 RBAC
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            Role-Based Access Control Matrix
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Strict BRD role separation, module permissions, department scopes, and store access boundaries.
          </p>
        </div>

        <!-- Active User Badge -->
        <div class="bg-white p-3 rounded-lg border border-neutral-200 flex items-center space-x-3 self-start sm:self-auto">
          <div class="w-8 h-8 rounded-md bg-neutral-900 text-white flex items-center justify-center font-medium text-xs font-mono">
            {{ user?.first_name?.[0] }}{{ user?.last_name?.[0] }}
          </div>
          <div>
            <div class="text-[10px] text-neutral-400 uppercase tracking-wider">Logged In As</div>
            <div class="text-xs font-semibold text-neutral-900">{{ user?.first_name }} {{ user?.last_name }}</div>
            <div class="flex items-center space-x-1 mt-0.5">
              <span class="text-[10px] font-mono text-neutral-600 bg-neutral-100 px-1.5 py-0.5 rounded">
                {{ roleDisplayName }}
              </span>
              <span v-if="auditType" class="text-[10px] font-mono text-neutral-600 bg-neutral-100 px-1.5 py-0.5 rounded">
                {{ auditType }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Permission Tester (Interactive Proof) -->
      <div class="bg-white rounded-lg border border-neutral-200 p-4 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
          <div>
            <div class="flex items-center space-x-2">
              <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
              <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900">Live Backend Authorization Probe</h3>
            </div>
            <p class="text-xs text-neutral-500 mt-0.5">
              Click any endpoint below. Laravel's backend middleware and Gates will enforce your role's permissions and return either 200 OK or 403 Forbidden.
            </p>
          </div>

          <div
            v-if="testResult"
            class="px-3 py-1.5 rounded-md text-xs font-mono border flex items-center space-x-2 transition-all"
            :class="testResult.status === 200 ? 'bg-neutral-50 border-neutral-300 text-neutral-900' : 'bg-rose-50 border-rose-200 text-rose-700'"
          >
            <span class="font-semibold">{{ testResult.status }}</span>
            <span>{{ testResult.message }}</span>
          </div>
        </div>

        <!-- Action Test Buttons -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 mt-3.5">
          <button
            type="button"
            @click="testEndpoint('/api/rbac/test/reports-view', 'View Reports')"
            :disabled="isTesting"
            class="p-2.5 rounded-md bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-left transition-colors"
          >
            <span class="text-neutral-400 text-[10px] uppercase font-mono block">reports.view</span>
            <span class="text-xs font-medium text-neutral-900 block mt-0.5">View Reports</span>
          </button>

          <button
            type="button"
            @click="testEndpoint('/api/rbac/test/reports-export', 'Export Reports')"
            :disabled="isTesting"
            class="p-2.5 rounded-md bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-left transition-colors"
          >
            <span class="text-neutral-400 text-[10px] uppercase font-mono block">reports.export</span>
            <span class="text-xs font-medium text-neutral-900 block mt-0.5">Export Reports</span>
          </button>

          <button
            type="button"
            @click="testEndpoint('/api/rbac/test/treasury-schedule', 'Schedule Treasury')"
            :disabled="isTesting"
            class="p-2.5 rounded-md bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-left transition-colors"
          >
            <span class="text-neutral-400 text-[10px] uppercase font-mono block">schedules.treasury</span>
            <span class="text-xs font-medium text-neutral-900 block mt-0.5">Treasury Visit</span>
          </button>

          <button
            type="button"
            @click="testEndpoint('/api/rbac/test/cashier-schedule', 'Schedule Cashier')"
            :disabled="isTesting"
            class="p-2.5 rounded-md bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-left transition-colors"
          >
            <span class="text-neutral-400 text-[10px] uppercase font-mono block">schedules.cashier</span>
            <span class="text-xs font-medium text-neutral-900 block mt-0.5">Cashier Visit</span>
          </button>

          <button
            type="button"
            @click="testEndpoint('/api/rbac/test/treasury-checklist', 'Treasury Checklist')"
            :disabled="isTesting"
            class="p-2.5 rounded-md bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-left transition-colors"
          >
            <span class="text-neutral-400 text-[10px] uppercase font-mono block">checklists.treasury</span>
            <span class="text-xs font-medium text-neutral-900 block mt-0.5">Treasury Audit</span>
          </button>

          <button
            type="button"
            @click="testEndpoint('/api/rbac/test/cashier-checklist', 'Cashier Checklist')"
            :disabled="isTesting"
            class="p-2.5 rounded-md bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-left transition-colors"
          >
            <span class="text-neutral-400 text-[10px] uppercase font-mono block">checklists.cashier</span>
            <span class="text-xs font-medium text-neutral-900 block mt-0.5">Cashier Audit</span>
          </button>
        </div>
      </div>

      <!-- 6 BRD Roles Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="role in rolesList"
          :key="role.role"
          :class="[
            'bg-white rounded-lg border p-4 transition-all flex flex-col justify-between',
            role.role === roleName ? 'border-neutral-900 ring-1 ring-neutral-900' : 'border-neutral-200'
          ]"
        >
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-neutral-100 text-neutral-700">
                {{ role.department }}
              </span>
              <span v-if="role.role === roleName" class="text-[10px] font-mono text-neutral-900 bg-neutral-100 border border-neutral-300 px-1.5 py-0.5 rounded">
                Current Role
              </span>
            </div>

            <h3 class="text-sm font-semibold text-neutral-900">{{ role.display_name }}</h3>
            <p class="text-xs text-neutral-500 mt-1 leading-relaxed">{{ role.description }}</p>
          </div>

          <div class="mt-3 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-400">
            <span class="font-mono text-[11px] text-neutral-500">{{ role.role }}</span>
            <span class="font-medium text-neutral-700 text-[11px]">{{ role.permissions.length }} permissions</span>
          </div>
        </div>
      </div>

      <!-- The Full Role-Permission Matrix Table -->
      <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
        <div class="p-3.5 border-b border-neutral-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900">BRD Role-Permission Comparison Matrix</h3>
            <p class="text-xs text-neutral-500">Visual breakdown of permissions across all 6 roles.</p>
          </div>
          <div class="flex items-center space-x-3 text-xs">
            <span class="flex items-center space-x-1 text-neutral-800 font-medium">
              <span class="w-3.5 h-3.5 rounded bg-neutral-100 flex items-center justify-center text-neutral-900 font-bold text-[10px]">✓</span>
              <span>Authorized</span>
            </span>
            <span class="flex items-center space-x-1 text-neutral-400">
              <span class="w-3.5 h-3.5 rounded bg-neutral-50 flex items-center justify-center text-neutral-300 text-[10px]">—</span>
              <span>Denied (403)</span>
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase tracking-wider text-[10px]">
                <th class="py-2.5 px-3 min-w-[200px]">Permission Slug / Module</th>
                <th class="py-2.5 px-2.5 text-center" :class="{ 'bg-neutral-100 text-neutral-900 font-semibold': roleName === 'admin' }">Admin</th>
                <th class="py-2.5 px-2.5 text-center" :class="{ 'bg-neutral-100 text-neutral-900 font-semibold': roleName === 'manager' }">Manager</th>
                <th class="py-2.5 px-2.5 text-center" :class="{ 'bg-neutral-100 text-neutral-900 font-semibold': roleName === 'asst_treasury_manager' }">Asst Treasury</th>
                <th class="py-2.5 px-2.5 text-center" :class="{ 'bg-neutral-100 text-neutral-900 font-semibold': roleName === 'asst_cashier_manager' }">Asst Cashier</th>
                <th class="py-2.5 px-2.5 text-center" :class="{ 'bg-neutral-100 text-neutral-900 font-semibold': roleName === 'area_treasury_supervisor' }">Treasury Sup</th>
                <th class="py-2.5 px-2.5 text-center" :class="{ 'bg-neutral-100 text-neutral-900 font-semibold': roleName === 'area_cashier_supervisor' }">Cashier Sup</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr
                v-for="perm in allPermissions"
                :key="perm"
                class="hover:bg-neutral-50/70 transition-colors"
              >
                <!-- Permission Name -->
                <td class="py-2 px-3 font-mono font-medium text-neutral-800 text-[11px]">
                  <span>{{ perm }}</span>
                </td>

                <!-- Admin -->
                <td class="py-2 px-2.5 text-center" :class="{ 'bg-neutral-100/50': roleName === 'admin' }">
                  <span v-if="matrix.admin?.permissions.includes(perm)" class="inline-flex w-4 h-4 rounded bg-neutral-900 text-white font-bold items-center justify-center text-[10px]">✓</span>
                  <span v-else class="text-neutral-300">—</span>
                </td>

                <!-- Manager -->
                <td class="py-2 px-2.5 text-center" :class="{ 'bg-neutral-100/50': roleName === 'manager' }">
                  <span v-if="matrix.manager?.permissions.includes(perm)" class="inline-flex w-4 h-4 rounded bg-neutral-900 text-white font-bold items-center justify-center text-[10px]">✓</span>
                  <span v-else class="text-neutral-300">—</span>
                </td>

                <!-- Asst Treasury Mgr -->
                <td class="py-2 px-2.5 text-center" :class="{ 'bg-neutral-100/50': roleName === 'asst_treasury_manager' }">
                  <span v-if="matrix.asst_treasury_manager?.permissions.includes(perm)" class="inline-flex w-4 h-4 rounded bg-neutral-900 text-white font-bold items-center justify-center text-[10px]">✓</span>
                  <span v-else class="text-neutral-300">—</span>
                </td>

                <!-- Asst Cashier Mgr -->
                <td class="py-2 px-2.5 text-center" :class="{ 'bg-neutral-100/50': roleName === 'asst_cashier_manager' }">
                  <span v-if="matrix.asst_cashier_manager?.permissions.includes(perm)" class="inline-flex w-4 h-4 rounded bg-neutral-900 text-white font-bold items-center justify-center text-[10px]">✓</span>
                  <span v-else class="text-neutral-300">—</span>
                </td>

                <!-- Treasury Supervisor -->
                <td class="py-2 px-2.5 text-center" :class="{ 'bg-neutral-100/50': roleName === 'area_treasury_supervisor' }">
                  <span v-if="matrix.area_treasury_supervisor?.permissions.includes(perm)" class="inline-flex w-4 h-4 rounded bg-neutral-900 text-white font-bold items-center justify-center text-[10px]">✓</span>
                  <span v-else class="text-neutral-300">—</span>
                </td>

                <!-- Cashier Supervisor -->
                <td class="py-2 px-2.5 text-center" :class="{ 'bg-neutral-100/50': roleName === 'area_cashier_supervisor' }">
                  <span v-if="matrix.area_cashier_supervisor?.permissions.includes(perm)" class="inline-flex w-4 h-4 rounded bg-neutral-900 text-white font-bold items-center justify-center text-[10px]">✓</span>
                  <span v-else class="text-neutral-300">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../services/api';
import { useAuth } from '../composables/useAuth';

const { user, roleName, roleDisplayName, auditType } = useAuth();

const matrix = ref({});
const allPermissions = ref([]);
const isTesting = ref(false);
const testResult = ref(null);

const rolesList = computed(() => Object.values(matrix.value || {}));

async function loadMatrix() {
  try {
    const data = await api.get('/api/rbac/matrix');
    matrix.value = data.matrix || {};
    allPermissions.value = data.all_permissions || [];
  } catch (err) {
    console.error('Failed to load RBAC matrix', err);
  }
}

async function testEndpoint(endpoint, actionName) {
  isTesting.value = true;
  testResult.value = null;
  try {
    const res = await api.get(endpoint);
    testResult.value = {
      status: 200,
      message: `${actionName}: 200 OK — ${res.message || 'Access granted!'}`,
    };
  } catch (err) {
    testResult.value = {
      status: err.status || 403,
      message: `${actionName}: ${err.status || 403} Forbidden — ${err.message || 'Access Denied.'}`,
    };
  } finally {
    isTesting.value = false;
  }
}

onMounted(() => {
  loadMatrix();
});
</script>

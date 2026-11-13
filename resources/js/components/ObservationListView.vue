<template>
  <div class="py-4 sm:py-6 max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-5">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <div class="text-[10px] font-mono uppercase tracking-wider text-brand-600 font-semibold bg-brand-50 inline-block px-2 py-0.5 rounded">
          Quality &bull; Non-Compliance Tracking (FR2004)
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-1">
          Audit Observations
        </h1>
        <p class="text-xs text-neutral-500 mt-0.5">
          Record store findings, assign required corrective actions, and track escalation deadlines.
        </p>
      </div>

      <!-- Action Button -->
      <button
        v-if="hasRole('area_treasury_supervisor') || hasRole('area_cashier_supervisor') || isAdmin"
        type="button"
        @click="openCreateModal"
        class="tap-target inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition-all shadow-xs shrink-0"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        <span>Log Observation</span>
      </button>
    </div>

    <!-- Alert / Toast Banner -->
    <div v-if="toast" class="p-3.5 rounded-xl border text-xs bg-brand-50 border-brand-200 text-brand-900 flex items-center justify-between shadow-xs">
      <span class="font-medium">{{ toast }}</span>
      <button @click="toast = ''" class="tap-target p-1 text-brand-600 hover:text-brand-900 font-bold">&times;</button>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200/80 shadow-xs flex flex-wrap items-center gap-2.5 text-xs">
      <select
        v-model="filters.status"
        @change="loadObservations"
        class="text-base sm:text-xs rounded-lg border border-neutral-200 bg-white px-3 py-2 text-neutral-800 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 tap-target sm:tap-target-none"
      >
        <option value="">All Statuses</option>
        <option value="open">Open</option>
        <option value="escalated">Escalated</option>
        <option value="resolved">Resolved</option>
        <option value="closed">Closed</option>
      </select>

      <select
        v-if="canFilterType"
        v-model="filters.audit_type"
        @change="loadObservations"
        class="text-base sm:text-xs rounded-lg border border-neutral-200 bg-white px-3 py-2 text-neutral-800 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 tap-target sm:tap-target-none"
      >
        <option value="">All Audit Types</option>
        <option value="treasury">Treasury</option>
        <option value="cashier">Cashier</option>
      </select>

      <button
        type="button"
        @click="resetFilters"
        class="text-xs font-semibold text-neutral-500 hover:text-neutral-900 px-3 py-2 tap-target flex items-center"
      >
        Reset Filters
      </button>
    </div>

    <!-- Observations Main Container: Desktop Table + Mobile Stacked Cards -->
    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-xs overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-10 text-center text-xs text-neutral-400 space-y-2">
        <div class="w-6 h-6 border-2 border-brand-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <div>Loading audit observations...</div>
      </div>

      <!-- Empty State -->
      <div v-else-if="observations.length === 0" class="p-12 text-center text-xs text-neutral-400">
        No observation records found matching active filter criteria.
      </div>

      <template v-else>
        <!-- MOBILE VIEW (< 640px): Stacked Cards Layout -->
        <div class="block sm:hidden divide-y divide-neutral-100">
          <div
            v-for="obs in observations"
            :key="obs.id"
            class="p-4 space-y-3 hover:bg-neutral-50/40 transition-colors"
          >
            <!-- Top line: Store & Status -->
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-mono uppercase text-brand-600 font-semibold bg-brand-50 px-1.5 py-0.5 rounded">
                  {{ obs.audit_type }}
                </span>
                <h3 class="text-xs font-bold text-neutral-900 mt-1">
                  {{ obs.store?.store_name }}
                </h3>
                <p class="text-[10px] font-mono text-neutral-400">
                  {{ obs.store?.store_code }} &bull; {{ obs.store?.area?.area_name }}
                </p>
              </div>
              <span :class="getStatusBadgeClass(obs.status)">
                {{ obs.status.toUpperCase() }}
              </span>
            </div>

            <!-- Issue & Action -->
            <div class="space-y-1.5 text-xs">
              <div class="p-2.5 rounded-lg bg-neutral-50 border border-neutral-100">
                <span class="text-[10px] font-mono uppercase text-neutral-400 font-semibold block mb-0.5">Issue Observed:</span>
                <p class="text-neutral-800 leading-snug">{{ obs.issue_description }}</p>
              </div>

              <div class="p-2.5 rounded-lg bg-brand-50/50 border border-brand-100/60">
                <span class="text-[10px] font-mono uppercase text-brand-700 font-semibold block mb-0.5">Required Action:</span>
                <p class="text-neutral-800 leading-snug">{{ obs.action_required }}</p>
              </div>
            </div>

            <!-- Personnel & Escalation Date -->
            <div class="grid grid-cols-2 gap-2 text-[11px] font-mono pt-1 text-neutral-500">
              <div>
                <span class="text-neutral-400 block text-[10px]">Personnel:</span>
                <span class="text-neutral-800 font-medium truncate block">{{ obs.personnel_name }}</span>
              </div>
              <div>
                <span class="text-neutral-400 block text-[10px]">Escalation Target:</span>
                <span class="text-neutral-800 font-medium block">{{ obs.target_escalation_date }}</span>
              </div>
            </div>

            <!-- Action Buttons for Mobile with 44px tap targets -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-neutral-100">
              <button
                v-if="['open', 'escalated'].includes(obs.status)"
                type="button"
                @click="openResolveModal(obs)"
                class="tap-target px-4 py-2 bg-brand-50 text-brand-700 hover:bg-brand-100 rounded-lg text-xs font-semibold transition-colors flex items-center"
              >
                Resolve
              </button>
              <button
                v-if="['resolved'].includes(obs.status) && canClose"
                type="button"
                @click="closeObservation(obs)"
                class="tap-target px-4 py-2 bg-neutral-900 text-white hover:bg-black rounded-lg text-xs font-semibold transition-colors flex items-center"
              >
                Close
              </button>
              <button
                v-if="['resolved', 'closed'].includes(obs.status)"
                type="button"
                @click="openReopenModal(obs)"
                class="tap-target px-4 py-2 border border-neutral-300 hover:bg-neutral-100 text-neutral-800 rounded-lg text-xs font-semibold transition-colors flex items-center"
              >
                Reopen
              </button>
            </div>
          </div>
        </div>

        <!-- DESKTOP VIEW (>= 640px): Standard Table -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50/80 border-b border-neutral-200/80 text-neutral-500 font-mono uppercase tracking-wider text-[10px]">
                <th class="py-3 px-4">Store Location</th>
                <th class="py-3 px-4">Audit Type</th>
                <th class="py-3 px-4">Personnel</th>
                <th class="py-3 px-4">Identified Issue</th>
                <th class="py-3 px-4">Action Required</th>
                <th class="py-3 px-4">Escalation</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-for="obs in observations" :key="obs.id" class="hover:bg-neutral-50/40 transition-colors">
                <td class="py-3 px-4">
                  <div class="font-bold text-neutral-900">{{ obs.store?.store_name }}</div>
                  <div class="text-[10px] font-mono text-neutral-400">{{ obs.store?.store_code }} &bull; {{ obs.store?.area?.area_name }}</div>
                </td>
                <td class="py-3 px-4">
                  <span class="font-mono uppercase text-[10px] px-1.5 py-0.5 rounded font-semibold bg-brand-50 text-brand-700">
                    {{ obs.audit_type }}
                  </span>
                </td>
                <td class="py-3 px-4 text-neutral-800 font-medium">
                  {{ obs.personnel_name }}
                </td>
                <td class="py-3 px-4 max-w-xs text-neutral-800">
                  <p class="line-clamp-2 leading-relaxed">{{ obs.issue_description }}</p>
                </td>
                <td class="py-3 px-4 max-w-xs text-neutral-600">
                  <p class="line-clamp-2 leading-relaxed">{{ obs.action_required }}</p>
                </td>
                <td class="py-3 px-4 font-mono text-neutral-600 text-[11px] whitespace-nowrap">
                  {{ obs.target_escalation_date }}
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <span :class="getStatusBadgeClass(obs.status)">
                    {{ obs.status.toUpperCase() }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                  <button
                    v-if="['open', 'escalated'].includes(obs.status)"
                    @click="openResolveModal(obs)"
                    class="text-brand-600 hover:text-brand-800 font-semibold px-2 py-1 rounded"
                  >
                    Resolve
                  </button>
                  <button
                    v-if="['resolved'].includes(obs.status) && canClose"
                    @click="closeObservation(obs)"
                    class="text-neutral-700 hover:text-black font-semibold px-2 py-1 rounded"
                  >
                    Close
                  </button>
                  <button
                    v-if="['resolved', 'closed'].includes(obs.status)"
                    @click="openReopenModal(obs)"
                    class="text-neutral-500 hover:text-neutral-800 font-semibold px-2 py-1 rounded"
                  >
                    Reopen
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- CREATE OBSERVATION MODAL (Full-Screen Sheet on Mobile) -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/60 backdrop-blur-xs flex sm:items-center sm:justify-center p-0 sm:p-4"
    >
      <div class="fixed inset-0 sm:inset-auto sm:relative bg-white sm:rounded-2xl max-w-lg w-full h-full sm:h-auto flex flex-col p-5 shadow-2xl border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100 shrink-0">
          <h3 class="text-base font-bold text-neutral-900">Log Audit Observation</h3>
          <button @click="showCreateModal = false" class="tap-target p-2 text-neutral-400 hover:text-neutral-700 text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitCreateObservation" class="flex-1 flex flex-col min-h-0">
          <div class="flex-1 overflow-y-auto py-3 space-y-3.5 pr-1">
            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Target Store *</label>
              <select v-model="createForm.store_id" required class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200 bg-white">
                <option value="">Select Store...</option>
                <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.store_code }} - {{ s.store_name }}</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Store Personnel (Specialist / Supervisor) *</label>
              <input v-model.trim="createForm.personnel_name" required type="text" placeholder="Full name of store personnel" class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200" />
            </div>

            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Identified Issue *</label>
              <textarea v-model.trim="createForm.issue_description" required rows="3" placeholder="Specific non-compliance or discrepancy observed..." class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200"></textarea>
            </div>

            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Action Required *</label>
              <textarea v-model.trim="createForm.action_required" required rows="3" placeholder="Mandatory corrective action to be performed by store..." class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Target Escalation Date *</label>
                <input v-model="createForm.target_escalation_date" required type="date" class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200" />
              </div>
              <div>
                <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Improvements (Optional)</label>
                <input v-model.trim="createForm.areas_for_improvement" type="text" placeholder="Recommended practice" class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200" />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100 shrink-0">
            <button type="button" @click="showCreateModal = false" class="tap-target px-4 py-2 text-neutral-600 font-medium">Cancel</button>
            <button type="submit" :disabled="isSubmitting" class="tap-target px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-bold disabled:opacity-50 shadow-xs">
              {{ isSubmitting ? 'Logging...' : 'Save Observation' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- RESOLVE MODAL (Full-Screen Sheet on Mobile) -->
    <div
      v-if="showResolveModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/60 backdrop-blur-xs flex sm:items-center sm:justify-center p-0 sm:p-4"
    >
      <div class="fixed inset-0 sm:inset-auto sm:relative bg-white sm:rounded-2xl max-w-md w-full h-full sm:h-auto flex flex-col p-5 shadow-2xl border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100 shrink-0">
          <h3 class="text-base font-bold text-neutral-900">Resolve Observation</h3>
          <button @click="showResolveModal = false" class="tap-target p-2 text-neutral-400 hover:text-neutral-700 text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitResolveObservation" class="flex-1 flex flex-col min-h-0">
          <div class="flex-1 overflow-y-auto py-3 space-y-3">
            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Resolution Details *</label>
              <textarea v-model.trim="resolveForm.resolution_details" required rows="4" placeholder="Describe how this issue was corrected..." class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200"></textarea>
            </div>
            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Date Resolved</label>
              <input v-model="resolveForm.date_resolved" type="date" class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200" />
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100 shrink-0">
            <button type="button" @click="showResolveModal = false" class="tap-target px-4 py-2 text-neutral-600 font-medium">Cancel</button>
            <button type="submit" class="tap-target px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-bold shadow-xs">Confirm Resolution</button>
          </div>
        </form>
      </div>
    </div>

    <!-- REOPEN MODAL (Full-Screen Sheet on Mobile) -->
    <div
      v-if="showReopenModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/60 backdrop-blur-xs flex sm:items-center sm:justify-center p-0 sm:p-4"
    >
      <div class="fixed inset-0 sm:inset-auto sm:relative bg-white sm:rounded-2xl max-w-md w-full h-full sm:h-auto flex flex-col p-5 shadow-2xl border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100 shrink-0">
          <h3 class="text-base font-bold text-neutral-900">Reopen Observation</h3>
          <button @click="showReopenModal = false" class="tap-target p-2 text-neutral-400 hover:text-neutral-700 text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitReopenObservation" class="flex-1 flex flex-col min-h-0">
          <div class="flex-1 overflow-y-auto py-3 space-y-3">
            <div>
              <label class="block font-semibold text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Mandatory Reopen Reason *</label>
              <textarea v-model.trim="reopenReason" required rows="4" placeholder="Provide justification for reopening..." class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200"></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100 shrink-0">
            <button type="button" @click="showReopenModal = false" class="tap-target px-4 py-2 text-neutral-600 font-medium">Cancel</button>
            <button type="submit" :disabled="!reopenReason" class="tap-target px-5 py-2.5 bg-neutral-900 hover:bg-black text-white rounded-lg font-bold disabled:opacity-50 shadow-xs">Confirm Reopen</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { api } from '../services/api';
import { useAuth } from '../composables/useAuth';

const { user, isAdmin, hasRole } = useAuth();

const observations = ref([]);
const stores = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const toast = ref('');

const filters = reactive({
  status: '',
  audit_type: '',
});

const showCreateModal = ref(false);
const createForm = reactive({
  store_id: '',
  personnel_name: '',
  issue_description: '',
  action_required: '',
  target_escalation_date: '',
  areas_for_improvement: '',
});

const showResolveModal = ref(false);
const activeObsId = ref(null);
const resolveForm = reactive({
  resolution_details: '',
  date_resolved: new Date().toISOString().split('T')[0],
});

const showReopenModal = ref(false);
const reopenReason = ref('');

const canFilterType = computed(() => isAdmin.value || user.value?.role?.name === 'manager');
const canClose = computed(() => isAdmin.value || user.value?.role?.name === 'manager');

onMounted(async () => {
  await Promise.all([loadObservations(), loadStores()]);
});

async function loadObservations() {
  isLoading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.audit_type) params.append('audit_type', filters.audit_type);

    const res = await api.get(`/observations?${params.toString()}`);
    observations.value = res.data?.data || [];
  } catch (err) {
    toast.value = err.message || 'Failed to load observations.';
  } finally {
    isLoading.value = false;
  }
}

async function loadStores() {
  try {
    const res = await api.get('/stores');
    stores.value = res.data || [];
  } catch {}
}

function resetFilters() {
  filters.status = '';
  filters.audit_type = '';
  loadObservations();
}

function openCreateModal() {
  createForm.store_id = '';
  createForm.personnel_name = '';
  createForm.issue_description = '';
  createForm.action_required = '';
  createForm.target_escalation_date = '';
  createForm.areas_for_improvement = '';
  showCreateModal.value = true;
}

async function submitCreateObservation() {
  isSubmitting.value = true;
  try {
    // Generate an Idempotency-Key for double submit protection
    const idempotencyKey = 'obs_' + (window.crypto?.randomUUID ? crypto.randomUUID() : Date.now() + Math.random().toString());
    await api.post('/observations', { ...createForm, idempotency_key: idempotencyKey }, {
      headers: { 'X-Idempotency-Key': idempotencyKey }
    });
    showCreateModal.value = false;
    toast.value = 'Observation logged successfully.';
    await loadObservations();
  } catch (err) {
    alert(err.message || 'Failed to create observation.');
  } finally {
    isSubmitting.value = false;
  }
}

function openResolveModal(obs) {
  activeObsId.value = obs.id;
  resolveForm.resolution_details = '';
  resolveForm.date_resolved = new Date().toISOString().split('T')[0];
  showResolveModal.value = true;
}

async function submitResolveObservation() {
  if (!activeObsId.value) return;
  try {
    await api.post(`/observations/${activeObsId.value}/resolve`, resolveForm);
    showResolveModal.value = false;
    toast.value = 'Observation marked as resolved.';
    await loadObservations();
  } catch (err) {
    alert(err.message || 'Failed to resolve observation.');
  }
}

async function closeObservation(obs) {
  if (!confirm('Close this resolved observation? Closed observations are finalized.')) return;
  try {
    await api.post(`/observations/${obs.id}/close`);
    toast.value = 'Observation closed.';
    await loadObservations();
  } catch (err) {
    alert(err.message || 'Failed to close observation.');
  }
}

function openReopenModal(obs) {
  activeObsId.value = obs.id;
  reopenReason.value = '';
  showReopenModal.value = true;
}

async function submitReopenObservation() {
  if (!activeObsId.value || !reopenReason.value) return;
  try {
    await api.post(`/observations/${activeObsId.value}/reopen`, {
      reopen_reason: reopenReason.value,
    });
    showReopenModal.value = false;
    toast.value = 'Observation reopened.';
    await loadObservations();
  } catch (err) {
    alert(err.message || 'Failed to reopen observation.');
  }
}

function getStatusBadgeClass(st) {
  if (st === 'open') return 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-50 text-amber-700 border border-amber-200';
  if (st === 'escalated') return 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-50 text-rose-700 border border-rose-200';
  if (st === 'resolved') return 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
  if (st === 'closed') return 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-neutral-100 text-neutral-700 border border-neutral-300';
  return 'px-2 py-0.5 rounded text-[10px] font-mono bg-neutral-100 text-neutral-800';
}
</script>

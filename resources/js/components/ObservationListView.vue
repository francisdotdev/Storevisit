<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Audit Findings &bull; Action Items
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            Audit Observations
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Log, track, escalate, and resolve non-compliance issues across retail stores.
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <button
            type="button"
            @click="loadObservations"
            class="px-3 py-1.5 rounded-md border border-neutral-200 bg-white text-xs font-medium text-neutral-700 hover:bg-neutral-50"
          >
            Refresh
          </button>
          <button
            type="button"
            @click="openCreateModal"
            class="px-3.5 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium"
          >
            Log Observation
          </button>
        </div>
      </div>

      <!-- Toast Alert -->
      <div v-if="toast" class="p-3 rounded-lg border text-xs bg-neutral-900 text-white flex items-center justify-between">
        <span>{{ toast }}</span>
        <button @click="toast = ''" class="text-neutral-400 hover:text-white">&times;</button>
      </div>

      <!-- Filters & Table -->
      <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-neutral-100 flex flex-wrap items-center gap-2">
          <!-- Status Filter -->
          <select
            v-model="filters.status"
            @change="loadObservations"
            class="text-xs rounded-md border border-neutral-200 bg-white px-2.5 py-1.5"
          >
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="escalated">Escalated</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>

          <!-- Audit Type Filter -->
          <select
            v-if="canFilterType"
            v-model="filters.audit_type"
            @change="loadObservations"
            class="text-xs rounded-md border border-neutral-200 bg-white px-2.5 py-1.5"
          >
            <option value="">All Audit Types</option>
            <option value="treasury">Treasury</option>
            <option value="cashier">Cashier</option>
          </select>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase text-[10px]">
                <th class="py-2.5 px-4">Store</th>
                <th class="py-2.5 px-4">Type</th>
                <th class="py-2.5 px-4">Personnel</th>
                <th class="py-2.5 px-4">Identified Issue</th>
                <th class="py-2.5 px-4">Improvements / Action</th>
                <th class="py-2.5 px-4">Target Date</th>
                <th class="py-2.5 px-4">Status</th>
                <th class="py-2.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-if="isLoading" class="text-center">
                <td colspan="8" class="py-8 text-neutral-400">Loading observations...</td>
              </tr>
              <tr v-else-if="observations.length === 0" class="text-center">
                <td colspan="8" class="py-8 text-neutral-400">No observations found.</td>
              </tr>
              <tr v-for="obs in observations" :key="obs.id" class="hover:bg-neutral-50/50">
                <td class="py-3 px-4">
                  <div class="font-medium text-neutral-900">{{ obs.store?.store_name }}</div>
                  <div class="text-[10px] font-mono text-neutral-400">{{ obs.store?.store_code }} &bull; {{ obs.store?.area?.area_name }}</div>
                </td>
                <td class="py-3 px-4 font-mono uppercase text-[11px] text-neutral-600">
                  {{ obs.audit_type }}
                </td>
                <td class="py-3 px-4 text-neutral-800 font-medium">
                  {{ obs.personnel_name }}
                </td>
                <td class="py-3 px-4 max-w-xs text-neutral-800">
                  <p class="line-clamp-2">{{ obs.issue_description }}</p>
                </td>
                <td class="py-3 px-4 max-w-xs text-neutral-600">
                  <p class="line-clamp-2">{{ obs.areas_for_improvement || obs.action_required }}</p>
                </td>
                <td class="py-3 px-4 font-mono text-neutral-600 text-[11px]">
                  {{ obs.target_escalation_date }}
                </td>
                <td class="py-3 px-4">
                  <span :class="getStatusBadgeClass(obs.status)">
                    {{ obs.status.toUpperCase() }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right space-x-2">
                  <!-- Resolve -->
                  <button
                    v-if="['open', 'escalated'].includes(obs.status)"
                    @click="openResolveModal(obs)"
                    class="text-neutral-600 hover:text-neutral-900 font-medium"
                  >
                    Resolve
                  </button>

                  <!-- Close (Managers/Admins) -->
                  <button
                    v-if="['resolved'].includes(obs.status) && canClose"
                    @click="closeObservation(obs)"
                    class="text-neutral-600 hover:text-neutral-900 font-medium"
                  >
                    Close
                  </button>

                  <!-- Reopen -->
                  <button
                    v-if="['resolved', 'closed'].includes(obs.status)"
                    @click="openReopenModal(obs)"
                    class="text-neutral-600 hover:text-neutral-900 font-medium"
                  >
                    Reopen
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CREATE OBSERVATION MODAL -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-lg border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">Log Audit Observation</h3>
          <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <form @submit.prevent="submitCreateObservation" class="space-y-3 mt-3">
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Target Store *</label>
            <select v-model="createForm.store_id" required class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white">
              <option value="">Select Store...</option>
              <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.store_code }} - {{ s.store_name }}</option>
            </select>
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Store Personnel (Specialist / Supervisor) *</label>
            <input v-model.trim="createForm.personnel_name" required type="text" placeholder="Full name of store personnel" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Identified Issue *</label>
            <textarea v-model.trim="createForm.issue_description" required rows="2" placeholder="Specific non-compliance or discrepancy observed..." class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200"></textarea>
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Action Required *</label>
            <textarea v-model.trim="createForm.action_required" required rows="2" placeholder="Mandatory corrective action to be performed by store..." class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Target Escalation Date *</label>
              <input v-model="createForm.target_escalation_date" required type="date" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Improvements (Optional)</label>
              <input v-model.trim="createForm.areas_for_improvement" type="text" placeholder="Recommended practice" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button type="button" @click="showCreateModal = false" class="px-3 py-1.5 text-neutral-600">Cancel</button>
            <button type="submit" :disabled="isSubmitting" class="px-3.5 py-1.5 bg-neutral-900 text-white rounded-md font-medium disabled:opacity-50">
              {{ isSubmitting ? 'Logging...' : 'Save Observation' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- RESOLVE MODAL -->
    <div v-if="showResolveModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-neutral-200 text-xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">Resolve Observation</h3>
          <button @click="showResolveModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>
        <form @submit.prevent="submitResolveObservation" class="space-y-3">
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Resolution Details *</label>
            <textarea v-model.trim="resolveForm.resolution_details" required rows="3" placeholder="Describe how this issue was corrected..." class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200"></textarea>
          </div>
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Date Resolved</label>
            <input v-model="resolveForm.date_resolved" type="date" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
          </div>
          <div class="flex items-center justify-end space-x-2 pt-2 border-t border-neutral-100">
            <button type="button" @click="showResolveModal = false" class="px-3 py-1.5 text-neutral-600">Cancel</button>
            <button type="submit" class="px-3.5 py-1.5 bg-neutral-900 text-white rounded-md font-medium">Confirm Resolution</button>
          </div>
        </form>
      </div>
    </div>

    <!-- REOPEN MODAL -->
    <div v-if="showReopenModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-neutral-200 text-xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">Reopen Observation</h3>
          <button @click="showReopenModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>
        <form @submit.prevent="submitReopenObservation" class="space-y-3">
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Mandatory Reopen Reason *</label>
            <textarea v-model.trim="reopenReason" required rows="3" placeholder="Provide justification for reopening..." class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200"></textarea>
          </div>
          <div class="flex items-center justify-end space-x-2 pt-2 border-t border-neutral-100">
            <button type="button" @click="showReopenModal = false" class="px-3 py-1.5 text-neutral-600">Cancel</button>
            <button type="submit" :disabled="!reopenReason" class="px-3.5 py-1.5 bg-neutral-900 text-white rounded-md font-medium disabled:opacity-50">Confirm Reopen</button>
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
    console.error('Failed to load observations:', err);
  } finally {
    isLoading.value = false;
  }
}

async function loadStores() {
  try {
    const res = await api.get('/stores');
    stores.value = res.data || [];
  } catch (err) {
    console.error('Failed to load stores:', err);
  }
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
    await api.post('/observations', createForm);
    toast.value = 'Observation logged successfully.';
    showCreateModal.value = false;
    await loadObservations();
  } catch (err) {
    toast.value = err.message || 'Failed to log observation.';
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
  try {
    await api.post(`/observations/${activeObsId.value}/resolve`, resolveForm);
    toast.value = 'Observation marked as resolved.';
    showResolveModal.value = false;
    await loadObservations();
  } catch (err) {
    toast.value = err.message || 'Failed to resolve.';
  }
}

async function closeObservation(obs) {
  if (!confirm('Close this observation permanently?')) return;
  try {
    await api.post(`/observations/${obs.id}/close`);
    toast.value = 'Observation closed.';
    await loadObservations();
  } catch (err) {
    toast.value = err.message || 'Failed to close.';
  }
}

function openReopenModal(obs) {
  activeObsId.value = obs.id;
  reopenReason.value = '';
  showReopenModal.value = true;
}

async function submitReopenObservation() {
  try {
    await api.post(`/observations/${activeObsId.value}/reopen`, {
      reopen_reason: reopenReason.value,
    });
    toast.value = 'Observation reopened.';
    showReopenModal.value = false;
    await loadObservations();
  } catch (err) {
    toast.value = err.message || 'Failed to reopen.';
  }
}

function getStatusBadgeClass(st) {
  if (st === 'open') return 'px-2 py-0.5 rounded text-[10px] font-mono bg-neutral-100 text-neutral-800 border border-neutral-200';
  if (st === 'escalated') return 'px-2 py-0.5 rounded text-[10px] font-mono bg-amber-50 text-amber-800 border border-amber-200';
  if (st === 'resolved') return 'px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200';
  return 'px-2 py-0.5 rounded text-[10px] font-mono bg-neutral-900 text-white';
}
</script>


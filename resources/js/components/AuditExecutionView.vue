<template>
  <div class="py-4 sm:py-8 max-w-4xl mx-auto px-3 sm:px-6 space-y-4">
    <!-- Back to Calendar -->
    <div class="flex items-center justify-between">
      <button
        type="button"
        @click="$emit('navigate', 'calendar')"
        class="inline-flex items-center space-x-1.5 text-xs text-neutral-500 hover:text-neutral-900 transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Back to Calendar</span>
      </button>

      <!-- Status Badge -->
      <div v-if="visitAudit" class="flex items-center space-x-2">
        <span :class="statusBadgeClasses">
          {{ visitAudit.status.toUpperCase() }}
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white p-12 rounded-lg border border-neutral-200 text-center text-xs text-neutral-400">
      Loading audit session...
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-lg text-xs space-y-2">
      <p class="font-semibold">{{ errorMessage }}</p>
      <button @click="$emit('navigate', 'calendar')" class="underline">Return to calendar</button>
    </div>

    <!-- Execution Workspace -->
    <div v-else-if="visitAudit" class="space-y-4">
      <!-- Session Header Card -->
      <div class="bg-white p-4 sm:p-5 rounded-lg border border-neutral-200 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-neutral-100 pb-3">
          <div>
            <span class="text-[10px] font-mono uppercase text-neutral-400">
              {{ visitAudit.schedule?.store?.area?.area_name }} &bull; {{ visitAudit.audit_type.toUpperCase() }} AUDIT
            </span>
            <h1 class="text-base sm:text-lg font-semibold text-neutral-900 mt-0.5">
              {{ visitAudit.schedule?.store?.store_name }}
            </h1>
            <p class="text-[11px] font-mono text-neutral-500">
              Code: {{ visitAudit.schedule?.store?.store_code }} | Scheduled: {{ visitAudit.schedule?.scheduled_date }}
            </p>
          </div>

          <!-- Live Compliance Score Card -->
          <div class="bg-neutral-50 border border-neutral-200 rounded-md p-3 text-right sm:min-w-44">
            <span class="text-[10px] font-mono uppercase text-neutral-400">Live Compliance</span>
            <div class="text-xl sm:text-2xl font-bold font-mono text-neutral-900 mt-0.5">
              {{ liveScorePercentage.toFixed(2) }}%
            </div>
            <div class="text-[10px] font-mono text-neutral-500 mt-0.5">
              {{ liveEarnedWeight.toFixed(1) }} / {{ livePossibleWeight.toFixed(1) }} pts
              <span v-if="visitAudit.rating?.grade" class="ml-1 font-semibold text-neutral-900">({{ visitAudit.rating.grade }})</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
          <div>
            <span class="text-neutral-400 block font-mono">Supervisor:</span>
            <span class="font-medium text-neutral-800">{{ visitAudit.supervisor?.name || 'Assigned Supervisor' }}</span>
          </div>
          <div>
            <span class="text-neutral-400 block font-mono">Started At:</span>
            <span class="font-medium text-neutral-800">{{ formatDateTime(visitAudit.actual_started_at) }}</span>
          </div>
          <div>
            <span class="text-neutral-400 block font-mono">Submitted At:</span>
            <span class="font-medium text-neutral-800">{{ formatDateTime(visitAudit.submitted_at) || '-' }}</span>
          </div>
          <div>
            <span class="text-neutral-400 block font-mono">Progress:</span>
            <span class="font-medium text-neutral-800 font-mono">{{ answeredCount }} / {{ visitAudit.answers.length }} Answered</span>
          </div>
        </div>

        <!-- Reopened Alert -->
        <div v-if="visitAudit.status === 'reopened'" class="bg-amber-50 border border-amber-200 text-amber-900 p-2.5 rounded text-xs">
          <span class="font-semibold">Reopened by {{ visitAudit.reopened_by }}</span>:
          <span class="italic">"{{ visitAudit.reopen_reason }}"</span>
        </div>
      </div>

      <!-- Toast Alert -->
      <div v-if="toast" class="p-3 rounded-lg border text-xs bg-neutral-900 text-white flex items-center justify-between">
        <span>{{ toast }}</span>
        <button @click="toast = ''" class="text-neutral-400 hover:text-white">&times;</button>
      </div>

      <!-- Checklist Categories & Items -->
      <div class="space-y-4">
        <div
          v-for="cat in groupedAnswers"
          :key="cat.name"
          class="bg-white rounded-lg border border-neutral-200 overflow-hidden shadow-xs"
        >
          <!-- Category Header -->
          <div class="bg-neutral-50/80 px-4 py-2.5 border-b border-neutral-200 flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-900">{{ cat.name }}</h2>
            <span class="text-[11px] font-mono text-neutral-500">{{ cat.items.length }} criteria</span>
          </div>

          <!-- Items in Category -->
          <div class="divide-y divide-neutral-100">
            <div
              v-for="item in cat.items"
              :key="item.id"
              class="p-4 space-y-3 hover:bg-neutral-50/30 transition-colors"
            >
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <div class="space-y-1">
                  <p class="text-xs font-medium text-neutral-900 leading-snug">{{ item.activity }}</p>
                  <p v-if="item.sub_activity" class="text-[11px] text-neutral-500 leading-relaxed">{{ item.sub_activity }}</p>
                </div>
                <div class="shrink-0 flex items-center space-x-1 font-mono text-[11px] text-neutral-500">
                  <span>Weight:</span>
                  <span class="font-semibold text-neutral-900">{{ Number(item.weight).toFixed(1) }} pts</span>
                </div>
              </div>

              <!-- Answer Choices (YES / NO / N/A) -->
              <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div class="inline-flex rounded-md border border-neutral-200 p-0.5 bg-neutral-50 text-xs">
                  <button
                    type="button"
                    :disabled="isReadonly"
                    @click="setAnswer(item, 'yes')"
                    :class="[
                      'px-3 py-1 rounded font-medium transition-all text-xs',
                      item.answer === 'yes'
                        ? 'bg-emerald-600 text-white shadow-xs font-semibold'
                        : 'text-neutral-600 hover:text-neutral-900'
                    ]"
                  >
                    YES
                  </button>
                  <button
                    type="button"
                    :disabled="isReadonly"
                    @click="setAnswer(item, 'no')"
                    :class="[
                      'px-3 py-1 rounded font-medium transition-all text-xs',
                      item.answer === 'no'
                        ? 'bg-rose-600 text-white shadow-xs font-semibold'
                        : 'text-neutral-600 hover:text-neutral-900'
                    ]"
                  >
                    NO
                  </button>
                  <button
                    type="button"
                    :disabled="isReadonly"
                    @click="setAnswer(item, 'n_a')"
                    :class="[
                      'px-3 py-1 rounded font-medium transition-all text-xs',
                      item.answer === 'n_a'
                        ? 'bg-neutral-800 text-white shadow-xs font-semibold'
                        : 'text-neutral-600 hover:text-neutral-900'
                    ]"
                  >
                    N/A
                  </button>
                </div>

                <!-- Input for Personnel in charge -->
                <div class="w-full sm:w-64">
                  <input
                    v-model.trim="item.personnel_in_charge"
                    :disabled="isReadonly"
                    type="text"
                    placeholder="Personnel In-Charge..."
                    class="w-full text-[11px] px-2 py-1 rounded border border-neutral-200 focus:border-neutral-900 focus:ring-0 disabled:bg-neutral-100"
                  />
                </div>
              </div>

              <!-- Remarks Input -->
              <div>
                <input
                  v-model.trim="item.remarks"
                  :disabled="isReadonly"
                  type="text"
                  placeholder="Notes, findings or specific remarks..."
                  class="w-full text-[11px] px-2 py-1 rounded border border-neutral-200 focus:border-neutral-900 focus:ring-0 disabled:bg-neutral-100"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Remarks -->
      <div class="bg-white p-4 rounded-lg border border-neutral-200 space-y-2">
        <label class="block text-xs font-semibold text-neutral-900">
          Executive Summary Remarks &amp; Overall Audit Notes
        </label>
        <textarea
          v-model.trim="summaryRemarks"
          :disabled="isReadonly"
          rows="3"
          placeholder="Summarize the store audit findings, store readiness, and key takeaways..."
          class="w-full text-xs p-2.5 rounded-md border border-neutral-200 focus:border-neutral-900 focus:ring-0 disabled:bg-neutral-100"
        ></textarea>
      </div>

      <!-- Sticky Action Bar -->
      <div class="sticky bottom-4 z-20 bg-white/95 backdrop-blur-sm p-3.5 rounded-lg border border-neutral-200 shadow-md flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center space-x-2 text-xs">
          <span class="font-mono text-neutral-500">Unanswered:</span>
          <span :class="['font-mono font-semibold', unansweredCount === 0 ? 'text-emerald-600' : 'text-amber-600']">
            {{ unansweredCount }}
          </span>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Reopen Button (Managers / Admins on submitted audits) -->
          <button
            v-if="visitAudit.status === 'submitted' && canReopen"
            type="button"
            @click="showReopenModal = true"
            class="px-3.5 py-1.5 text-xs font-medium border border-neutral-300 hover:bg-neutral-100 rounded-md transition-colors text-neutral-800"
          >
            Reopen Audit
          </button>

          <!-- Save Draft Button -->
          <button
            v-if="!isReadonly"
            type="button"
            @click="saveDraft"
            :disabled="isSaving"
            class="px-3.5 py-1.5 text-xs font-medium border border-neutral-300 hover:bg-neutral-100 rounded-md transition-colors text-neutral-800 disabled:opacity-50"
          >
            {{ isSaving ? 'Saving...' : 'Save Draft' }}
          </button>

          <!-- Submit Audit Button -->
          <button
            v-if="!isReadonly"
            type="button"
            @click="submitAudit"
            :disabled="isSubmitting || unansweredCount > 0"
            class="px-4 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors disabled:opacity-50"
          >
            {{ isSubmitting ? 'Submitting...' : 'Submit Final Audit' }}
          </button>
        </div>
      </div>
    </div>

    <!-- REOPEN MODAL -->
    <div v-if="showReopenModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-neutral-200 text-xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">Reopen Submitted Audit</h3>
          <button @click="showReopenModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>
        <p class="text-neutral-600">
          Reopening this audit will return it to editable in-progress status. A mandatory justification reason is required for the audit trail.
        </p>
        <textarea
          v-model.trim="reopenReason"
          required
          rows="3"
          placeholder="Specify why this audit is being reopened for corrections..."
          class="w-full text-xs p-2.5 rounded-md border border-neutral-200"
        ></textarea>
        <div class="flex items-center justify-end space-x-2 pt-2 border-t border-neutral-100">
          <button type="button" @click="showReopenModal = false" class="px-3 py-1.5 text-neutral-600">Cancel</button>
          <button
            type="button"
            @click="confirmReopen"
            :disabled="!reopenReason || isReopening"
            class="px-3.5 py-1.5 bg-neutral-900 text-white rounded-md font-medium disabled:opacity-50"
          >
            {{ isReopening ? 'Reopening...' : 'Confirm Reopen' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../services/api';
import { useAuth } from '../composables/useAuth';

const props = defineProps({
  scheduleId: {
    type: [Number, String],
    required: true,
  },
});

const emit = defineEmits(['navigate']);
const { user, isAdmin, hasRole } = useAuth();

const visitAudit = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isSubmitting = ref(false);
const isReopening = ref(false);
const errorMessage = ref('');
const toast = ref('');
const summaryRemarks = ref('');
const showReopenModal = ref(false);
const reopenReason = ref('');

onMounted(() => {
  initAuditSession();
});

async function initAuditSession() {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const res = await api.post(`/visit-audits/start/${props.scheduleId}`);
    visitAudit.value = res.data;
    summaryRemarks.value = res.data.summary_remarks || '';
  } catch (err) {
    errorMessage.value = err.message || 'Failed to start audit session.';
  } finally {
    isLoading.value = false;
  }
}

const isReadonly = computed(() => {
  return visitAudit.value?.status === 'submitted';
});

const canReopen = computed(() => {
  if (isAdmin.value || user.value?.role?.name === 'manager') return true;
  if (visitAudit.value?.audit_type === 'treasury' && hasRole('asst_treasury_manager')) return true;
  if (visitAudit.value?.audit_type === 'cashier' && hasRole('asst_cashier_manager')) return true;
  return false;
});

const statusBadgeClasses = computed(() => {
  const st = visitAudit.value?.status;
  if (st === 'submitted') return 'px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-neutral-900 text-white';
  if (st === 'reopened') return 'px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-amber-100 text-amber-800 border border-amber-300';
  return 'px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-neutral-100 text-neutral-800 border border-neutral-300';
});

const groupedAnswers = computed(() => {
  if (!visitAudit.value?.answers) return [];
  const groups = {};
  for (const it of visitAudit.value.answers) {
    const cat = it.category || 'General';
    if (!groups[cat]) {
      groups[cat] = { name: cat, items: [] };
    }
    groups[cat].items.push(it);
  }
  return Object.values(groups);
});

const answeredCount = computed(() => {
  if (!visitAudit.value?.answers) return 0;
  return visitAudit.value.answers.filter(it => it.answer !== null && it.answer !== '').length;
});

const unansweredCount = computed(() => {
  if (!visitAudit.value?.answers) return 0;
  return visitAudit.value.answers.length - answeredCount.value;
});

const livePossibleWeight = computed(() => {
  if (!visitAudit.value?.answers) return 0;
  return visitAudit.value.answers.reduce((acc, it) => {
    if (it.answer === 'n_a') return acc;
    return acc + Number(it.weight || 0);
  }, 0);
});

const liveEarnedWeight = computed(() => {
  if (!visitAudit.value?.answers) return 0;
  return visitAudit.value.answers.reduce((acc, it) => {
    if (it.answer === 'yes') return acc + Number(it.weight || 0);
    return acc;
  }, 0);
});

const liveScorePercentage = computed(() => {
  if (livePossibleWeight.value === 0) return 0.0;
  return (liveEarnedWeight.value / livePossibleWeight.value) * 100;
});

function setAnswer(item, val) {
  if (isReadonly.value) return;
  item.answer = val;
}

async function saveDraft() {
  if (isReadonly.value) return;
  isSaving.value = true;
  try {
    const payload = {
      summary_remarks: summaryRemarks.value,
      answers: visitAudit.value.answers.map(a => ({
        id: a.id,
        answer: a.answer,
        remarks: a.remarks,
        personnel_in_charge: a.personnel_in_charge,
      })),
    };
    const res = await api.post(`/visit-audits/${visitAudit.value.id}/draft`, payload);
    visitAudit.value = res.data;
    toast.value = 'Draft saved successfully.';
  } catch (err) {
    toast.value = err.message || 'Failed to save draft.';
  } finally {
    isSaving.value = false;
  }
}

async function submitAudit() {
  if (!confirm('Submit final audit? Once submitted, answers can only be changed if reopened by a manager.')) {
    return;
  }
  isSubmitting.value = true;
  try {
    // First save answers
    await saveDraft();
    const res = await api.post(`/visit-audits/${visitAudit.value.id}/submit`, {
      summary_remarks: summaryRemarks.value,
    });
    visitAudit.value = res.data;
    toast.value = 'Audit submitted successfully!';
  } catch (err) {
    toast.value = err.message || 'Submission failed.';
  } finally {
    isSubmitting.value = false;
  }
}

async function confirmReopen() {
  isReopening.value = true;
  try {
    const res = await api.post(`/visit-audits/${visitAudit.value.id}/reopen`, {
      reopen_reason: reopenReason.value,
    });
    visitAudit.value = res.data;
    showReopenModal.value = false;
    reopenReason.value = '';
    toast.value = 'Audit has been reopened for editing.';
  } catch (err) {
    toast.value = err.message || 'Failed to reopen.';
  } finally {
    isReopening.value = false;
  }
}

function formatDateTime(dt) {
  if (!dt) return null;
  return new Date(dt).toLocaleString();
}
</script>


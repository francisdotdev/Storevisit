<template>
  <div class="py-4 sm:py-6 max-w-4xl mx-auto px-3 sm:px-6 space-y-4">
    <!-- Back to Calendar & Top Status Bar -->
    <div class="flex items-center justify-between gap-2">
      <button
        type="button"
        @click="$emit('navigate', 'calendar')"
        class="inline-flex items-center space-x-2 text-xs font-medium text-neutral-600 hover:text-neutral-900 transition-colors tap-target px-2 -ml-2 rounded-lg"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Back to Calendar</span>
      </button>

      <!-- Status & Save State Badge -->
      <div v-if="visitAudit" class="flex items-center space-x-2">
        <!-- Save state indicator -->
        <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono border"
          :class="saveStateClasses"
        >
          <span :class="saveDotClasses"></span>
          <span>{{ saveStateLabel }}</span>
        </div>

        <span :class="statusBadgeClasses">
          {{ visitAudit.status.toUpperCase() }}
        </span>
      </div>
    </div>

    <!-- Network Failure / Unsaved Retry Banner -->
    <div
      v-if="hasUnsavedChanges && saveState === 'error'"
      class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs"
    >
      <div class="flex items-center space-x-2">
        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <span class="font-semibold block">Unsaved changes kept in memory.</span>
          <span class="text-rose-700 text-[11px]">Network save failed. Your answers are preserved. Tap retry to save.</span>
        </div>
      </div>
      <button
        type="button"
        @click="saveDraft"
        :disabled="isSaving"
        class="tap-target px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs transition-colors shrink-0 flex items-center justify-center space-x-1"
      >
        <span>{{ isSaving ? 'Retrying...' : 'Retry Save Now' }}</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white p-12 rounded-xl border border-neutral-200/80 text-center text-xs text-neutral-400 space-y-2">
      <div class="w-8 h-8 rounded-full border-2 border-brand-600 border-t-transparent animate-spin mx-auto"></div>
      <div>Loading audit session...</div>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="bg-rose-50 border border-rose-200 text-rose-800 p-5 rounded-xl text-xs space-y-3">
      <p class="font-semibold text-sm">{{ errorMessage }}</p>
      <button @click="$emit('navigate', 'calendar')" class="tap-target px-3 py-1.5 bg-rose-700 text-white rounded-md">Return to calendar</button>
    </div>

    <!-- Execution Workspace -->
    <div v-else-if="visitAudit" class="space-y-4">
      <!-- Session Header Card -->
      <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200/80 space-y-3 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-neutral-100 pb-3">
          <div>
            <div class="inline-flex items-center space-x-1.5 text-[10px] font-mono uppercase text-brand-600 font-semibold bg-brand-50 px-2 py-0.5 rounded">
              <span>{{ visitAudit.schedule?.store?.area?.area_name }}</span>
              <span>&bull;</span>
              <span>{{ visitAudit.audit_type.toUpperCase() }} AUDIT</span>
            </div>
            <h1 class="text-base sm:text-lg font-bold text-neutral-900 mt-1">
              {{ visitAudit.schedule?.store?.store_name }}
            </h1>
            <p class="text-[11px] font-mono text-neutral-500">
              Store Code: {{ visitAudit.schedule?.store?.store_code }} &bull; Scheduled: {{ visitAudit.schedule?.scheduled_date }}
            </p>
          </div>

          <!-- Live Compliance Score Card -->
          <div class="bg-brand-50/70 border border-brand-100 rounded-xl p-3 sm:text-right min-w-44">
            <span class="text-[10px] font-mono uppercase text-brand-700 font-semibold">Live Compliance</span>
            <div class="text-xl sm:text-2xl font-bold font-mono text-brand-900 mt-0.5">
              {{ liveScorePercentage.toFixed(2) }}%
            </div>
            <div class="text-[10px] font-mono text-brand-700 mt-0.5">
              {{ liveEarnedWeight.toFixed(1) }} / {{ livePossibleWeight.toFixed(1) }} pts
              <span v-if="visitAudit.rating?.grade" class="ml-1 font-semibold">({{ visitAudit.rating.grade }})</span>
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
            <span class="font-medium text-neutral-800 font-mono">{{ answeredCount }} / {{ totalCount }} Answered</span>
          </div>
        </div>

        <!-- Reopened Alert -->
        <div v-if="visitAudit.status === 'reopened'" class="bg-amber-50 border border-amber-200 text-amber-900 p-3 rounded-lg text-xs">
          <span class="font-semibold">Reopened by {{ visitAudit.reopened_by }}</span>:
          <span class="italic">"{{ visitAudit.reopen_reason }}"</span>
        </div>
      </div>

      <!-- Checklist Categories & Items -->
      <div class="space-y-4">
        <div
          v-for="cat in groupedAnswers"
          :key="cat.name"
          class="bg-white rounded-xl border border-neutral-200/80 overflow-hidden shadow-xs"
        >
          <!-- Category Header -->
          <div class="bg-neutral-50/80 px-4 py-3 border-b border-neutral-200/80 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-900">{{ cat.name }}</h2>
            <span class="text-[11px] font-mono text-neutral-500 font-medium">{{ cat.items.length }} criteria</span>
          </div>

          <!-- Items in Category -->
          <div class="divide-y divide-neutral-100">
            <div
              v-for="item in cat.items"
              :key="item.id"
              class="p-4 space-y-3.5 hover:bg-neutral-50/30 transition-colors"
            >
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <div class="space-y-1">
                  <p class="text-xs font-semibold text-neutral-900 leading-snug">{{ item.activity }}</p>
                  <p v-if="item.sub_activity" class="text-xs text-neutral-500 leading-relaxed">{{ item.sub_activity }}</p>
                </div>
                <div class="shrink-0 flex items-center space-x-1 font-mono text-[11px] text-neutral-500">
                  <span>Weight:</span>
                  <span class="font-bold text-neutral-900">{{ Number(item.weight).toFixed(1) }} pts</span>
                </div>
              </div>

              <!-- Answer Choices (YES / NO / N/A) with 44px touch targets for mobile -->
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                <!-- 3 Tap Target Choice Buttons -->
                <div class="grid grid-cols-3 sm:inline-flex rounded-xl border border-neutral-200 p-1 bg-neutral-100/70 gap-1 sm:gap-1.5">
                  <button
                    type="button"
                    :disabled="isReadonly"
                    @click="setAnswer(item, 'yes')"
                    :class="[
                      'tap-target px-4 py-2.5 rounded-lg font-bold text-xs transition-all flex items-center justify-center space-x-1',
                      item.answer === 'yes'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : 'text-neutral-700 hover:text-neutral-900 hover:bg-white/80'
                    ]"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>YES</span>
                  </button>

                  <button
                    type="button"
                    :disabled="isReadonly"
                    @click="setAnswer(item, 'no')"
                    :class="[
                      'tap-target px-4 py-2.5 rounded-lg font-bold text-xs transition-all flex items-center justify-center space-x-1',
                      item.answer === 'no'
                        ? 'bg-rose-600 text-white shadow-xs'
                        : 'text-neutral-700 hover:text-neutral-900 hover:bg-white/80'
                    ]"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>NO</span>
                  </button>

                  <button
                    type="button"
                    :disabled="isReadonly"
                    @click="setAnswer(item, 'n_a')"
                    :class="[
                      'tap-target px-4 py-2.5 rounded-lg font-bold text-xs transition-all flex items-center justify-center space-x-1',
                      item.answer === 'n_a'
                        ? 'bg-neutral-800 text-white shadow-xs'
                        : 'text-neutral-700 hover:text-neutral-900 hover:bg-white/80'
                    ]"
                  >
                    <span>N/A</span>
                  </button>
                </div>

                <!-- Input for Personnel in charge (16px font to stop iOS zoom) -->
                <div class="w-full sm:w-64">
                  <label class="block sm:hidden text-[10px] font-mono uppercase text-neutral-400 mb-1">Personnel In-Charge</label>
                  <input
                    v-model.trim="item.personnel_in_charge"
                    @input="onFieldChange"
                    :disabled="isReadonly"
                    type="text"
                    placeholder="Personnel In-Charge..."
                    class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 disabled:bg-neutral-100"
                  />
                </div>
              </div>

              <!-- Remarks Input (16px font on mobile) -->
              <div>
                <label class="block sm:hidden text-[10px] font-mono uppercase text-neutral-400 mb-1">Findings / Remarks</label>
                <input
                  v-model.trim="item.remarks"
                  @input="onFieldChange"
                  :disabled="isReadonly"
                  type="text"
                  placeholder="Notes, findings or specific remarks..."
                  class="w-full text-base sm:text-xs px-3 py-2 rounded-lg border border-neutral-200 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 disabled:bg-neutral-100"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Remarks -->
      <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200/80 space-y-2 shadow-xs">
        <label class="block text-xs font-bold text-neutral-900 uppercase tracking-wider">
          Executive Summary Remarks &amp; Overall Audit Notes
        </label>
        <textarea
          v-model.trim="summaryRemarks"
          @input="onFieldChange"
          :disabled="isReadonly"
          rows="3"
          placeholder="Summarize the store audit findings, store readiness, and key takeaways..."
          class="w-full text-base sm:text-xs p-3 rounded-lg border border-neutral-200 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 disabled:bg-neutral-100"
        ></textarea>
      </div>

      <!-- Sticky Action Bar for Mobile & Field (Fixed Bottom) -->
      <div class="sticky bottom-3 z-30 bg-white/95 backdrop-blur-md p-3.5 sm:p-4 rounded-xl border border-neutral-200/90 shadow-xl space-y-2">
        <!-- Progress Bar & Indicator -->
        <div class="flex items-center justify-between text-xs">
          <div class="flex items-center space-x-2">
            <span class="font-mono text-neutral-500 font-medium">Answered:</span>
            <span class="font-mono font-bold text-neutral-900">{{ answeredCount }} / {{ totalCount }}</span>
            <span class="text-neutral-400 font-mono text-[11px]">({{ completionPercentage }}%)</span>
          </div>

          <div class="flex items-center space-x-1.5">
            <span class="font-mono text-[11px] text-neutral-400">Score:</span>
            <span class="font-mono font-bold text-brand-600">{{ liveScorePercentage.toFixed(1) }}%</span>
          </div>
        </div>

        <!-- Progress track -->
        <div class="w-full bg-neutral-100 rounded-full h-1.5 overflow-hidden">
          <div
            class="bg-brand-600 h-1.5 rounded-full transition-all duration-300"
            :style="{ width: `${completionPercentage}%` }"
          ></div>
        </div>

        <!-- Buttons Row -->
        <div class="flex items-center justify-between gap-2 pt-1">
          <!-- Reopen Button (Managers / Admins on submitted audits) -->
          <button
            v-if="visitAudit.status === 'submitted' && canReopen"
            type="button"
            @click="showReopenModal = true"
            class="tap-target px-4 py-2 text-xs font-semibold border border-neutral-300 hover:bg-neutral-100 rounded-lg transition-colors text-neutral-800"
          >
            Reopen Audit
          </button>

          <div v-else class="text-[11px] font-mono text-neutral-400">
            <span v-if="unansweredCount > 0">{{ unansweredCount }} unanswered</span>
            <span v-else class="text-emerald-600 font-semibold">&check; All items answered</span>
          </div>

          <div class="flex items-center space-x-2">
            <!-- Save Draft Button -->
            <button
              v-if="!isReadonly"
              type="button"
              @click="saveDraft"
              :disabled="isSaving"
              class="tap-target px-4 py-2 text-xs font-semibold border border-neutral-300 hover:bg-neutral-100 rounded-lg transition-colors text-neutral-800 disabled:opacity-50"
            >
              {{ isSaving ? 'Saving...' : 'Save Draft' }}
            </button>

            <!-- Submit Audit Button -->
            <button
              v-if="!isReadonly"
              type="button"
              @click="submitAudit"
              :disabled="isSubmitting || unansweredCount > 0"
              class="tap-target px-5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors disabled:opacity-50 shadow-xs"
            >
              {{ isSubmitting ? 'Submitting...' : 'Submit Final Audit' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- REOPEN MODAL (Full-Screen Sheet on Mobile) -->
    <div
      v-if="showReopenModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/60 backdrop-blur-xs flex sm:items-center sm:justify-center p-0 sm:p-4"
    >
      <div class="fixed inset-0 sm:inset-auto sm:relative bg-white sm:rounded-2xl max-w-md w-full h-full sm:h-auto flex flex-col p-5 shadow-2xl border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100 shrink-0">
          <h3 class="text-base font-bold text-neutral-900">Reopen Submitted Audit</h3>
          <button @click="showReopenModal = false" class="tap-target p-2 text-neutral-400 hover:text-neutral-700 text-lg">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto py-3 space-y-3">
          <p class="text-neutral-600 leading-relaxed text-xs">
            Reopening this audit will return it to editable in-progress status. A mandatory justification reason is required for the audit trail.
          </p>
          <div>
            <label class="block text-[10px] font-mono uppercase text-neutral-400 mb-1">Justification Reason *</label>
            <textarea
              v-model.trim="reopenReason"
              required
              rows="4"
              placeholder="Specify why this audit is being reopened for corrections..."
              class="w-full text-base sm:text-xs p-3 rounded-xl border border-neutral-200 focus:border-brand-600 focus:ring-1 focus:ring-brand-600"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100 shrink-0">
          <button type="button" @click="showReopenModal = false" class="tap-target px-4 py-2 text-neutral-600 font-medium">Cancel</button>
          <button
            type="button"
            @click="confirmReopen"
            :disabled="!reopenReason || isReopening"
            class="tap-target px-5 py-2.5 bg-neutral-900 hover:bg-black text-white rounded-lg font-bold disabled:opacity-50"
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
const summaryRemarks = ref('');
const showReopenModal = ref(false);
const reopenReason = ref('');

// Save states: 'saved' | 'saving' | 'error' | 'unsaved'
const saveState = ref('saved');
const hasUnsavedChanges = ref(false);

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
    saveState.value = 'saved';
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

const saveStateLabel = computed(() => {
  if (saveState.value === 'saving') return 'Saving…';
  if (saveState.value === 'error') return 'Not saved — retry';
  if (hasUnsavedChanges.value) return 'Unsaved changes';
  return 'Saved';
});

const saveStateClasses = computed(() => {
  if (saveState.value === 'saving') return 'bg-amber-50 text-amber-700 border-amber-200';
  if (saveState.value === 'error') return 'bg-rose-50 text-rose-700 border-rose-300 font-semibold';
  if (hasUnsavedChanges.value) return 'bg-neutral-50 text-neutral-600 border-neutral-200';
  return 'bg-emerald-50 text-emerald-700 border-emerald-200';
});

const saveDotClasses = computed(() => {
  if (saveState.value === 'saving') return 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
  if (saveState.value === 'error') return 'w-2 h-2 rounded-full bg-rose-600';
  if (hasUnsavedChanges.value) return 'w-2 h-2 rounded-full bg-neutral-400';
  return 'w-2 h-2 rounded-full bg-emerald-600';
});

const statusBadgeClasses = computed(() => {
  const st = visitAudit.value?.status;
  if (st === 'submitted') return 'px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-neutral-900 text-white';
  if (st === 'reopened') return 'px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300';
  return 'px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200';
});

const groupedAnswers = computed(() => {
  if (!visitAudit.value?.answers) return [];
  const groups = {};
  for (const it of visitAudit.value.answers) {
    const cat = it.category || 'General Operations';
    if (!groups[cat]) {
      groups[cat] = { name: cat, items: [] };
    }
    groups[cat].items.push(it);
  }
  return Object.values(groups);
});

const totalCount = computed(() => {
  return visitAudit.value?.answers?.length || 0;
});

const answeredCount = computed(() => {
  if (!visitAudit.value?.answers) return 0;
  return visitAudit.value.answers.filter(it => it.answer !== null && it.answer !== '').length;
});

const unansweredCount = computed(() => {
  return totalCount.value - answeredCount.value;
});

const completionPercentage = computed(() => {
  if (totalCount.value === 0) return 0;
  return Math.round((answeredCount.value / totalCount.value) * 100);
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

function onFieldChange() {
  hasUnsavedChanges.value = true;
  if (saveState.value === 'error') {
    // Keep error state until explicit save succeeds
  } else {
    saveState.value = 'unsaved';
  }
}

function setAnswer(item, val) {
  if (isReadonly.value) return;
  item.answer = val;
  onFieldChange();
}

async function saveDraft() {
  if (isReadonly.value) return;
  isSaving.value = true;
  saveState.value = 'saving';
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
    saveState.value = 'saved';
    hasUnsavedChanges.value = false;
  } catch (err) {
    saveState.value = 'error';
    // CRITICAL: Unsaved answers remain intact in memory (visitAudit.value.answers). Never drop input.
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
    // First save latest in-memory answers
    await saveDraft();
    const res = await api.post(`/visit-audits/${visitAudit.value.id}/submit`, {
      summary_remarks: summaryRemarks.value,
    });
    visitAudit.value = res.data;
    saveState.value = 'saved';
    hasUnsavedChanges.value = false;
  } catch (err) {
    saveState.value = 'error';
    alert(err.message || 'Submission failed. Please check your network and retry.');
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
    saveState.value = 'saved';
  } catch (err) {
    alert(err.message || 'Failed to reopen audit.');
  } finally {
    isReopening.value = false;
  }
}

function formatDateTime(dt) {
  if (!dt) return null;
  return new Date(dt).toLocaleString();
}
</script>

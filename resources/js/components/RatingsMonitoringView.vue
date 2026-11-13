<template>
  <div class="py-5 sm:py-7">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Performance Analytics &bull; FR2005 / FR3004-B
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-1">
            Ratings Monitoring Matrix
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Store visit audit scores aggregated weekly across retail locations (Asia/Manila time).
          </p>
        </div>

        <!-- Export Buttons -->
        <div class="flex flex-wrap items-center gap-2">
          <a
            :href="exportUrl('csv')"
            download
            class="min-h-[44px] inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-neutral-300 bg-white text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:border-neutral-400 transition-colors shadow-xs"
          >
            <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>CSV</span>
          </a>
          <a
            :href="exportUrl('excel')"
            download
            class="min-h-[44px] inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-neutral-300 bg-white text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:border-neutral-400 transition-colors shadow-xs"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Excel (.xlsx)</span>
          </a>
          <a
            :href="exportUrl('pdf')"
            download
            class="min-h-[44px] inline-flex items-center space-x-1.5 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs font-semibold transition-colors shadow-xs"
          >
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>PDF</span>
          </a>
        </div>
      </div>

      <!-- Filters & Matrix Card -->
      <div class="bg-white rounded-xl border border-neutral-200/80 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-neutral-100 flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-3">
            <!-- Audit Type Toggle -->
            <div class="inline-flex rounded-lg border border-neutral-300 bg-neutral-100/80 p-1 text-xs">
              <button
                type="button"
                @click="switchType('treasury')"
                :class="[
                  'min-h-[38px] px-4 py-1.5 rounded-md font-semibold transition-colors',
                  filters.audit_type === 'treasury' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:text-neutral-900'
                ]"
              >
                Treasury
              </button>
              <button
                type="button"
                @click="switchType('cashier')"
                :class="[
                  'min-h-[38px] px-4 py-1.5 rounded-md font-semibold transition-colors',
                  filters.audit_type === 'cashier' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:text-neutral-900'
                ]"
              >
                Cashier
              </button>
            </div>

            <!-- Month Picker -->
            <input
              v-model="filters.month"
              @change="loadMatrix"
              type="month"
              class="min-h-[44px] text-base sm:text-xs rounded-lg border border-neutral-300 px-3 py-2 bg-white text-neutral-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
            />
          </div>

          <div class="text-xs text-neutral-400 font-mono">
            Timezone: Asia/Manila (+08:00)
          </div>
        </div>

        <!-- Desktop Matrix Table (hidden on mobile) -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase text-[10px]">
                <th class="py-3 px-4 w-32">Area</th>
                <th class="py-3 px-4 w-28">Store Code</th>
                <th class="py-3 px-4">Store Name</th>
                <th class="py-3 px-4 text-right w-24">W1 (1-7)</th>
                <th class="py-3 px-4 text-right w-24">W2 (8-14)</th>
                <th class="py-3 px-4 text-right w-24">W3 (15-21)</th>
                <th class="py-3 px-4 text-right w-24">W4 (22-End)</th>
                <th class="py-3 px-4 text-right w-28">Month Avg</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-if="isLoading" class="text-center">
                <td colspan="8" class="py-12 text-neutral-400">Computing ratings matrix...</td>
              </tr>
              <tr v-else-if="matrixRows.length === 0" class="text-center">
                <td colspan="8" class="py-12 text-neutral-400">No stores found or no audits submitted for this period.</td>
              </tr>
              <tr
                v-for="row in matrixRows"
                :key="row.store_id"
                class="hover:bg-neutral-50/60 transition-colors"
              >
                <td class="py-3 px-4 font-medium text-neutral-900">{{ row.area_name }}</td>
                <td class="py-3 px-4 font-mono text-neutral-500">{{ row.store_code }}</td>
                <td class="py-3 px-4 text-neutral-800 font-medium">{{ row.store_name }}</td>
                <td class="py-3 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_1) }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_2) }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_3) }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_4) }}
                </td>
                <td class="py-3 px-4 text-right font-mono font-bold text-brand-700 bg-brand-50/40">
                  {{ formatScore(row.month_average) }}
                </td>
              </tr>

              <!-- Average Row -->
              <tr v-if="matrixRows.length > 0" class="bg-neutral-50/80 font-bold border-t-2 border-neutral-300">
                <td colspan="3" class="py-3 px-4 uppercase text-[11px] tracking-wider text-neutral-900">
                  NETWORK AVERAGE
                </td>
                <td class="py-3 px-4 text-right font-mono">{{ formatScore(summary.week_1_avg) }}</td>
                <td class="py-3 px-4 text-right font-mono">{{ formatScore(summary.week_2_avg) }}</td>
                <td class="py-3 px-4 text-right font-mono">{{ formatScore(summary.week_3_avg) }}</td>
                <td class="py-3 px-4 text-right font-mono">{{ formatScore(summary.week_4_avg) }}</td>
                <td class="py-3 px-4 text-right font-mono">-</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Stacked Card View (< 640px) -->
        <div class="block sm:hidden divide-y divide-neutral-100">
          <div v-if="isLoading" class="py-12 text-center text-neutral-400 text-xs">
            Computing ratings matrix...
          </div>
          <div v-else-if="matrixRows.length === 0" class="py-12 text-center text-neutral-400 text-xs">
            No stores found or no audits submitted for this period.
          </div>
          <div
            v-else
            v-for="row in matrixRows"
            :key="'mob-matrix-' + row.store_id"
            class="p-4 space-y-3"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="text-sm font-bold text-neutral-900">{{ row.store_name }}</div>
                <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                  {{ row.area_name }} &bull; {{ row.store_code }}
                </div>
              </div>
              <div class="text-right shrink-0">
                <div class="text-[10px] text-neutral-400 uppercase font-mono">Avg Score</div>
                <div class="text-base font-bold text-brand-700 font-mono">{{ formatScore(row.month_average) }}</div>
              </div>
            </div>

            <!-- 4 Weeks Grid -->
            <div class="grid grid-cols-4 gap-1.5 text-center bg-neutral-50 p-2.5 rounded-lg border border-neutral-100 text-xs font-mono">
              <div>
                <div class="text-[10px] text-neutral-400">W1</div>
                <div class="font-semibold text-neutral-800 mt-0.5">{{ formatScore(row.week_1) }}</div>
              </div>
              <div>
                <div class="text-[10px] text-neutral-400">W2</div>
                <div class="font-semibold text-neutral-800 mt-0.5">{{ formatScore(row.week_2) }}</div>
              </div>
              <div>
                <div class="text-[10px] text-neutral-400">W3</div>
                <div class="font-semibold text-neutral-800 mt-0.5">{{ formatScore(row.week_3) }}</div>
              </div>
              <div>
                <div class="text-[10px] text-neutral-400">W4</div>
                <div class="font-semibold text-neutral-800 mt-0.5">{{ formatScore(row.week_4) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { api } from '../services/api';

const matrixRows = ref([]);
const summary = ref({});
const isLoading = ref(false);

const currentMonthStr = new Date().toISOString().slice(0, 7);

const filters = reactive({
  audit_type: 'treasury',
  month: currentMonthStr,
});

onMounted(() => {
  loadMatrix();
});

async function loadMatrix() {
  isLoading.value = true;
  try {
    const res = await api.get(`/ratings/matrix?audit_type=${filters.audit_type}&month=${filters.month}`);
    matrixRows.value = res.data?.rows || [];
    summary.value = res.data?.summary || {};
  } catch (err) {
    console.error('Failed to load matrix:', err);
  } finally {
    isLoading.value = false;
  }
}

function switchType(t) {
  filters.audit_type = t;
  loadMatrix();
}

function formatScore(val) {
  if (val === null || val === undefined) return '-';
  return Number(val).toFixed(2) + '%';
}

function exportUrl(format) {
  return `/api/ratings/export/${format}?audit_type=${filters.audit_type}&month=${filters.month}`;
}
</script>


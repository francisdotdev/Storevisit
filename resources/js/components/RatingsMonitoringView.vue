<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Performance Analytics &bull; FR2005 / FR3004-B
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            Ratings Monitoring Matrix
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Store visit audit scores aggregated weekly across retail locations (Asia/Manila time).
          </p>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center space-x-2">
          <a
            :href="exportUrl('csv')"
            download
            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md border border-neutral-200 bg-white text-xs font-medium text-neutral-700 hover:bg-neutral-50"
          >
            <span>CSV</span>
          </a>
          <a
            :href="exportUrl('excel')"
            download
            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md border border-neutral-200 bg-white text-xs font-medium text-neutral-700 hover:bg-neutral-50"
          >
            <span>Excel (.xlsx)</span>
          </a>
          <a
            :href="exportUrl('pdf')"
            download
            class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium"
          >
            <span>PDF</span>
          </a>
        </div>
      </div>

      <!-- Filters & Matrix Card -->
      <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-neutral-100 flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Audit Type Toggle -->
            <div class="inline-flex rounded-md border border-neutral-200 bg-neutral-50 p-0.5 text-xs">
              <button
                type="button"
                @click="switchType('treasury')"
                :class="[
                  'px-3 py-1 rounded font-medium transition-colors',
                  filters.audit_type === 'treasury' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
                ]"
              >
                Treasury
              </button>
              <button
                type="button"
                @click="switchType('cashier')"
                :class="[
                  'px-3 py-1 rounded font-medium transition-colors',
                  filters.audit_type === 'cashier' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
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
              class="text-xs rounded-md border border-neutral-200 px-2.5 py-1.5 bg-white text-neutral-800"
            />
          </div>

          <div class="text-xs text-neutral-400 font-mono">
            Timezone: Asia/Manila (+08:00)
          </div>
        </div>

        <!-- Matrix Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase text-[10px]">
                <th class="py-2.5 px-4 w-32">Area</th>
                <th class="py-2.5 px-4 w-28">Store Code</th>
                <th class="py-2.5 px-4">Store Name</th>
                <th class="py-2.5 px-4 text-right w-24">W1 (1-7)</th>
                <th class="py-2.5 px-4 text-right w-24">W2 (8-14)</th>
                <th class="py-2.5 px-4 text-right w-24">W3 (15-21)</th>
                <th class="py-2.5 px-4 text-right w-24">W4 (22-End)</th>
                <th class="py-2.5 px-4 text-right w-28">Month Avg</th>
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
                class="hover:bg-neutral-50/50 transition-colors"
              >
                <td class="py-2.5 px-4 font-medium text-neutral-900">{{ row.area_name }}</td>
                <td class="py-2.5 px-4 font-mono text-neutral-500">{{ row.store_code }}</td>
                <td class="py-2.5 px-4 text-neutral-800">{{ row.store_name }}</td>
                <td class="py-2.5 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_1) }}
                </td>
                <td class="py-2.5 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_2) }}
                </td>
                <td class="py-2.5 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_3) }}
                </td>
                <td class="py-2.5 px-4 text-right font-mono font-medium">
                  {{ formatScore(row.week_4) }}
                </td>
                <td class="py-2.5 px-4 text-right font-mono font-bold text-neutral-900 bg-neutral-50/50">
                  {{ formatScore(row.month_average) }}
                </td>
              </tr>

              <!-- Average Row -->
              <tr v-if="matrixRows.length > 0" class="bg-neutral-50 font-bold border-t-2 border-neutral-300">
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


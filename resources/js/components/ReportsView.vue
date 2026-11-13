<template>
  <div class="py-5 sm:py-7">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Reporting &amp; Data Export &bull; FR3002 / FR3004-A
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-1">
            Executive Audit Reports
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Cross-department visit calendar logs, observation tracking, and export downloads (CSV, Excel, PDF).
          </p>
        </div>

        <!-- Export Buttons (matching active report) -->
        <div class="flex flex-wrap items-center gap-2">
          <a
            :href="getExportUrl('csv')"
            download
            class="min-h-[44px] inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-neutral-300 bg-white text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:border-neutral-400 transition-colors shadow-xs"
          >
            <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>CSV</span>
          </a>
          <a
            :href="getExportUrl('excel')"
            download
            class="min-h-[44px] inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-neutral-300 bg-white text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:border-neutral-400 transition-colors shadow-xs"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Excel (.xlsx)</span>
          </a>
          <a
            :href="getExportUrl('pdf')"
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

      <!-- Report Tabs Switcher -->
      <div class="flex items-center space-x-6 border-b border-neutral-200">
        <button
          type="button"
          @click="activeReport = 'calendar'"
          :class="[
            'min-h-[44px] pb-3 text-xs font-medium border-b-2 transition-colors flex items-center',
            activeReport === 'calendar'
              ? 'border-brand-600 text-brand-700 font-bold'
              : 'border-transparent text-neutral-500 hover:text-neutral-900'
          ]"
        >
          Calendar Visits (FR3002)
        </button>
        <button
          type="button"
          @click="activeReport = 'observations'"
          :class="[
            'min-h-[44px] pb-3 text-xs font-medium border-b-2 transition-colors flex items-center',
            activeReport === 'observations'
              ? 'border-brand-600 text-brand-700 font-bold'
              : 'border-transparent text-neutral-500 hover:text-neutral-900'
          ]"
        >
          Audit Observations (FR3004-A)
        </button>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white p-4 rounded-xl border border-neutral-200/80 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <select
          v-model="filters.audit_type"
          @change="loadReportData"
          class="min-h-[44px] rounded-lg border border-neutral-300 bg-white px-3 py-2 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
        >
          <option value="">All Audit Types</option>
          <option value="treasury">Treasury</option>
          <option value="cashier">Cashier</option>
        </select>

        <select
          v-model="filters.status"
          @change="loadReportData"
          class="min-h-[44px] rounded-lg border border-neutral-300 bg-white px-3 py-2 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
        >
          <option value="">All Statuses</option>
          <template v-if="activeReport === 'calendar'">
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </template>
          <template v-else>
            <option value="open">Open</option>
            <option value="escalated">Escalated</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </template>
        </select>

        <div class="flex items-center space-x-2">
          <span class="text-neutral-500 font-mono text-[11px]">From:</span>
          <input
            v-model="filters.date_from"
            @change="loadReportData"
            type="date"
            class="min-h-[44px] rounded-lg border border-neutral-300 bg-white px-3 py-2 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
          />
        </div>

        <div class="flex items-center space-x-2">
          <span class="text-neutral-500 font-mono text-[11px]">To:</span>
          <input
            v-model="filters.date_to"
            @change="loadReportData"
            type="date"
            class="min-h-[44px] rounded-lg border border-neutral-300 bg-white px-3 py-2 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
          />
        </div>

        <button
          type="button"
          @click="resetFilters"
          class="min-h-[44px] px-3.5 py-2 rounded-lg border border-neutral-300 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 transition-colors font-medium text-xs"
        >
          Reset Filters
        </button>
      </div>

      <!-- Table & Mobile Cards Container -->
      <div class="bg-white rounded-xl border border-neutral-200/80 overflow-hidden shadow-xs">
        <!-- Desktop Table (hidden on small screens) -->
        <div class="hidden sm:block overflow-x-auto">
          <!-- Calendar Visits Table -->
          <table v-if="activeReport === 'calendar'" class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase text-[10px]">
                <th class="py-3 px-4">Code</th>
                <th class="py-3 px-4">Scheduled Date</th>
                <th class="py-3 px-4">Time Window</th>
                <th class="py-3 px-4">Area</th>
                <th class="py-3 px-4">Store</th>
                <th class="py-3 px-4">Type</th>
                <th class="py-3 px-4">Supervisor</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Actual Started</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-if="isLoading" class="text-center">
                <td colspan="9" class="py-12 text-neutral-400">Loading visit records...</td>
              </tr>
              <tr v-else-if="rows.length === 0" class="text-center">
                <td colspan="9" class="py-12 text-neutral-400">No records found matching filters.</td>
              </tr>
              <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50/60 transition-colors">
                <td class="py-3 px-4 font-mono font-medium text-brand-700">{{ r.schedule_code }}</td>
                <td class="py-3 px-4 font-mono text-neutral-700">{{ r.scheduled_date }}</td>
                <td class="py-3 px-4 text-neutral-600 font-mono text-[11px]">{{ r.time_window }}</td>
                <td class="py-3 px-4 font-medium text-neutral-900">{{ r.area_name }}</td>
                <td class="py-3 px-4">
                  <div class="text-neutral-900 font-medium">{{ r.store_name }}</div>
                  <div class="text-[10px] font-mono text-neutral-400">{{ r.store_code }}</div>
                </td>
                <td class="py-3 px-4 font-mono uppercase text-[11px] text-neutral-600">{{ r.audit_type }}</td>
                <td class="py-3 px-4 text-neutral-800">{{ r.supervisor_name }}</td>
                <td class="py-3 px-4">
                  <span :class="getStatusPill(r.status)">{{ r.status }}</span>
                </td>
                <td class="py-3 px-4 font-mono text-neutral-500 text-[11px]">{{ r.actual_started }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Observations Table -->
          <table v-else class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase text-[10px]">
                <th class="py-3 px-4">Observed Date</th>
                <th class="py-3 px-4">Store</th>
                <th class="py-3 px-4">Type</th>
                <th class="py-3 px-4">Supervisor</th>
                <th class="py-3 px-4">Identified Issue</th>
                <th class="py-3 px-4">Improvements</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Days Open</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-if="isLoading" class="text-center">
                <td colspan="8" class="py-12 text-neutral-400">Loading observation records...</td>
              </tr>
              <tr v-else-if="rows.length === 0" class="text-center">
                <td colspan="8" class="py-12 text-neutral-400">No records found matching filters.</td>
              </tr>
              <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50/60 transition-colors">
                <td class="py-3 px-4 font-mono text-[11px] text-neutral-600">{{ r.observed_at }}</td>
                <td class="py-3 px-4">
                  <div class="font-medium text-neutral-900">{{ r.store_name }}</div>
                  <div class="text-[10px] font-mono text-neutral-400">{{ r.area_name }}</div>
                </td>
                <td class="py-3 px-4 font-mono uppercase text-[11px] text-neutral-600">{{ r.audit_type }}</td>
                <td class="py-3 px-4 text-neutral-800">{{ r.supervisor_name }}</td>
                <td class="py-3 px-4 max-w-xs text-neutral-800">
                  <p class="line-clamp-2">{{ r.issue_description }}</p>
                </td>
                <td class="py-3 px-4 max-w-xs text-neutral-600">
                  <p class="line-clamp-2">{{ r.improvements }}</p>
                </td>
                <td class="py-3 px-4">
                  <span :class="getStatusPill(r.status)">{{ r.status }}</span>
                </td>
                <td class="py-3 px-4 text-right font-mono font-medium text-neutral-900">{{ r.days_open }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Stacked Card View (< 640px) -->
        <div class="block sm:hidden divide-y divide-neutral-100">
          <div v-if="isLoading" class="py-12 text-center text-neutral-400 text-xs">
            Loading records...
          </div>
          <div v-else-if="rows.length === 0" class="py-12 text-center text-neutral-400 text-xs">
            No records found matching filters.
          </div>

          <!-- Mobile Cards: Calendar Visits -->
          <div
            v-else-if="activeReport === 'calendar'"
            v-for="r in rows"
            :key="'mob-cal-' + r.id"
            class="p-4 space-y-2.5"
          >
            <div class="flex items-center justify-between">
              <span class="font-mono font-bold text-xs text-brand-700">{{ r.schedule_code }}</span>
              <span :class="getStatusPill(r.status)">{{ r.status }}</span>
            </div>
            <div>
              <div class="text-sm font-bold text-neutral-900">{{ r.store_name }}</div>
              <div class="text-[11px] text-neutral-500 flex items-center gap-2 mt-0.5">
                <span>{{ r.area_name }}</span>
                <span>&bull;</span>
                <span class="font-mono uppercase">{{ r.audit_type }}</span>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
              <div>
                <span class="text-[10px] text-neutral-400 block uppercase">Scheduled</span>
                <span class="font-mono text-neutral-800">{{ r.scheduled_date }}</span>
              </div>
              <div>
                <span class="text-[10px] text-neutral-400 block uppercase">Time Window</span>
                <span class="font-mono text-neutral-800">{{ r.time_window }}</span>
              </div>
              <div class="col-span-2 pt-1 border-t border-neutral-200/50 flex justify-between items-center">
                <span class="text-neutral-500">Auditor:</span>
                <span class="font-medium text-neutral-900">{{ r.supervisor_name }}</span>
              </div>
            </div>
          </div>

          <!-- Mobile Cards: Observations -->
          <div
            v-else
            v-for="r in rows"
            :key="'mob-obs-' + r.id"
            class="p-4 space-y-2.5"
          >
            <div class="flex items-center justify-between">
              <span class="font-mono text-[11px] text-neutral-500">{{ r.observed_at }}</span>
              <span :class="getStatusPill(r.status)">{{ r.status }}</span>
            </div>
            <div>
              <div class="text-sm font-bold text-neutral-900">{{ r.store_name }}</div>
              <div class="text-[11px] text-neutral-500 font-mono uppercase">{{ r.audit_type }} &bull; {{ r.area_name }}</div>
            </div>
            <div class="text-xs bg-neutral-50 p-3 rounded-lg border border-neutral-100 space-y-1.5">
              <div>
                <span class="text-[10px] font-semibold text-neutral-500 uppercase block">Issue</span>
                <p class="text-neutral-900 leading-relaxed">{{ r.issue_description }}</p>
              </div>
              <div v-if="r.improvements" class="pt-1.5 border-t border-neutral-200/50">
                <span class="text-[10px] font-semibold text-neutral-500 uppercase block">Required Actions</span>
                <p class="text-neutral-700 leading-relaxed">{{ r.improvements }}</p>
              </div>
            </div>
            <div class="flex items-center justify-between text-xs text-neutral-500">
              <span>Auditor: <strong class="text-neutral-800 font-medium">{{ r.supervisor_name }}</strong></span>
              <span class="font-mono font-semibold text-neutral-900">{{ r.days_open }} days open</span>
            </div>
          </div>
        </div>

        <!-- Pagination Bar -->
        <div v-if="pagination.total > 0" class="p-3 border-t border-neutral-100 bg-neutral-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-neutral-500">
          <div>Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} records (Page max capped at 100)</div>
          <div class="flex items-center justify-center space-x-2">
            <button
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
              class="min-h-[44px] min-w-[44px] px-3 py-1.5 rounded-lg border border-neutral-300 bg-white text-neutral-700 font-medium disabled:opacity-40 transition-colors shadow-xs"
            >
              Previous
            </button>
            <span class="px-2 font-mono font-semibold text-neutral-700">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
              class="min-h-[44px] min-w-[44px] px-3 py-1.5 rounded-lg border border-neutral-300 bg-white text-neutral-700 font-medium disabled:opacity-40 transition-colors shadow-xs"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import { api } from '../services/api';

const activeReport = ref('calendar'); // 'calendar' | 'observations'
const rows = ref([]);
const isLoading = ref(false);

const filters = reactive({
  audit_type: '',
  status: '',
  date_from: '',
  date_to: '',
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: 0,
  to: 0,
});

watch(activeReport, () => {
  filters.status = '';
  pagination.current_page = 1;
  loadReportData();
});

onMounted(() => {
  loadReportData();
});

async function loadReportData() {
  isLoading.value = true;
  try {
    const endpoint = activeReport.value === 'calendar' ? '/reports/calendar-visits' : '/reports/observations';
    const params = new URLSearchParams({
      page: pagination.current_page,
      per_page: pagination.per_page,
    });
    if (filters.audit_type) params.append('audit_type', filters.audit_type);
    if (filters.status) params.append('status', filters.status);
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);

    const res = await api.get(`${endpoint}?${params.toString()}`);
    const paginated = res.data;
    rows.value = paginated.data || [];
    pagination.current_page = paginated.current_page || 1;
    pagination.last_page = paginated.last_page || 1;
    pagination.total = paginated.total || 0;
    pagination.from = paginated.from || 0;
    pagination.to = paginated.to || 0;
  } catch (err) {
    console.error('Failed to load report:', err);
  } finally {
    isLoading.value = false;
  }
}

function changePage(p) {
  pagination.current_page = p;
  loadReportData();
}

function resetFilters() {
  filters.audit_type = '';
  filters.status = '';
  filters.date_from = '';
  filters.date_to = '';
  pagination.current_page = 1;
  loadReportData();
}

function getExportUrl(format) {
  const base = activeReport.value === 'calendar' ? '/api/reports/calendar-visits/export' : '/api/reports/observations/export';
  const params = new URLSearchParams();
  if (filters.audit_type) params.append('audit_type', filters.audit_type);
  if (filters.status) params.append('status', filters.status);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  return `${base}/${format}?${params.toString()}`;
}

function getStatusPill(st) {
  const s = String(st).toLowerCase();
  if (s === 'completed' || s === 'resolved') return 'px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200';
  if (s === 'in_progress' || s === 'escalated') return 'px-2 py-0.5 rounded text-[10px] font-mono bg-amber-50 text-amber-800 border border-amber-200';
  if (s === 'missed' || s === 'cancelled') return 'px-2 py-0.5 rounded text-[10px] font-mono bg-rose-50 text-rose-800 border border-rose-200';
  return 'px-2 py-0.5 rounded text-[10px] font-mono bg-neutral-100 text-neutral-800 border border-neutral-200';
}
</script>


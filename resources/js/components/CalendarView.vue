<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Page Header & View Toggle -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Audit Planning &bull; Field Operations
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            Store Visit Calendar
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Plan, monitor, and track field audit schedules, visit dates, and execution timings.
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <!-- View Toggle: Grid vs List -->
          <div class="bg-neutral-100 p-0.5 rounded-md flex items-center space-x-0.5">
            <button
              type="button"
              @click="switchViewMode('grid')"
              :class="[
                'px-3 py-1.5 rounded text-xs font-medium transition-all flex items-center space-x-1.5',
                viewMode === 'grid'
                  ? 'bg-white text-neutral-900 shadow-xs'
                  : 'text-neutral-500 hover:text-neutral-900'
              ]"
            >
              <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Calendar</span>
            </button>

            <button
              type="button"
              @click="switchViewMode('list')"
              :class="[
                'px-3 py-1.5 rounded text-xs font-medium transition-all flex items-center space-x-1.5',
                viewMode === 'list'
                  ? 'bg-white text-neutral-900 shadow-xs'
                  : 'text-neutral-500 hover:text-neutral-900'
              ]"
            >
              <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
              <span>List View</span>
            </button>
          </div>

          <!-- Schedule New Visit Button (Asst Managers & Admin) -->
          <button
            v-if="canScheduleVisits"
            type="button"
            @click="openScheduleModal"
            class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" />
            </svg>
            <span>Schedule Visit</span>
          </button>
        </div>
      </div>

      <!-- Action Toast Banner -->
      <div v-if="toastMessage" :class="toastClasses" class="p-3 rounded-md flex items-center justify-between text-xs transition-all border">
        <div class="flex items-center space-x-2">
          <span>{{ toastMessage }}</span>
        </div>
        <button type="button" @click="toastMessage = ''" class="text-neutral-400 hover:text-neutral-600">&times;</button>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
          <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Total Visits</p>
          <p class="text-xl font-semibold text-neutral-900 mt-1">{{ stats.total_visits }}</p>
        </div>

        <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
          <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Scheduled</p>
          <p class="text-xl font-semibold text-neutral-900 mt-1">{{ stats.scheduled }}</p>
        </div>

        <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
          <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">In Progress</p>
          <p class="text-xl font-semibold text-neutral-900 mt-1">{{ stats.in_progress }}</p>
        </div>

        <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
          <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Completed</p>
          <p class="text-xl font-semibold text-neutral-900 mt-1">{{ stats.completed }}</p>
        </div>
      </div>

      <!-- Supervisor Scoping Notice -->
      <div v-if="isSupervisor" class="p-3 rounded-md bg-neutral-50 border border-neutral-200 text-neutral-700 text-xs flex items-center space-x-2">
        <span class="w-1.5 h-1.5 rounded-full bg-neutral-500"></span>
        <div>
          <span class="font-medium">Supervisor Scope:</span>
          Showing only visits assigned specifically to you for <span class="font-mono uppercase">{{ auditType }}</span> audits.
        </div>
      </div>

      <!-- Period Navigation & Filters Toolbar -->
      <div class="bg-white rounded-lg border border-neutral-200 p-3 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Month / Period Navigation Controls -->
        <div class="flex items-center space-x-1.5">
          <button
            type="button"
            @click="previousMonth"
            class="p-1.5 rounded border border-neutral-200 text-neutral-600 hover:bg-neutral-50 transition-colors"
            title="Previous Month"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <span class="font-medium text-xs text-neutral-900 min-w-[130px] text-center font-mono">
            {{ currentMonthFormatted }}
          </span>

          <button
            type="button"
            @click="nextMonth"
            class="p-1.5 rounded border border-neutral-200 text-neutral-600 hover:bg-neutral-50 transition-colors"
            title="Next Month"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <button
            type="button"
            @click="goToCurrentMonth"
            class="px-2 py-1 text-xs font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded transition-colors ml-1"
          >
            Today
          </button>
        </div>

        <!-- Filters (Audit Type, Area, Status, Search) -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Audit Type (Treasury / Cashier) -->
          <select
            v-model="filterAuditType"
            @change="reloadSchedules"
            :disabled="!isAdmin && !isManager"
            class="px-2.5 py-1 text-xs rounded border border-neutral-200 bg-white text-neutral-700 focus:border-neutral-900 focus:ring-0 disabled:bg-neutral-100"
          >
            <option value="">All Tracks</option>
            <option value="treasury">Treasury</option>
            <option value="cashier">Cashier</option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="filterStatus"
            @change="reloadSchedules"
            class="px-2.5 py-1 text-xs rounded border border-neutral-200 bg-white text-neutral-700 focus:border-neutral-900 focus:ring-0"
          >
            <option value="all">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>

          <!-- Area Filter -->
          <select
            v-model="filterAreaId"
            @change="reloadSchedules"
            class="px-2.5 py-1 text-xs rounded border border-neutral-200 bg-white text-neutral-700 focus:border-neutral-900 focus:ring-0"
          >
            <option value="">All Areas</option>
            <option v-for="a in areasDropdown" :key="a.id" :value="a.id">{{ a.area_name }}</option>
          </select>

          <!-- Search Input -->
          <div class="relative min-w-[170px]">
            <input
              v-model="filterSearch"
              @keydown.enter="reloadSchedules"
              type="text"
              placeholder="Search store, code..."
              class="w-full pl-7 pr-2.5 py-1 text-xs rounded border border-neutral-200 focus:border-neutral-900 focus:ring-0"
            />
            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-neutral-400">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================= -->
      <!-- VIEW 1: CALENDAR / GRID VIEW                                   -->
      <!-- ============================================================= -->
      <div v-if="viewMode === 'grid'" class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
        <!-- Calendar Days Header -->
        <div class="grid grid-cols-7 border-b border-neutral-200 bg-neutral-50 text-center text-[10px] font-mono uppercase tracking-wider text-neutral-400 py-2.5">
          <div>Sun</div>
          <div>Mon</div>
          <div>Tue</div>
          <div>Wed</div>
          <div>Thu</div>
          <div>Fri</div>
          <div>Sat</div>
        </div>

        <!-- Calendar Day Cells Grid -->
        <div class="grid grid-cols-7 divide-x divide-y divide-neutral-100 auto-rows-fr min-h-[500px]">
          <div
            v-for="(day, idx) in calendarDays"
            :key="idx"
            :class="[
              'p-2 flex flex-col justify-between min-h-[96px] transition-colors',
              day.isCurrentMonth ? 'bg-white' : 'bg-neutral-50/40 text-neutral-300',
              day.isToday ? 'bg-neutral-50' : ''
            ]"
          >
            <!-- Day Number -->
            <div class="flex items-center justify-between mb-1">
              <span
                :class="[
                  'text-xs font-mono w-5 h-5 rounded-full flex items-center justify-center',
                  day.isToday ? 'bg-neutral-900 text-white font-semibold' : day.isCurrentMonth ? 'text-neutral-700' : 'text-neutral-300'
                ]"
              >
                {{ day.dayNumber }}
              </span>
              <span v-if="day.events.length" class="text-[10px] font-mono text-neutral-400">
                {{ day.events.length }}
              </span>
            </div>

            <!-- Event Chips for this day -->
            <div class="space-y-1 overflow-y-auto max-h-[85px] text-[11px]">
              <div
                v-for="ev in day.events"
                :key="ev.id"
                @click="openEventDetails(ev)"
                :class="[
                  'px-1.5 py-0.5 rounded text-[11px] font-medium cursor-pointer border truncate transition-colors hover:border-neutral-400',
                  ev.status === 'completed'
                    ? 'bg-neutral-50 border-neutral-200 text-neutral-500'
                    : 'bg-neutral-50 border-neutral-200 text-neutral-900'
                ]"
                :title="`${ev.store?.store_name} (${ev.audit_type}) - ${ev.supervisor?.first_name}`"
              >
                <div class="flex items-center space-x-1">
                  <span
                    class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                    :class="ev.status === 'completed' ? 'bg-emerald-600' : ev.status === 'in_progress' ? 'bg-amber-500' : 'bg-neutral-400'"
                  ></span>
                  <span class="font-mono text-[10px] truncate">{{ ev.store?.store_code }}</span>
                </div>
                <div class="text-[10px] text-neutral-400 truncate">
                  {{ ev.supervisor?.first_name }} {{ ev.supervisor?.last_name?.[0] }}.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================= -->
      <!-- VIEW 2: LIST VIEW (BRD Specified Core Fields)                  -->
      <!-- ============================================================= -->
      <div v-else class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-mono uppercase tracking-wider text-[10px]">
                <th class="py-2.5 px-4">Date Visited</th>
                <th class="py-2.5 px-4">Store Visited</th>
                <th class="py-2.5 px-4">Time Started</th>
                <th class="py-2.5 px-4">Time End</th>
                <th class="py-2.5 px-4">Assigned Supervisor</th>
                <th class="py-2.5 px-4">Status</th>
                <th class="py-2.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-if="isLoading" class="text-center py-10">
                <td colspan="7" class="py-10 text-neutral-400">Loading visit schedules...</td>
              </tr>

              <tr v-else-if="listSchedules.length === 0" class="text-center py-10">
                <td colspan="7" class="py-10 text-neutral-400">No store visit records found for this period.</td>
              </tr>

              <tr v-for="item in listSchedules" :key="item.id" class="hover:bg-neutral-50/50 transition-colors">
                <!-- 1. Date Visited -->
                <td class="py-3 px-4">
                  <div class="font-medium text-neutral-900 text-xs">
                    {{ formatDate(item.scheduled_date) }}
                  </div>
                  <div class="font-mono text-[10px] text-neutral-400">
                    {{ item.schedule_code }}
                  </div>
                </td>

                <!-- 2. Store Visited -->
                <td class="py-3 px-4">
                  <div class="font-medium text-neutral-900 text-xs">
                    {{ item.store?.store_name }}
                  </div>
                  <div class="flex items-center space-x-1 mt-0.5 text-neutral-400 font-mono text-[10px]">
                    <span>{{ item.store?.store_code }}</span>
                    <span>&bull;</span>
                    <span>{{ item.store?.area?.area_name }}</span>
                  </div>
                </td>

                <!-- 3. Time Started -->
                <td class="py-3 px-4">
                  <span v-if="item.started_at" class="font-mono text-neutral-900 text-xs">
                    {{ formatTime(item.started_at) }}
                  </span>
                  <span v-else class="text-neutral-400 font-mono text-[11px]">&mdash;</span>
                </td>

                <!-- 4. Time End -->
                <td class="py-3 px-4">
                  <span v-if="item.completed_at" class="font-mono text-neutral-900 text-xs">
                    {{ formatTime(item.completed_at) }}
                  </span>
                  <span v-else-if="item.status === 'in_progress'" class="text-neutral-700 font-mono text-[11px]">In Progress</span>
                  <span v-else class="text-neutral-400 font-mono text-[11px]">&mdash;</span>
                </td>

                <!-- Assigned Supervisor & Audit Type -->
                <td class="py-3 px-4">
                  <div class="font-medium text-neutral-800 text-xs">
                    {{ item.supervisor?.first_name }} {{ item.supervisor?.last_name }}
                  </div>
                  <div class="flex items-center space-x-1.5 mt-0.5 text-[10px] font-mono text-neutral-400">
                    <span class="uppercase">{{ item.audit_type }}</span>
                    <span>&bull;</span>
                    <span>{{ item.supervisor?.employee_id }}</span>
                  </div>
                </td>

                <!-- Status -->
                <td class="py-3 px-4">
                  <span
                    class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[11px] border"
                    :class="getStatusBadgeClass(item.status)"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(item.status)"></span>
                    <span>{{ formatStatus(item.status) }}</span>
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right">
                  <div class="inline-flex items-center space-x-1">
                    <!-- Supervisor Quick Execution Buttons -->
                    <button
                      v-if="isSupervisor && item.supervisor_id === user?.id && item.status === 'scheduled'"
                      type="button"
                      @click="triggerStatusUpdate(item, 'in_progress')"
                      class="px-2 py-1 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded transition-colors"
                    >
                      Start Visit
                    </button>

                    <button
                      v-if="isSupervisor && item.supervisor_id === user?.id && item.status === 'in_progress'"
                      type="button"
                      @click="triggerStatusUpdate(item, 'completed')"
                      class="px-2 py-1 text-xs font-medium border border-neutral-900 hover:bg-neutral-900 hover:text-white rounded transition-colors"
                    >
                      End Visit
                    </button>

                    <!-- View Details Modal -->
                    <button
                      type="button"
                      @click="openEventDetails(item)"
                      class="px-2 py-1 text-xs font-medium rounded border border-neutral-200 text-neutral-600 hover:bg-neutral-50 transition-colors"
                    >
                      Details
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="p-3 border-t border-neutral-200 bg-neutral-50 flex items-center justify-between text-xs text-neutral-500">
          <div>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} records</div>
          <div class="flex items-center space-x-1">
            <button :disabled="pagination.current_page <= 1" @click="changeListPage(pagination.current_page - 1)" class="px-2 py-1 rounded border border-neutral-200 bg-white disabled:opacity-40">Previous</button>
            <span class="px-2 font-mono">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="pagination.current_page >= pagination.last_page" @click="changeListPage(pagination.current_page + 1)" class="px-2 py-1 rounded border border-neutral-200 bg-white disabled:opacity-40">Next</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: SCHEDULE NEW VISIT                                      -->
    <!-- ============================================================= -->
    <div v-if="showScheduleModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">Schedule Store Audit Visit</h3>
          <button @click="showScheduleModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <form @submit.prevent="submitScheduleForm" class="space-y-3.5 mt-3 text-xs">
          <!-- Audit Type -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Audit Department Track <span class="text-neutral-400">*</span>
            </label>
            <select
              v-model="scheduleForm.audit_type"
              @change="loadSupervisorsForSchedule"
              :disabled="!isAdmin && auditType !== null"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
            >
              <option value="treasury">Treasury Audit</option>
              <option value="cashier">Cashier Audit</option>
            </select>
          </div>

          <!-- Store Selection -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Target Store Visited <span class="text-neutral-400">*</span>
            </label>
            <select
              v-model="scheduleForm.store_id"
              required
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
            >
              <option value="" disabled>Select store location...</option>
              <option v-for="st in storesDropdown" :key="st.id" :value="st.id">
                {{ st.store_name }} ({{ st.store_code }})
              </option>
            </select>
            <p v-if="scheduleErrors.store_id" class="mt-1 text-rose-600 text-[11px]">{{ scheduleErrors.store_id[0] }}</p>
          </div>

          <!-- Supervisor Selection -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Assigned Area Supervisor <span class="text-neutral-400">*</span>
            </label>
            <select
              v-model="scheduleForm.supervisor_id"
              required
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
            >
              <option value="" disabled>Select supervisor...</option>
              <option v-for="sup in eligibleSupervisors" :key="sup.id" :value="sup.id">
                {{ sup.first_name }} {{ sup.last_name }} ({{ sup.employee_id }})
              </option>
            </select>
            <p v-if="scheduleErrors.supervisor_id" class="mt-1 text-rose-600 text-[11px]">{{ scheduleErrors.supervisor_id[0] }}</p>
          </div>

          <!-- Scheduled Date Visited -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Date Visited <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model="scheduleForm.scheduled_date"
                type="date"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="scheduleErrors.scheduled_date" class="mt-1 text-rose-600 text-[11px]">{{ scheduleErrors.scheduled_date[0] }}</p>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                End Date (Optional)
              </label>
              <input
                v-model="scheduleForm.end_date"
                type="date"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Visit Instructions / Notes
            </label>
            <textarea
              v-model="scheduleForm.notes"
              rows="2"
              placeholder="e.g. Conduct full cash count and review safe compliance..."
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button type="button" @click="showScheduleModal = false" class="px-3 py-1.5 text-neutral-600 hover:text-neutral-900 text-xs font-medium">Cancel</button>
            <button type="submit" :disabled="isSubmittingSchedule" class="px-3.5 py-1.5 font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md text-xs transition-colors">
              {{ isSubmittingSchedule ? 'Scheduling...' : 'Confirm Schedule' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: EVENT DETAILS & STATUS UPDATE                          -->
    <!-- ============================================================= -->
    <div v-if="showDetailsModal && selectedEvent" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <div>
            <span class="text-[10px] font-mono uppercase tracking-wider px-1.5 py-0.5 rounded border border-neutral-200 bg-neutral-50 text-neutral-600">
              {{ selectedEvent.audit_type }} Audit
            </span>
            <h3 class="text-sm font-semibold text-neutral-900 mt-1">{{ selectedEvent.store?.store_name }}</h3>
          </div>
          <button @click="showDetailsModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <div class="space-y-3 my-3 text-xs">
          <!-- 4 Core Fields Grid -->
          <div class="grid grid-cols-2 gap-2.5 p-3 bg-neutral-50 rounded-md border border-neutral-200">
            <div>
              <span class="text-[10px] uppercase font-mono text-neutral-400 block">Date Visited</span>
              <span class="font-medium text-neutral-900 text-xs">{{ formatDate(selectedEvent.scheduled_date) }}</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-mono text-neutral-400 block">Store Visited</span>
              <span class="font-medium text-neutral-900 text-xs">{{ selectedEvent.store?.store_code }}</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-mono text-neutral-400 block">Time Started</span>
              <span class="font-mono text-neutral-900 text-xs">{{ selectedEvent.started_at ? formatTime(selectedEvent.started_at) : 'Not Started' }}</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-mono text-neutral-400 block">Time End</span>
              <span class="font-mono text-neutral-900 text-xs">{{ selectedEvent.completed_at ? formatTime(selectedEvent.completed_at) : 'In Progress' }}</span>
            </div>
          </div>

          <!-- Supervisor & Scheduler Info -->
          <div class="flex justify-between items-center text-neutral-600 text-xs">
            <div>
              <span class="text-neutral-400">Supervisor:</span>
              <span class="font-medium ml-1 text-neutral-800">{{ selectedEvent.supervisor?.first_name }} {{ selectedEvent.supervisor?.last_name }}</span>
            </div>
            <div>
              <span class="text-neutral-400">Scheduled by:</span>
              <span class="font-medium ml-1 text-neutral-700">{{ selectedEvent.scheduler?.first_name || 'System' }}</span>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedEvent.notes" class="p-2.5 bg-neutral-50 border border-neutral-200 rounded text-neutral-700 text-xs">
            <span class="font-medium text-neutral-900 block mb-0.5">Notes:</span>
            <p>{{ selectedEvent.notes }}</p>
          </div>

          <!-- Supervisor Execution Actions -->
          <div v-if="canExecuteVisit(selectedEvent)" class="p-3 bg-neutral-50 border border-neutral-200 rounded-md space-y-2">
            <span class="text-xs font-medium text-neutral-700 block">Audit Visit Controls:</span>
            <div class="space-y-2">
              <button
                type="button"
                @click="$emit('start-audit', selectedEvent.id)"
                class="w-full py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium rounded transition-colors flex items-center justify-center space-x-1.5"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>{{ selectedEvent.status === 'completed' ? 'Review Checklist & Answers' : 'Conduct Audit Checklist' }}</span>
              </button>

              <div class="flex items-center space-x-2">
                <button
                  v-if="selectedEvent.status === 'scheduled'"
                  type="button"
                  @click="triggerStatusUpdate(selectedEvent, 'in_progress')"
                  class="w-full py-1.5 border border-neutral-300 hover:bg-neutral-100 text-neutral-800 text-xs font-medium rounded transition-colors"
                >
                  Mark In Progress
                </button>
                <button
                  v-if="selectedEvent.status === 'in_progress'"
                  type="button"
                  @click="triggerStatusUpdate(selectedEvent, 'completed')"
                  class="w-full py-1.5 border border-neutral-300 hover:bg-neutral-100 text-neutral-800 text-xs font-medium rounded transition-colors"
                >
                  Mark Completed
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-100">
          <button type="button" @click="showDetailsModal = false" class="px-4 py-1.5 text-xs font-semibold text-slate-600">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { api } from '../services/api';
import { useAuth } from '../composables/useAuth';

const { user, isAdmin, isManager, isSupervisor, auditType, hasPermission } = useAuth();
const emit = defineEmits(['start-audit']);

const viewMode = ref('grid'); // 'grid' or 'list'
const toastMessage = ref('');
const toastType = ref('success');
const isLoading = ref(false);

const canScheduleVisits = computed(() => {
  return isAdmin.value || hasPermission('schedules.manage_treasury') || hasPermission('schedules.manage_cashier');
});

const toastClasses = computed(() => {
  return toastType.value === 'success'
    ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
    : 'bg-rose-50 border border-rose-200 text-rose-800';
});

function showToast(message, type = 'success') {
  toastMessage.value = message;
  toastType.value = type;
  setTimeout(() => {
    if (toastMessage.value === message) toastMessage.value = '';
  }, 4000);
}

// ---------------- PERIOD & CALENDAR STATE ----------------
const currentDate = ref(new Date());
const currentMonthYear = computed(() => {
  const y = currentDate.value.getFullYear();
  const m = String(currentDate.value.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}`;
});

const currentMonthFormatted = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric',
  });
});

function previousMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
  reloadSchedules();
}

function nextMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
  reloadSchedules();
}

function goToCurrentMonth() {
  currentDate.value = new Date();
  reloadSchedules();
}

// ---------------- FILTERS ----------------
const filterAuditType = ref(auditType.value || '');
const filterStatus = ref('all');
const filterAreaId = ref('');
const filterSearch = ref('');
const areasDropdown = ref([]);
const storesDropdown = ref([]);

// ---------------- STATS ----------------
const stats = reactive({
  total_visits: 0,
  scheduled: 0,
  in_progress: 0,
  completed: 0,
  cancelled: 0,
});

// ---------------- GRID VIEW EVENTS & CELLS ----------------
const gridEvents = ref([]);
const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();

  const firstDayOfMonth = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();

  const days = [];
  const todayStr = new Date().toISOString().split('T')[0];

  // Previous month trailing days
  for (let i = firstDayOfMonth - 1; i >= 0; i--) {
    const d = daysInPrevMonth - i;
    days.push({
      dayNumber: d,
      isCurrentMonth: false,
      isToday: false,
      dateStr: '',
      events: [],
    });
  }

  // Current month days
  for (let d = 1; d <= daysInMonth; d++) {
    const monthStr = String(month + 1).padStart(2, '0');
    const dayStr = String(d).padStart(2, '0');
    const fullDate = `${year}-${monthStr}-${dayStr}`;

    const matchingEvents = gridEvents.value.filter(ev => {
      const evDate = typeof ev.scheduled_date === 'string' ? ev.scheduled_date.split('T')[0] : '';
      return evDate === fullDate;
    });

    days.push({
      dayNumber: d,
      isCurrentMonth: true,
      isToday: fullDate === todayStr,
      dateStr: fullDate,
      events: matchingEvents,
    });
  }

  // Next month leading days (fill grid to multiple of 7)
  const remaining = (7 - (days.length % 7)) % 7;
  for (let i = 1; i <= remaining; i++) {
    days.push({
      dayNumber: i,
      isCurrentMonth: false,
      isToday: false,
      dateStr: '',
      events: [],
    });
  }

  return days;
});

// ---------------- LIST VIEW DATA ----------------
const listSchedules = ref([]);
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 });

async function loadGridEvents() {
  isLoading.value = true;
  try {
    const data = await api.get('/api/schedules', {
      all: 1,
      month: currentMonthYear.value,
      audit_type: filterAuditType.value,
      status: filterStatus.value,
      area_id: filterAreaId.value,
      search: filterSearch.value,
    });
    gridEvents.value = data.events || [];
    if (data.stats) Object.assign(stats, data.stats);
  } catch (err) {
    showToast(err.message || 'Failed to load grid calendar events', 'error');
  } finally {
    isLoading.value = false;
  }
}

async function loadListSchedules() {
  isLoading.value = true;
  try {
    const data = await api.get('/api/schedules', {
      month: currentMonthYear.value,
      audit_type: filterAuditType.value,
      status: filterStatus.value,
      area_id: filterAreaId.value,
      search: filterSearch.value,
      page: pagination.current_page,
    });
    listSchedules.value = data.schedules.data || [];
    pagination.current_page = data.schedules.current_page;
    pagination.last_page = data.schedules.last_page;
    pagination.per_page = data.schedules.per_page;
    pagination.total = data.schedules.total;
    pagination.from = data.schedules.from || 0;
    pagination.to = data.schedules.to || 0;

    if (data.stats) Object.assign(stats, data.stats);
  } catch (err) {
    showToast(err.message || 'Failed to load list view records', 'error');
  } finally {
    isLoading.value = false;
  }
}

function reloadSchedules() {
  if (viewMode.value === 'grid') {
    loadGridEvents();
  } else {
    loadListSchedules();
  }
}

function switchViewMode(mode) {
  viewMode.value = mode;
  reloadSchedules();
}

function changeListPage(page) {
  pagination.current_page = page;
  loadListSchedules();
}

// ---------------- SCHEDULE MODAL ----------------
const showScheduleModal = ref(false);
const isSubmittingSchedule = ref(false);
const scheduleErrors = ref({});
const eligibleSupervisors = ref([]);
const scheduleForm = reactive({
  store_id: '',
  supervisor_id: '',
  audit_type: auditType.value || 'treasury',
  scheduled_date: new Date().toISOString().split('T')[0],
  end_date: '',
  notes: '',
});

async function loadSupervisorsForSchedule() {
  try {
    eligibleSupervisors.value = await api.get('/api/stores/supervisors', {
      audit_type: scheduleForm.audit_type,
    });
    if (eligibleSupervisors.value.length) {
      scheduleForm.supervisor_id = eligibleSupervisors.value[0].id;
    }
  } catch (err) {
    console.error('Failed to load supervisors for schedule modal', err);
  }
}

async function openScheduleModal() {
  scheduleErrors.value = {};
  scheduleForm.audit_type = auditType.value || 'treasury';
  scheduleForm.store_id = storesDropdown.value[0]?.id || '';
  scheduleForm.scheduled_date = new Date().toISOString().split('T')[0];
  scheduleForm.end_date = '';
  scheduleForm.notes = '';
  await loadSupervisorsForSchedule();
  showScheduleModal.value = true;
}

async function submitScheduleForm() {
  isSubmittingSchedule.value = true;
  scheduleErrors.value = {};
  try {
    const res = await api.post('/api/schedules', scheduleForm);
    showToast(res.message || 'Visit scheduled successfully.');
    showScheduleModal.value = false;
    reloadSchedules();
  } catch (err) {
    if (err.status === 422) {
      scheduleErrors.value = err.errors || {};
    } else {
      showToast(err.message || 'Failed to schedule visit.', 'error');
    }
  } finally {
    isSubmittingSchedule.value = false;
  }
}

// ---------------- DETAILS / STATUS MODAL ----------------
const showDetailsModal = ref(false);
const selectedEvent = ref(null);

function openEventDetails(event) {
  selectedEvent.value = event;
  showDetailsModal.value = true;
}

function canExecuteVisit(event) {
  return isSupervisor.value && event.supervisor_id === user.value?.id;
}

async function triggerStatusUpdate(event, newStatus) {
  try {
    const res = await api.post(`/api/schedules/${event.id}/status`, {
      status: newStatus,
    });
    showToast(res.message || `Visit updated to ${newStatus}.`);
    showDetailsModal.value = false;
    reloadSchedules();
  } catch (err) {
    showToast(err.message || 'Failed to update visit status.', 'error');
  }
}

// ---------------- HELPERS ----------------
function formatDate(dateStr) {
  if (!dateStr) return '—';
  try {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  } catch {
    return dateStr;
  }
}

function formatTime(isoStr) {
  if (!isoStr) return '—';
  try {
    return new Date(isoStr).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return isoStr;
  }
}

function formatStatus(status) {
  switch (status) {
    case 'in_progress': return 'In Progress';
    case 'completed': return 'Completed';
    case 'cancelled': return 'Cancelled';
    default: return 'Scheduled';
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'in_progress': return 'bg-neutral-50 text-neutral-800 border-neutral-300';
    case 'completed': return 'bg-neutral-50 text-neutral-800 border-neutral-300';
    case 'cancelled': return 'bg-neutral-50 text-neutral-400 border-neutral-200';
    default: return 'bg-neutral-50 text-neutral-700 border-neutral-200';
  }
}

function getStatusDotClass(status) {
  switch (status) {
    case 'in_progress': return 'bg-amber-500';
    case 'completed': return 'bg-emerald-600';
    case 'cancelled': return 'bg-neutral-400';
    default: return 'bg-neutral-500';
  }
}

onMounted(async () => {
  try {
    areasDropdown.value = await api.get('/api/areas', { all: 1 });
    storesDropdown.value = await api.get('/api/stores', { all: 1 });
  } catch (err) {
    console.error('Failed to load dropdown dependencies', err);
  }
  reloadSchedules();
});
</script>


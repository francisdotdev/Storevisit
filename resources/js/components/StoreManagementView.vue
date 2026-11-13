<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Page Header & Tab Switcher -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Stores &amp; Areas &bull; Master Data
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            Store &amp; Area Management
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Configure geographic areas, manage store branches, and assign Area Supervisors for audit visits.
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <!-- View Switcher (Stores vs Areas) -->
          <div class="bg-neutral-100 p-0.5 rounded-md flex items-center space-x-0.5">
            <button
              type="button"
              @click="activeSubTab = 'stores'"
              :class="[
                'px-3 py-1.5 rounded text-xs font-medium transition-all',
                activeSubTab === 'stores'
                  ? 'bg-white text-neutral-900 shadow-xs'
                  : 'text-neutral-500 hover:text-neutral-900'
              ]"
            >
              Stores ({{ storeStats.total }})
            </button>
            <button
              type="button"
              @click="activeSubTab = 'areas'"
              :class="[
                'px-3 py-1.5 rounded text-xs font-medium transition-all',
                activeSubTab === 'areas'
                  ? 'bg-white text-neutral-900 shadow-xs'
                  : 'text-neutral-500 hover:text-neutral-900'
              ]"
            >
              Areas ({{ areaStats.total }})
            </button>
          </div>

          <!-- Add Button (Contextual) -->
          <button
            v-if="activeSubTab === 'stores' && canManageStores"
            type="button"
            @click="openAddStoreModal"
            class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Store</span>
          </button>

          <button
            v-if="activeSubTab === 'areas' && canManageAreas"
            type="button"
            @click="openAddAreaModal"
            class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Area</span>
          </button>
        </div>
      </div>

      <!-- Action Toast Banner -->
      <div v-if="toastMessage" :class="toastClasses" class="p-3 rounded-md flex items-center justify-between text-xs transition-all border">
        <div class="flex items-center space-x-2">
          <span>{{ toastMessage }}</span>
        </div>
        <button type="button" @click="toastMessage = ''" class="text-neutral-400 hover:text-neutral-600">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- ============================================================= -->
      <!-- TAB 1: STORES MANAGEMENT                                      -->
      <!-- ============================================================= -->
      <div v-if="activeSubTab === 'stores'" class="space-y-4">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Total Stores</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ storeStats.total }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Active Branches</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ storeStats.active }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Inactive Branches</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ storeStats.inactive }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Geographic Areas</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ storeStats.total_areas }}</p>
          </div>
        </div>

        <!-- Supervisor Scoping Notice -->
        <div v-if="isSupervisor" class="p-3 rounded-md bg-neutral-50 border border-neutral-200 text-neutral-700 text-xs flex items-center space-x-2">
          <span class="w-1.5 h-1.5 rounded-full bg-neutral-500"></span>
          <div>
            <span class="font-medium">Authorized Scope Active:</span>
            Displaying only stores assigned to you for <span class="font-mono uppercase">{{ auditType }}</span> audits by your Assistant Manager.
          </div>
        </div>

        <!-- Filters & Search Toolbar -->
        <div class="bg-white rounded-lg border border-neutral-200 p-3 flex flex-col md:flex-row md:items-center justify-between gap-3">
          <!-- Status Tabs -->
          <div class="flex items-center space-x-0.5 border border-neutral-200 rounded-md p-0.5 bg-neutral-100">
            <button
              type="button"
              v-for="st in ['all', 'active', 'inactive']"
              :key="st"
              @click="storeFilterStatus = st; loadStores()"
              :class="[
                'px-2.5 py-1 rounded text-xs font-medium capitalize transition-all',
                storeFilterStatus === st ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-500 hover:text-neutral-900'
              ]"
            >
              {{ st }}
            </button>
          </div>

          <!-- Area Dropdown & Search -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <select
              v-model="storeFilterAreaId"
              @change="loadStores"
              class="px-2.5 py-1 text-xs rounded border border-neutral-200 bg-white text-neutral-700 focus:border-neutral-900 focus:ring-0"
            >
              <option value="">All Areas</option>
              <option v-for="a in areasDropdown" :key="a.id" :value="a.id">
                {{ a.area_name }} ({{ a.area_code }})
              </option>
            </select>

            <div class="relative min-w-[200px]">
              <input
                v-model="storeSearch"
                @keydown.enter="loadStores"
                type="text"
                placeholder="Search store code, name..."
                class="w-full pl-7 pr-2.5 py-1 text-xs rounded border border-neutral-200 focus:border-neutral-900 focus:ring-0"
              />
              <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-neutral-400">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>

            <button
              type="button"
              @click="loadStores"
              class="px-3 py-1 bg-neutral-900 hover:bg-neutral-800 text-white rounded text-xs font-medium transition-colors"
            >
              Search
            </button>
          </div>
        </div>

        <!-- Stores Table -->
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-mono uppercase tracking-wider text-[10px]">
                  <th class="py-2.5 px-4">Store Code &amp; Name</th>
                  <th class="py-2.5 px-4">Area Location</th>
                  <th class="py-2.5 px-4">Assigned Supervisors</th>
                  <th class="py-2.5 px-4">Status</th>
                  <th class="py-2.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-neutral-100">
                <tr v-if="isStoresLoading" class="text-center py-10">
                  <td colspan="5" class="py-10 text-neutral-400">Loading stores data...</td>
                </tr>

                <tr v-else-if="stores.length === 0" class="text-center py-10">
                  <td colspan="5" class="py-10 text-neutral-400">No store records found.</td>
                </tr>

                <tr v-for="s in stores" :key="s.id" class="hover:bg-neutral-50/50 transition-colors">
                  <!-- Store Code & Name -->
                  <td class="py-3 px-4">
                    <div class="font-medium text-neutral-900 text-xs">{{ s.store_name }}</div>
                    <div class="font-mono text-neutral-400 text-[10px] mt-0.5">{{ s.store_code }}</div>
                  </td>

                  <!-- Area Location -->
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono border border-neutral-200 bg-neutral-50 text-neutral-700">
                      {{ s.area?.area_name }}
                    </span>
                  </td>

                  <!-- Assigned Supervisors -->
                  <td class="py-3 px-4">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <!-- Treasury Supervisor Badge -->
                      <span
                        v-if="getStoreAssignment(s, 'treasury')"
                        class="inline-flex items-center space-x-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-neutral-200 bg-neutral-50 text-neutral-700"
                        :title="'Assigned by: ' + (getStoreAssignment(s, 'treasury').assigner?.first_name || 'Manager')"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-500"></span>
                        <span>TR: {{ getStoreAssignment(s, 'treasury').supervisor?.first_name }} {{ getStoreAssignment(s, 'treasury').supervisor?.last_name }}</span>
                      </span>

                      <!-- Cashier Supervisor Badge -->
                      <span
                        v-if="getStoreAssignment(s, 'cashier')"
                        class="inline-flex items-center space-x-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-neutral-200 bg-neutral-50 text-neutral-700"
                        :title="'Assigned by: ' + (getStoreAssignment(s, 'cashier').assigner?.first_name || 'Manager')"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-500"></span>
                        <span>CS: {{ getStoreAssignment(s, 'cashier').supervisor?.first_name }} {{ getStoreAssignment(s, 'cashier').supervisor?.last_name }}</span>
                      </span>

                      <span v-if="!s.supervisor_assignments || s.supervisor_assignments.length === 0" class="text-neutral-400 text-[11px] font-mono">
                        &mdash;
                      </span>
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="py-3 px-4">
                    <span
                      class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[11px] border"
                      :class="s.is_active ? 'bg-neutral-50 text-neutral-800 border-neutral-300' : 'bg-neutral-50 text-neutral-400 border-neutral-200'"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="s.is_active ? 'bg-emerald-600' : 'bg-neutral-400'"></span>
                      <span>{{ s.is_active ? 'Active' : 'Inactive' }}</span>
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="py-3 px-4 text-right">
                    <div class="inline-flex items-center space-x-1">
                      <!-- Assign Supervisor (Asst Mgrs or Admin) -->
                      <button
                        v-if="canAssignSupervisors"
                        type="button"
                        @click="openAssignModal(s)"
                        class="px-2 py-1 text-xs font-medium rounded border border-neutral-200 text-neutral-700 hover:bg-neutral-50 transition-colors"
                        title="Assign Area Supervisor to store"
                      >
                        Assign
                      </button>

                      <!-- Toggle Status -->
                      <button
                        v-if="canManageStores"
                        type="button"
                        @click="toggleStoreStatus(s)"
                        class="px-2 py-1 text-xs font-medium rounded border border-neutral-200 text-neutral-600 hover:bg-neutral-50 transition-colors"
                      >
                        {{ s.is_active ? 'Deactivate' : 'Activate' }}
                      </button>

                      <!-- Edit Store -->
                      <button
                        v-if="canManageStores"
                        type="button"
                        @click="openEditStoreModal(s)"
                        class="p-1 text-neutral-400 hover:text-neutral-900 rounded"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Store Pagination -->
          <div v-if="storePagination.total > storePagination.per_page" class="p-3 border-t border-neutral-200 bg-neutral-50 flex items-center justify-between text-xs text-neutral-500">
            <div>Showing {{ storePagination.from }} to {{ storePagination.to }} of {{ storePagination.total }} stores</div>
            <div class="flex items-center space-x-1">
              <button :disabled="storePagination.current_page <= 1" @click="changeStorePage(storePagination.current_page - 1)" class="px-2 py-1 rounded border border-neutral-200 bg-white disabled:opacity-40">Previous</button>
              <span class="px-2 font-mono">{{ storePagination.current_page }} / {{ storePagination.last_page }}</span>
              <button :disabled="storePagination.current_page >= storePagination.last_page" @click="changeStorePage(storePagination.current_page + 1)" class="px-2 py-1 rounded border border-neutral-200 bg-white disabled:opacity-40">Next</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================= -->
      <!-- TAB 2: AREAS MANAGEMENT                                       -->
      <!-- ============================================================= -->
      <div v-else-if="activeSubTab === 'areas'" class="space-y-4">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Total Areas</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ areaStats.total }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Active Areas</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ areaStats.active }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Inactive Areas</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ areaStats.inactive }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Total Stores</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ areaStats.total_stores }}</p>
          </div>
        </div>

        <!-- Areas Table -->
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-mono uppercase tracking-wider text-[10px]">
                  <th class="py-2.5 px-4">Area Code</th>
                  <th class="py-2.5 px-4">Area Name</th>
                  <th class="py-2.5 px-4">Stores Count</th>
                  <th class="py-2.5 px-4">Status</th>
                  <th class="py-2.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-neutral-100">
                <tr v-if="isAreasLoading" class="text-center py-10">
                  <td colspan="5" class="py-10 text-neutral-400">Loading areas data...</td>
                </tr>

                <tr v-else-if="areas.length === 0" class="text-center py-10">
                  <td colspan="5" class="py-10 text-neutral-400">No area records found.</td>
                </tr>

                <tr v-for="a in areas" :key="a.id" class="hover:bg-neutral-50/50 transition-colors">
                  <!-- Area Code -->
                  <td class="py-3 px-4 font-mono font-medium text-neutral-800">
                    {{ a.area_code }}
                  </td>

                  <!-- Area Name -->
                  <td class="py-3 px-4 font-medium text-neutral-900 text-xs">
                    {{ a.area_name }}
                  </td>

                  <!-- Stores Count -->
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono border border-neutral-200 bg-neutral-50 text-neutral-600">
                      {{ a.stores_count }} stores
                    </span>
                  </td>

                  <!-- Status -->
                  <td class="py-3 px-4">
                    <span
                      class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[11px] border"
                      :class="a.is_active ? 'bg-neutral-50 text-neutral-800 border-neutral-300' : 'bg-neutral-50 text-neutral-400 border-neutral-200'"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="a.is_active ? 'bg-emerald-600' : 'bg-neutral-400'"></span>
                      <span>{{ a.is_active ? 'Active' : 'Inactive' }}</span>
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="py-3 px-4 text-right">
                    <div class="inline-flex items-center space-x-1">
                      <button
                        v-if="canManageAreas"
                        type="button"
                        @click="toggleAreaStatus(a)"
                        class="px-2 py-1 text-xs font-medium rounded border border-neutral-200 text-neutral-600 hover:bg-neutral-50 transition-colors"
                      >
                        {{ a.is_active ? 'Deactivate' : 'Activate' }}
                      </button>

                      <button
                        v-if="canManageAreas"
                        type="button"
                        @click="openEditAreaModal(a)"
                        class="p-1 text-neutral-400 hover:text-neutral-900 rounded"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: ADD / EDIT STORE                                        -->
    <!-- ============================================================= -->
    <div v-if="showStoreModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">{{ isEditingStore ? 'Edit Store Branch' : 'Add New Store Branch' }}</h3>
          <button @click="showStoreModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <form @submit.prevent="submitStoreForm" class="space-y-3.5 mt-3 text-xs">
          <!-- Area Selection -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Select Area Location <span class="text-neutral-400">*</span>
            </label>
            <select
              v-model="storeForm.area_id"
              required
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
            >
              <option value="" disabled>Choose area location...</option>
              <option v-for="a in areasDropdown" :key="a.id" :value="a.id">
                {{ a.area_name }} ({{ a.area_code }})
              </option>
            </select>
            <p v-if="storeErrors.area_id" class="mt-1 text-[11px] text-rose-600">{{ storeErrors.area_id[0] }}</p>
          </div>

          <!-- Store Code -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Store Code <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="storeForm.store_code"
              type="text"
              required
              placeholder="e.g. STR-NAGA-01"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="storeErrors.store_code" class="mt-1 text-[11px] text-rose-600">{{ storeErrors.store_code[0] }}</p>
          </div>

          <!-- Store Name -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Store Name <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="storeForm.store_name"
              type="text"
              required
              placeholder="e.g. LCC Supermarket Naga"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="storeErrors.store_name" class="mt-1 text-[11px] text-rose-600">{{ storeErrors.store_name[0] }}</p>
          </div>

          <!-- Status -->
          <div class="flex items-center space-x-2 pt-1">
            <input
              id="store_active"
              v-model="storeForm.is_active"
              type="checkbox"
              class="w-3.5 h-3.5 rounded border-neutral-300 text-neutral-900 focus:ring-0"
            />
            <label for="store_active" class="text-xs text-neutral-700">Active branch</label>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button type="button" @click="showStoreModal = false" class="px-3 py-1.5 text-xs text-neutral-600 hover:text-neutral-900">Cancel</button>
            <button type="submit" :disabled="isSubmittingStore" class="px-3.5 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors">
              {{ isSubmittingStore ? 'Saving...' : 'Save Store' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: ADD / EDIT AREA                                         -->
    <!-- ============================================================= -->
    <div v-if="showAreaModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">{{ isEditingArea ? 'Edit Geographic Area' : 'Add Geographic Area' }}</h3>
          <button @click="showAreaModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <form @submit.prevent="submitAreaForm" class="space-y-3.5 mt-3 text-xs">
          <!-- Area Code -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Area Code <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="areaForm.area_code"
              type="text"
              required
              placeholder="e.g. AREA-CAM-SUR"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="areaErrors.area_code" class="mt-1 text-[11px] text-rose-600">{{ areaErrors.area_code[0] }}</p>
          </div>

          <!-- Area Name -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Area Name <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="areaForm.area_name"
              type="text"
              required
              placeholder="e.g. Camarines Sur Area"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="areaErrors.area_name" class="mt-1 text-[11px] text-rose-600">{{ areaErrors.area_name[0] }}</p>
          </div>

          <!-- Status -->
          <div class="flex items-center space-x-2 pt-1">
            <input
              id="area_active"
              v-model="areaForm.is_active"
              type="checkbox"
              class="w-3.5 h-3.5 rounded border-neutral-300 text-neutral-900 focus:ring-0"
            />
            <label for="area_active" class="text-xs text-neutral-700">Active area</label>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button type="button" @click="showAreaModal = false" class="px-3 py-1.5 text-xs text-neutral-600 hover:text-neutral-900">Cancel</button>
            <button type="submit" :disabled="isSubmittingArea" class="px-3.5 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors">
              {{ isSubmittingArea ? 'Saving...' : 'Save Area' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: ASSIGN SUPERVISOR TO STORE                             -->
    <!-- ============================================================= -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <div>
            <h3 class="text-sm font-semibold text-neutral-900">Assign Supervisor to Store</h3>
            <p class="text-xs text-neutral-500 font-mono">{{ selectedStoreForAssign?.store_name }} ({{ selectedStoreForAssign?.store_code }})</p>
          </div>
          <button @click="showAssignModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <div class="space-y-3.5 mt-3 text-xs">
          <!-- Existing Assignments in this Store -->
          <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200 text-xs">
            <span class="font-medium text-neutral-800 block mb-2 text-xs">Current Store Assignments:</span>
            <div v-if="selectedStoreForAssign?.supervisor_assignments?.length" class="space-y-1.5">
              <div
                v-for="asg in selectedStoreForAssign.supervisor_assignments"
                :key="asg.id"
                class="flex items-center justify-between bg-white p-2 rounded border border-neutral-200"
              >
                <div>
                  <span class="font-mono uppercase text-[10px] px-1.5 py-0.2 rounded mr-1 bg-neutral-100 border border-neutral-200 text-neutral-700">
                    {{ asg.audit_type }}
                  </span>
                  <span class="font-medium text-neutral-800">{{ asg.supervisor?.first_name }} {{ asg.supervisor?.last_name }}</span>
                  <span class="text-neutral-400 font-mono ml-1">({{ asg.supervisor?.employee_id }})</span>
                </div>
                <button
                  type="button"
                  @click="removeAssignment(selectedStoreForAssign, asg)"
                  class="text-neutral-500 hover:text-neutral-900 text-xs font-medium"
                >
                  Remove
                </button>
              </div>
            </div>
            <div v-else class="text-neutral-400 text-xs">No supervisors currently assigned to this store.</div>
          </div>

          <!-- New Assignment Form -->
          <form @submit.prevent="submitAssignSupervisor" class="space-y-3 pt-1">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Audit Department Type <span class="text-neutral-400">*</span>
              </label>
              <select
                v-model="assignForm.audit_type"
                @change="loadEligibleSupervisors"
                :disabled="!isAdmin && auditType !== null"
                class="w-full px-2.5 py-1.5 text-xs rounded-md border border-neutral-200 bg-white focus:border-neutral-900 focus:ring-0"
              >
                <option value="treasury">Treasury</option>
                <option value="cashier">Cashier</option>
              </select>
            </div>

            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Select Area Supervisor <span class="text-neutral-400">*</span>
              </label>
              <select
                v-model="assignForm.supervisor_id"
                required
                class="w-full px-2.5 py-1.5 text-xs rounded-md border border-neutral-200 bg-white focus:border-neutral-900 focus:ring-0"
              >
                <option value="" disabled>Choose supervisor...</option>
                <option v-for="sup in eligibleSupervisors" :key="sup.id" :value="sup.id">
                  {{ sup.first_name }} {{ sup.last_name }} ({{ sup.employee_id }})
                </option>
              </select>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
              <button type="button" @click="showAssignModal = false" class="px-3 py-1.5 text-xs text-neutral-600 hover:text-neutral-900">Close</button>
              <button type="submit" :disabled="isSubmittingAssign" class="px-3.5 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors">
                {{ isSubmittingAssign ? 'Assigning...' : 'Confirm Assignment' }}
              </button>
            </div>
          </form>
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

const activeSubTab = ref('stores');
const toastMessage = ref('');
const toastType = ref('success');

const canManageStores = computed(() => hasPermission('stores.manage') || isAdmin.value);
const canManageAreas = computed(() => hasPermission('areas.manage') || isAdmin.value);
const canAssignSupervisors = computed(() => {
  return isAdmin.value || hasPermission('store_assignments.treasury') || hasPermission('store_assignments.cashier');
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

// ---------------- STORES STATE ----------------
const stores = ref([]);
const isStoresLoading = ref(false);
const storeSearch = ref('');
const storeFilterAreaId = ref('');
const storeFilterStatus = ref('all');
const storeStats = reactive({ total: 0, active: 0, inactive: 0, total_areas: 0 });
const storePagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 });

async function loadStores() {
  isStoresLoading.value = true;
  try {
    const data = await api.get('/api/stores', {
      search: storeSearch.value,
      area_id: storeFilterAreaId.value,
      status: storeFilterStatus.value,
      page: storePagination.current_page,
    });
    stores.value = data.stores.data || [];
    storePagination.current_page = data.stores.current_page;
    storePagination.last_page = data.stores.last_page;
    storePagination.per_page = data.stores.per_page;
    storePagination.total = data.stores.total;
    storePagination.from = data.stores.from || 0;
    storePagination.to = data.stores.to || 0;

    if (data.stats) {
      storeStats.total = data.stats.total;
      storeStats.active = data.stats.active;
      storeStats.inactive = data.stats.inactive;
      storeStats.total_areas = data.stats.total_areas;
    }
  } catch (err) {
    showToast(err.message || 'Failed to load stores', 'error');
  } finally {
    isStoresLoading.value = false;
  }
}

function changeStorePage(page) {
  storePagination.current_page = page;
  loadStores();
}

function getStoreAssignment(store, type) {
  if (!store.supervisor_assignments) return null;
  return store.supervisor_assignments.find(a => a.audit_type === type) || null;
}

// ---------------- AREAS STATE ----------------
const areas = ref([]);
const areasDropdown = ref([]);
const isAreasLoading = ref(false);
const areaStats = reactive({ total: 0, active: 0, inactive: 0, total_stores: 0 });

async function loadAreas() {
  isAreasLoading.value = true;
  try {
    const data = await api.get('/api/areas');
    areas.value = data.areas.data || [];
    if (data.stats) {
      areaStats.total = data.stats.total;
      areaStats.active = data.stats.active;
      areaStats.inactive = data.stats.inactive;
      areaStats.total_stores = data.stats.total_stores;
    }
  } catch (err) {
    showToast(err.message || 'Failed to load areas', 'error');
  } finally {
    isAreasLoading.value = false;
  }
}

async function loadAreasDropdown() {
  try {
    areasDropdown.value = await api.get('/api/areas', { all: 1 });
  } catch (err) {
    console.error('Failed to load area dropdown', err);
  }
}

// ---------------- STORE MODAL ----------------
const showStoreModal = ref(false);
const isEditingStore = ref(false);
const editingStoreId = ref(null);
const isSubmittingStore = ref(false);
const storeErrors = ref({});
const storeForm = reactive({ area_id: '', store_code: '', store_name: '', is_active: true });

function openAddStoreModal() {
  isEditingStore.value = false;
  editingStoreId.value = null;
  storeErrors.value = {};
  storeForm.area_id = areasDropdown.value[0]?.id || '';
  storeForm.store_code = '';
  storeForm.store_name = '';
  storeForm.is_active = true;
  showStoreModal.value = true;
}

function openEditStoreModal(store) {
  isEditingStore.value = true;
  editingStoreId.value = store.id;
  storeErrors.value = {};
  storeForm.area_id = store.area_id;
  storeForm.store_code = store.store_code;
  storeForm.store_name = store.store_name;
  storeForm.is_active = store.is_active;
  showStoreModal.value = true;
}

async function submitStoreForm() {
  isSubmittingStore.value = true;
  storeErrors.value = {};
  try {
    if (isEditingStore.value) {
      const res = await api.put(`/api/stores/${editingStoreId.value}`, storeForm);
      showToast(res.message || 'Store updated successfully.');
    } else {
      const res = await api.post('/api/stores', storeForm);
      showToast(res.message || 'Store created successfully.');
    }
    showStoreModal.value = false;
    loadStores();
  } catch (err) {
    if (err.status === 422) {
      storeErrors.value = err.errors || {};
    } else {
      showToast(err.message || 'Failed to save store.', 'error');
    }
  } finally {
    isSubmittingStore.value = false;
  }
}

async function toggleStoreStatus(store) {
  const action = store.is_active ? 'deactivate' : 'activate';
  if (!confirm(`Are you sure you want to ${action} ${store.store_name}?`)) return;
  try {
    const res = await api.post(`/api/stores/${store.id}/toggle-status`);
    showToast(res.message);
    loadStores();
  } catch (err) {
    showToast(err.message || 'Failed to change store status', 'error');
  }
}

// ---------------- AREA MODAL ----------------
const showAreaModal = ref(false);
const isEditingArea = ref(false);
const editingAreaId = ref(null);
const isSubmittingArea = ref(false);
const areaErrors = ref({});
const areaForm = reactive({ area_code: '', area_name: '', is_active: true });

function openAddAreaModal() {
  isEditingArea.value = false;
  editingAreaId.value = null;
  areaErrors.value = {};
  areaForm.area_code = '';
  areaForm.area_name = '';
  areaForm.is_active = true;
  showAreaModal.value = true;
}

function openEditAreaModal(area) {
  isEditingArea.value = true;
  editingAreaId.value = area.id;
  areaErrors.value = {};
  areaForm.area_code = area.area_code;
  areaForm.area_name = area.area_name;
  areaForm.is_active = area.is_active;
  showAreaModal.value = true;
}

async function submitAreaForm() {
  isSubmittingArea.value = true;
  areaErrors.value = {};
  try {
    if (isEditingArea.value) {
      const res = await api.put(`/api/areas/${editingAreaId.value}`, areaForm);
      showToast(res.message || 'Area updated successfully.');
    } else {
      const res = await api.post('/api/areas', areaForm);
      showToast(res.message || 'Area created successfully.');
    }
    showAreaModal.value = false;
    loadAreas();
    loadAreasDropdown();
  } catch (err) {
    if (err.status === 422) {
      areaErrors.value = err.errors || {};
    } else {
      showToast(err.message || 'Failed to save area.', 'error');
    }
  } finally {
    isSubmittingArea.value = false;
  }
}

async function toggleAreaStatus(area) {
  const action = area.is_active ? 'deactivate' : 'activate';
  if (!confirm(`Are you sure you want to ${action} ${area.area_name}?`)) return;
  try {
    const res = await api.post(`/api/areas/${area.id}/toggle-status`);
    showToast(res.message);
    loadAreas();
    loadAreasDropdown();
  } catch (err) {
    showToast(err.message || 'Failed to change area status', 'error');
  }
}

// ---------------- SUPERVISOR ASSIGNMENT MODAL ----------------
const showAssignModal = ref(false);
const selectedStoreForAssign = ref(null);
const eligibleSupervisors = ref([]);
const isSubmittingAssign = ref(false);
const assignForm = reactive({ supervisor_id: '', audit_type: 'treasury' });

async function loadEligibleSupervisors() {
  try {
    eligibleSupervisors.value = await api.get('/api/stores/supervisors', {
      audit_type: assignForm.audit_type,
    });
    assignForm.supervisor_id = eligibleSupervisors.value[0]?.id || '';
  } catch (err) {
    console.error('Failed to load eligible supervisors', err);
  }
}

async function openAssignModal(store) {
  selectedStoreForAssign.value = store;
  // Default audit type to user's department scope if assistant manager
  if (auditType.value) {
    assignForm.audit_type = auditType.value;
  } else {
    assignForm.audit_type = 'treasury';
  }
  await loadEligibleSupervisors();
  showAssignModal.value = true;
}

async function submitAssignSupervisor() {
  if (!selectedStoreForAssign.value) return;
  isSubmittingAssign.value = true;
  try {
    const res = await api.post(`/api/stores/${selectedStoreForAssign.value.id}/assign-supervisor`, assignForm);
    showToast(res.message || 'Supervisor assigned successfully.');
    showAssignModal.value = false;
    loadStores();
  } catch (err) {
    showToast(err.message || 'Failed to assign supervisor.', 'error');
  } finally {
    isSubmittingAssign.value = false;
  }
}

async function removeAssignment(store, assignment) {
  if (!confirm(`Remove ${assignment.supervisor?.first_name}'s assignment from ${store.store_name}?`)) return;
  try {
    const res = await api.delete(`/api/stores/${store.id}/assignments/${assignment.id}`);
    showToast(res.message);
    showAssignModal.value = false;
    loadStores();
  } catch (err) {
    showToast(err.message || 'Failed to remove assignment.', 'error');
  }
}

onMounted(() => {
  loadAreasDropdown();
  loadStores();
  loadAreas();
});
</script>


<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            User Management &bull; Admin
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            User Account Management
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Manage system users, review pending registrations, assign roles, and control access permissions.
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <button
            type="button"
            @click="loadUsers"
            :disabled="isLoading"
            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md border border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 text-xs font-medium transition-colors"
          >
            <svg :class="{ 'animate-spin': isLoading }" class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Refresh</span>
          </button>

          <button
            v-if="activeMainTab === 'users'"
            type="button"
            @click="openAddModal"
            class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add User</span>
          </button>
        </div>
      </div>

      <!-- Tab Switcher (Admin Only) -->
      <div class="flex items-center space-x-4 border-b border-neutral-200">
        <button
          type="button"
          @click="activeMainTab = 'users'"
          :class="[
            'pb-2.5 text-xs font-medium border-b-2 transition-colors',
            activeMainTab === 'users'
              ? 'border-neutral-900 text-neutral-900 font-semibold'
              : 'border-transparent text-neutral-500 hover:text-neutral-900'
          ]"
        >
          User Accounts
        </button>
        <button
          type="button"
          @click="activeMainTab = 'roles'"
          :class="[
            'pb-2.5 text-xs font-medium border-b-2 transition-colors',
            activeMainTab === 'roles'
              ? 'border-neutral-900 text-neutral-900 font-semibold'
              : 'border-transparent text-neutral-500 hover:text-neutral-900'
          ]"
        >
          Roles &amp; Access
        </button>
      </div>

      <!-- Toast Alert Notification -->
      <div
        v-if="toastMessage"
        class="p-3 rounded-lg border text-xs flex items-center justify-between transition-all"
        :class="toastClasses"
      >
        <span>{{ toastMessage }}</span>
        <button @click="toastMessage = ''" class="ml-4 font-bold opacity-60 hover:opacity-100">&times;</button>
      </div>

      <!-- MAIN TAB 1: USERS -->
      <div v-if="activeMainTab === 'users'" class="space-y-5">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Total Users</p>
            <p class="text-xl font-semibold text-neutral-900 mt-1">{{ stats.total }}</p>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Pending Review</p>
            <div class="flex items-center space-x-2 mt-1">
              <span class="text-xl font-semibold text-neutral-900">{{ stats.pending }}</span>
              <span v-if="stats.pending > 0" class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Active Accounts</p>
            <div class="flex items-center space-x-2 mt-1">
              <span class="text-xl font-semibold text-neutral-900">{{ stats.active }}</span>
              <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
            </div>
          </div>

          <div class="bg-white p-3.5 rounded-lg border border-neutral-200">
            <p class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">Disabled</p>
            <p class="text-xl font-semibold text-neutral-400 mt-1">{{ stats.disabled }}</p>
          </div>
        </div>

        <!-- Filters & Data Table Container -->
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden shadow-xs">
          <!-- Status Filter Tabs -->
          <div class="border-b border-neutral-100 px-4 pt-2.5 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center space-x-1">
              <button
                v-for="tab in statusTabs"
                :key="tab.value"
                type="button"
                @click="selectStatusFilter(tab.value)"
                :class="[
                  'px-3 py-2 text-xs font-medium border-b-2 transition-colors relative flex items-center space-x-1.5',
                  currentStatusFilter === tab.value
                    ? 'border-neutral-900 text-neutral-900 font-semibold'
                    : 'border-transparent text-neutral-500 hover:text-neutral-800'
                ]"
              >
                <span>{{ tab.label }}</span>
                <span class="text-[10px] font-mono text-neutral-400">({{ tab.count }})</span>
              </button>
            </div>

            <!-- Search and Role Dropdown -->
            <div class="flex items-center space-x-2 pb-2 sm:pb-0">
              <select
                v-model="selectedRoleId"
                @change="loadUsers"
                class="text-xs rounded-md border border-neutral-200 bg-white px-2.5 py-1.5 text-neutral-700 focus:border-neutral-900 focus:ring-0"
              >
                <option value="">All Roles</option>
                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name }}</option>
              </select>

              <div class="relative">
                <input
                  v-model.trim="searchQuery"
                  @keyup.enter="loadUsers"
                  type="text"
                  placeholder="Search users..."
                  class="text-xs rounded-md border border-neutral-200 pl-7 pr-3 py-1.5 text-neutral-900 placeholder-neutral-400 focus:border-neutral-900 focus:ring-0 w-44"
                />
                <svg class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500 font-medium uppercase tracking-wider text-[10px]">
                  <th class="py-2.5 px-4">Employee / Name</th>
                  <th class="py-2.5 px-4">Email Address</th>
                  <th class="py-2.5 px-4">Role / Department</th>
                  <th class="py-2.5 px-4">Branch Code</th>
                  <th class="py-2.5 px-4">Status</th>
                  <th class="py-2.5 px-4">Created</th>
                  <th class="py-2.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-neutral-100">
                <tr v-if="isLoading" class="text-center">
                  <td colspan="7" class="py-8 text-neutral-400">Loading user accounts...</td>
                </tr>
                <tr v-else-if="users.length === 0" class="text-center">
                  <td colspan="7" class="py-8 text-neutral-400">No users found matching the selected filter.</td>
                </tr>
                <tr
                  v-for="u in users"
                  :key="u.id"
                  class="hover:bg-neutral-50/60 transition-colors"
                >
                  <!-- Employee ID and Name -->
                  <td class="py-3 px-4">
                    <div class="font-medium text-neutral-900">{{ u.first_name }} {{ u.last_name }}</div>
                    <div class="text-[11px] text-neutral-400 font-mono mt-0.5">{{ u.employee_id }}</div>
                  </td>

                  <!-- Email -->
                  <td class="py-3 px-4 font-mono text-neutral-600">
                    {{ u.email }}
                  </td>

                  <!-- Role -->
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-neutral-100 text-neutral-800">
                      {{ u.role ? u.role.display_name : 'No role' }}
                    </span>
                  </td>

                  <!-- Branch Code -->
                  <td class="py-3 px-4 font-mono text-neutral-500">
                    {{ u.branch_code || '—' }}
                  </td>

                  <!-- Status Badge -->
                  <td class="py-3 px-4">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono capitalize"
                      :class="{
                        'bg-emerald-50 text-emerald-700': u.status === 'active',
                        'bg-amber-50 text-amber-700': u.status === 'pending',
                        'bg-neutral-100 text-neutral-400': u.status === 'disabled',
                      }"
                    >
                      {{ u.status }}
                    </span>
                  </td>

                  <!-- Created Date -->
                  <td class="py-3 px-4 text-neutral-400 text-[11px]">
                    {{ formatDate(u.created_at) }}
                  </td>

                  <!-- Row Actions -->
                  <td class="py-3 px-4 text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <!-- Approve Button (If pending) -->
                      <button
                        v-if="u.status === 'pending'"
                        type="button"
                        @click="approveUser(u)"
                        title="Approve user registration"
                        class="px-2 py-1 bg-neutral-900 hover:bg-neutral-800 text-white rounded text-[11px] font-medium transition-colors"
                      >
                        Approve
                      </button>

                      <!-- Edit Button -->
                      <button
                        type="button"
                        @click="openEditModal(u)"
                        title="Edit user details"
                        class="text-neutral-500 hover:text-neutral-900 p-1 rounded hover:bg-neutral-100 transition-colors"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                      </button>

                      <!-- Toggle Status (Activate / Disable) -->
                      <button
                        v-if="u.id !== currentUser?.id"
                        type="button"
                        @click="toggleUserStatus(u)"
                        :title="u.status === 'disabled' ? 'Enable Account' : 'Disable Account'"
                        class="text-neutral-400 hover:text-neutral-700 p-1 rounded hover:bg-neutral-100 transition-colors"
                      >
                        <svg v-if="u.status === 'disabled'" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.total > pagination.per_page" class="p-3 border-t border-neutral-100 bg-neutral-50/50 flex items-center justify-between text-xs text-neutral-500">
            <div>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} users</div>
            <div class="flex items-center space-x-1">
              <button :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)" class="px-2 py-1 rounded border border-neutral-200 bg-white disabled:opacity-40">Previous</button>
              <span class="px-2 font-mono">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
              <button :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)" class="px-2 py-1 rounded border border-neutral-200 bg-white disabled:opacity-40">Next</button>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN TAB 2: ROLES & ACCESS -->
      <div v-else class="space-y-4">
        <div class="bg-white p-4 rounded-lg border border-neutral-200">
          <h2 class="text-sm font-semibold text-neutral-900">Configured System Roles &amp; Permissions</h2>
          <p class="text-xs text-neutral-500 mt-0.5">Read-only overview of the 6 core business roles defined in the Store Visit Audit specification.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="r in rolesWithDescriptions" :key="r.name" class="bg-white p-4 rounded-lg border border-neutral-200 space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-neutral-100 text-neutral-600 border border-neutral-200">
                  {{ r.department }}
                </span>
                <h3 class="text-sm font-semibold text-neutral-900 mt-1.5">{{ r.display_name }}</h3>
                <span class="text-[11px] font-mono text-neutral-400">{{ r.name }}</span>
              </div>
              <div class="text-right">
                <span class="text-xs font-semibold text-neutral-900">{{ getUserCountForRole(r.name) }}</span>
                <div class="text-[10px] text-neutral-400">users</div>
              </div>
            </div>

            <p class="text-xs text-neutral-600 leading-relaxed">{{ r.description }}</p>

            <div class="pt-2 border-t border-neutral-100">
              <div class="text-[10px] font-mono uppercase text-neutral-400 mb-1">Scope &amp; Authority</div>
              <p class="text-xs text-neutral-700 font-medium">{{ r.scope_text }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD USER MODAL -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">Add New User Account</h3>
          <button @click="showAddModal = false" class="text-neutral-400 hover:text-neutral-600">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitAddUser" class="space-y-3.5 mt-3 text-xs">
          <!-- Employee ID -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Employee ID <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="addForm.employee_id"
              type="text"
              required
              placeholder="e.g. EMP00201"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="modalErrors.employee_id" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.employee_id[0] }}</p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                First Name <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="addForm.first_name"
                type="text"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="modalErrors.first_name" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.first_name[0] }}</p>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Last Name <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="addForm.last_name"
                type="text"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="modalErrors.last_name" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.last_name[0] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Email Address <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="addForm.email"
                type="email"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="modalErrors.email" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.email[0] }}</p>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Branch Code
              </label>
              <input
                v-model.trim="addForm.branch_code"
                type="text"
                placeholder="Optional"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Role / Position <span class="text-neutral-400">*</span>
              </label>
              <select
                v-model="addForm.role_id"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
              >
                <option value="">Select a role...</option>
                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name }}</option>
              </select>
              <p v-if="modalErrors.role_id" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.role_id[0] }}</p>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Initial Status
              </label>
              <select
                v-model="addForm.status"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
              >
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="disabled">Disabled</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Temporary Password <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model="addForm.password"
              type="password"
              required
              placeholder="Minimum 8 characters"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="modalErrors.password" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.password[0] }}</p>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button
              type="button"
              @click="showAddModal = false"
              class="px-3 py-1.5 text-xs text-neutral-600 hover:text-neutral-900"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSubmittingModal"
              class="px-3.5 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors disabled:opacity-50"
            >
              {{ isSubmittingModal ? 'Saving...' : 'Create Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-lg border border-neutral-200">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <div>
            <h3 class="text-sm font-semibold text-neutral-900">Edit User Details</h3>
            <p class="text-xs text-neutral-500 font-mono">{{ editingUser?.employee_id }}</p>
          </div>
          <button @click="showEditModal = false" class="text-neutral-400 hover:text-neutral-600">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitEditUser" class="space-y-3.5 mt-3 text-xs">
          <!-- Employee ID -->
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Employee ID <span class="text-neutral-400">*</span>
            </label>
            <input
              v-model.trim="editForm.employee_id"
              type="text"
              required
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="modalErrors.employee_id" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.employee_id[0] }}</p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                First Name <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="editForm.first_name"
                type="text"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="modalErrors.first_name" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.first_name[0] }}</p>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Last Name <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="editForm.last_name"
                type="text"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="modalErrors.last_name" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.last_name[0] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Email Address <span class="text-neutral-400">*</span>
              </label>
              <input
                v-model.trim="editForm.email"
                type="email"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
              <p v-if="modalErrors.email" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.email[0] }}</p>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Branch Code
              </label>
              <input
                v-model.trim="editForm.branch_code"
                type="text"
                placeholder="Optional"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Role / Position <span class="text-neutral-400">*</span>
              </label>
              <select
                v-model="editForm.role_id"
                required
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0"
              >
                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name }}</option>
              </select>
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
                Account Status
              </label>
              <select
                v-model="editForm.status"
                :disabled="editingUser?.id === currentUser?.id"
                class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 bg-white text-xs focus:border-neutral-900 focus:ring-0 disabled:bg-neutral-100"
              >
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="disabled">Disabled</option>
              </select>
              <p v-if="editingUser?.id === currentUser?.id" class="text-[10px] text-neutral-400 mt-1">Cannot change your own status</p>
            </div>
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">
              Reset Password <span class="text-[11px] text-neutral-400 font-normal">(Leave blank to keep current)</span>
            </label>
            <input
              v-model="editForm.password"
              type="password"
              placeholder="Leave blank or min. 8 characters"
              class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200 text-xs focus:border-neutral-900 focus:ring-0"
            />
            <p v-if="modalErrors.password" class="mt-1 text-[11px] text-rose-600">{{ modalErrors.password[0] }}</p>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-3 py-1.5 text-xs text-neutral-600 hover:text-neutral-900"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSubmittingModal"
              class="px-3.5 py-1.5 text-xs font-medium bg-neutral-900 hover:bg-neutral-800 text-white rounded-md transition-colors disabled:opacity-50"
            >
              {{ isSubmittingModal ? 'Saving Changes...' : 'Save Changes' }}
            </button>
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

const { user: currentUser } = useAuth();

const activeMainTab = ref('users');

const rolesWithDescriptions = [
  {
    name: 'admin',
    display_name: 'Administrator',
    department: 'System Administration',
    scope_text: 'Full System Access',
    description: 'System access control, user approval & management, high-level reporting across all locations.',
  },
  {
    name: 'manager',
    display_name: 'Operations Manager',
    department: 'Operations Management',
    scope_text: 'Cross-Department Operations',
    description: 'Executive oversight, cross-department data review, variable filtering, and multi-format report exports.',
  },
  {
    name: 'asst_treasury_manager',
    display_name: 'Assistant Treasury Manager',
    department: 'Treasury',
    scope_text: 'Treasury Administration',
    description: 'Area location setup, store assignments, scheduling, checklist template configuration, and audit management for Treasury.',
  },
  {
    name: 'asst_cashier_manager',
    display_name: 'Assistant Cashier Manager',
    department: 'Cashiering',
    scope_text: 'Cashier Administration',
    description: 'Area location setup, store assignments, scheduling, checklist template configuration, and audit management for Cashiering.',
  },
  {
    name: 'area_treasury_supervisor',
    display_name: 'Area Treasury Supervisor',
    department: 'Treasury',
    scope_text: 'Field Auditor (Assigned Stores)',
    description: 'Field execution: conducts Treasury store checklist audits, scores compliance, and logs observations for assigned stores.',
  },
  {
    name: 'area_cashier_supervisor',
    display_name: 'Area Cashier Supervisor',
    department: 'Cashiering',
    scope_text: 'Field Auditor (Assigned Stores)',
    description: 'Field execution: conducts Cashier store checklist audits, scores compliance, and logs observations for assigned stores.',
  },
];

function getUserCountForRole(roleName) {
  return users.value.filter(u => u.role?.name === roleName).length;
}

const users = ref([]);
const roles = ref([]);
const stats = reactive({
  total: 0,
  pending: 0,
  active: 0,
  disabled: 0,
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

const currentStatusFilter = ref('all');
const selectedRoleId = ref('');
const searchQuery = ref('');
const isLoading = ref(false);

const toastMessage = ref('');
const toastType = ref('success');

const statusTabs = computed(() => [
  { label: 'All Users', value: 'all', count: stats.total },
  { label: 'Pending', value: 'pending', count: stats.pending },
  { label: 'Active', value: 'active', count: stats.active },
  { label: 'Disabled', value: 'disabled', count: stats.disabled },
]);

const toastClasses = computed(() => {
  return toastType.value === 'success'
    ? 'bg-neutral-900 text-white border-neutral-900'
    : 'bg-rose-50 border-rose-200 text-rose-800';
});

function showToast(message, type = 'success') {
  toastMessage.value = message;
  toastType.value = type;
  setTimeout(() => {
    toastMessage.value = '';
  }, 4000);
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

async function loadUsers() {
  isLoading.value = true;
  try {
    const params = new URLSearchParams({
      page: pagination.current_page,
      per_page: pagination.per_page,
    });

    if (currentStatusFilter.value !== 'all') {
      params.append('status', currentStatusFilter.value);
    }
    if (selectedRoleId.value) {
      params.append('role_id', selectedRoleId.value);
    }
    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }

    const res = await api.get(`/admin/users?${params.toString()}`);
    users.value = res.data.data;
    pagination.current_page = res.data.current_page;
    pagination.last_page = res.data.last_page;
    pagination.total = res.data.total;
    pagination.from = res.data.from;
    pagination.to = res.data.to;

    // Refresh stats summary
    computeStats();
  } catch (err) {
    showToast(err.message || 'Failed to load user accounts', 'error');
  } finally {
    isLoading.value = false;
  }
}

async function loadRoles() {
  try {
    const res = await api.get('/auth/roles');
    roles.value = res.data;
  } catch (err) {
    console.error('Failed to load roles', err);
  }
}

function computeStats() {
  // Compute counts from all users if on initial load
  stats.total = pagination.total;
  let active = 0;
  let pending = 0;
  let disabled = 0;
  users.value.forEach((u) => {
    if (u.status === 'active') active++;
    else if (u.status === 'pending') pending++;
    else if (u.status === 'disabled') disabled++;
  });
  stats.active = active;
  stats.pending = pending;
  stats.disabled = disabled;
}

function selectStatusFilter(status) {
  currentStatusFilter.value = status;
  pagination.current_page = 1;
  loadUsers();
}

function changePage(page) {
  pagination.current_page = page;
  loadUsers();
}

// Approve User Action
async function approveUser(user) {
  if (!confirm(`Are you sure you want to approve registration for ${user.first_name} ${user.last_name}?`)) {
    return;
  }
  try {
    const res = await api.post(`/admin/users/${user.id}/approve`);
    showToast(res.message || 'User approved successfully');
    loadUsers();
  } catch (err) {
    showToast(err.message || 'Failed to approve user', 'error');
  }
}

// Toggle Status Action
async function toggleUserStatus(user) {
  const actionText = user.status === 'disabled' ? 'activate' : 'disable';
  if (!confirm(`Are you sure you want to ${actionText} the account for ${user.first_name} ${user.last_name}?`)) {
    return;
  }
  try {
    const res = await api.post(`/admin/users/${user.id}/toggle-status`);
    showToast(res.message || 'User status updated');
    loadUsers();
  } catch (err) {
    showToast(err.message || 'Failed to update user status', 'error');
  }
}

// Add User Modal State & Actions
const showAddModal = ref(false);
const isSubmittingModal = ref(false);
const modalErrors = ref({});

const addForm = reactive({
  employee_id: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  role_id: '',
  branch_code: '',
  status: 'active',
});

function openAddModal() {
  modalErrors.value = {};
  addForm.employee_id = '';
  addForm.first_name = '';
  addForm.last_name = '';
  addForm.email = '';
  addForm.password = '';
  addForm.role_id = '';
  addForm.branch_code = '';
  addForm.status = 'active';
  showAddModal.value = true;
}

async function submitAddUser() {
  modalErrors.value = {};
  isSubmittingModal.value = true;
  try {
    const res = await api.post('/admin/users', addForm);
    showToast(res.message || 'User account created successfully');
    showAddModal.value = false;
    loadUsers();
  } catch (err) {
    if (err.errors) {
      modalErrors.value = err.errors;
    } else {
      showToast(err.message || 'Failed to create user', 'error');
    }
  } finally {
    isSubmittingModal.value = false;
  }
}

// Edit User Modal State & Actions
const showEditModal = ref(false);
const editingUser = ref(null);
const editForm = reactive({
  employee_id: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  role_id: '',
  branch_code: '',
  status: 'active',
});

function openEditModal(u) {
  modalErrors.value = {};
  editingUser.value = u;
  editForm.employee_id = u.employee_id;
  editForm.first_name = u.first_name;
  editForm.last_name = u.last_name;
  editForm.email = u.email;
  editForm.password = '';
  editForm.role_id = u.role_id;
  editForm.branch_code = u.branch_code || '';
  editForm.status = u.status;
  showEditModal.value = true;
}

async function submitEditUser() {
  modalErrors.value = {};
  isSubmittingModal.value = true;
  try {
    const payload = { ...editForm };
    if (!payload.password) delete payload.password;

    const res = await api.put(`/admin/users/${editingUser.value.id}`, payload);
    showToast(res.message || 'User details updated successfully');
    showEditModal.value = false;
    loadUsers();
  } catch (err) {
    if (err.errors) {
      modalErrors.value = err.errors;
    } else {
      showToast(err.message || 'Failed to update user', 'error');
    }
  } finally {
    isSubmittingModal.value = false;
  }
}

onMounted(() => {
  loadRoles();
  loadUsers();
});
</script>

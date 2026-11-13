<template>
  <div class="py-5 sm:py-7">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Audit Templates &bull; {{ currentDepartmentLabel }}
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-1">
            Checklist Item Templates
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Configure audit categories, activities, sub-activities, and weight allocations.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Audit Type Switcher (If Admin or Cross-dept) -->
          <div v-if="canSwitchType" class="inline-flex rounded-lg border border-neutral-300 bg-neutral-100/80 p-1 text-xs">
            <button
              type="button"
              @click="switchAuditType('treasury')"
              :class="[
                'min-h-[38px] px-3.5 py-1.5 rounded-md font-semibold transition-colors',
                selectedAuditType === 'treasury' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Treasury
            </button>
            <button
              type="button"
              @click="switchAuditType('cashier')"
              :class="[
                'min-h-[38px] px-3.5 py-1.5 rounded-md font-semibold transition-colors',
                selectedAuditType === 'cashier' ? 'bg-brand-600 text-white shadow-xs' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Cashier
            </button>
          </div>

          <button
            v-if="canManageCurrentType"
            type="button"
            @click="openAddModal"
            class="min-h-[44px] inline-flex items-center space-x-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs font-semibold transition-colors shadow-xs"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Item</span>
          </button>
        </div>
      </div>

      <!-- Stats Bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-neutral-200/80 shadow-xs">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Total Items</span>
          <div class="text-xl font-bold text-neutral-900 mt-0.5">{{ items.length }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-neutral-200/80 shadow-xs">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Categories</span>
          <div class="text-xl font-bold text-neutral-900 mt-0.5">{{ groupedCategories.length }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-neutral-200/80 shadow-xs">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Total Weight</span>
          <div class="text-xl font-bold text-brand-700 mt-0.5 font-mono">{{ totalWeight.toFixed(2) }} pts</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-neutral-200/80 shadow-xs">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Active Status</span>
          <div class="text-xl font-bold text-neutral-900 mt-0.5 font-mono">
            {{ activeItemsCount }} / {{ items.length }}
          </div>
        </div>
      </div>

      <!-- Toast Alert -->
      <div v-if="toast" class="p-3.5 rounded-xl border text-xs bg-brand-900 text-white flex items-center justify-between shadow-xs">
        <span class="font-medium">{{ toast }}</span>
        <button @click="toast = ''" class="p-1 min-h-[36px] min-w-[36px] flex items-center justify-center text-brand-300 hover:text-white">&times;</button>
      </div>

      <!-- Grouped Checklist Table -->
      <div class="bg-white rounded-xl border border-neutral-200/80 overflow-hidden shadow-xs">
        <div v-if="isLoading" class="p-12 text-center text-xs text-neutral-400">
          Loading checklist template items...
        </div>
        <div v-else-if="items.length === 0" class="p-12 text-center text-xs text-neutral-400">
          No checklist items found for {{ currentDepartmentLabel }}. Click "Add Item" to begin.
        </div>
        <div v-else class="divide-y divide-neutral-200">
          <div v-for="cat in groupedCategories" :key="cat.name" class="p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <div class="flex items-center space-x-2.5">
                <span class="w-1.5 h-4 bg-brand-600 rounded-full"></span>
                <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-neutral-900">{{ cat.name }}</h3>
                <span class="text-[11px] font-mono text-neutral-400">({{ cat.items.length }} items)</span>
              </div>
              <span class="text-xs font-mono font-medium text-brand-700 bg-brand-50 px-2.5 py-1 rounded-lg border border-brand-100">
                Weight: {{ cat.subtotalWeight.toFixed(2) }} pts
              </span>
            </div>

            <!-- Table View -->
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs min-w-[500px]">
                <thead>
                  <tr class="bg-neutral-50 border-y border-neutral-100 text-[10px] uppercase font-mono text-neutral-400">
                    <th class="py-2.5 px-3 w-12 text-center">#</th>
                    <th class="py-2.5 px-3">Activity</th>
                    <th class="py-2.5 px-3">Sub-Activity / Details</th>
                    <th class="py-2.5 px-3 w-24 text-right">Weight</th>
                    <th class="py-2.5 px-3 w-20 text-center">Status</th>
                    <th v-if="canManageCurrentType" class="py-2.5 px-3 w-28 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                  <tr v-for="(it, idx) in cat.items" :key="it.id" class="hover:bg-neutral-50/60 transition-colors">
                    <td class="py-3 px-3 text-center font-mono text-neutral-400">{{ idx + 1 }}</td>
                    <td class="py-3 px-3 font-semibold text-neutral-900">{{ it.activity }}</td>
                    <td class="py-3 px-3 text-neutral-600">{{ it.sub_activity || '-' }}</td>
                    <td class="py-3 px-3 text-right font-mono font-bold text-neutral-900">{{ Number(it.weight).toFixed(2) }}</td>
                    <td class="py-3 px-3 text-center">
                      <span :class="['px-2 py-0.5 rounded text-[10px] font-mono font-semibold', it.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-neutral-100 text-neutral-500 border border-neutral-200']">
                        {{ it.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td v-if="canManageCurrentType" class="py-3 px-3 text-right">
                      <div class="flex items-center justify-end space-x-2">
                        <button @click="openEditModal(it)" class="min-h-[36px] px-2.5 py-1 text-brand-600 hover:text-brand-800 font-semibold text-xs">Edit</button>
                        <button @click="deleteItem(it)" class="min-h-[36px] px-2.5 py-1 text-neutral-400 hover:text-rose-600 font-semibold text-xs">Delete</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD / EDIT MODAL (Full-screen sheet on mobile) -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/50 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="bg-white rounded-t-2xl sm:rounded-xl max-w-lg w-full h-[90vh] sm:h-auto flex flex-col p-5 sm:p-6 shadow-xl border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100 shrink-0">
          <h3 class="text-sm font-bold text-neutral-900">{{ isEditing ? 'Edit Checklist Item' : 'New Checklist Item' }}</h3>
          <button @click="showModal = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-neutral-400 hover:text-neutral-700 text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4 mt-4 overflow-y-auto flex-1 pr-1">
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Category <span class="text-rose-500">*</span></label>
            <input v-model.trim="form.category" required type="text" placeholder="e.g. Cash Management" class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Activity / Audit Question <span class="text-rose-500">*</span></label>
            <textarea v-model.trim="form.activity" required rows="2" placeholder="e.g. Verify safe vault dual combination compliance" class="w-full px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Sub-Activity / Additional Detail</label>
            <textarea v-model.trim="form.sub_activity" rows="2" placeholder="Optional details or criteria" class="w-full px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Weight (Points) <span class="text-rose-500">*</span></label>
              <input v-model.number="form.weight" required type="number" step="0.5" min="0" max="100" class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Sort Order</label>
              <input v-model.number="form.sort_order" type="number" min="0" class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-neutral-300 text-base sm:text-xs text-neutral-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
            </div>
          </div>

          <div class="flex items-center space-x-2.5 pt-1">
            <input id="item_active" v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500" />
            <label for="item_active" class="text-neutral-700 font-semibold cursor-pointer">Item is active for audit execution</label>
          </div>

          <div class="sticky bottom-0 bg-white pt-4 pb-2 border-t border-neutral-100 flex items-center justify-end space-x-3 shrink-0">
            <button type="button" @click="showModal = false" class="min-h-[44px] px-4 py-2 text-neutral-600 hover:text-neutral-900 font-medium">Cancel</button>
            <button type="submit" :disabled="isSubmitting" class="min-h-[44px] px-5 py-2 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-lg font-semibold text-xs shadow-xs disabled:opacity-50">
              {{ isSubmitting ? 'Saving...' : 'Save Item' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../services/api';
import { useAuth } from '../composables/useAuth';

const { user, isAdmin, hasRole } = useAuth();

const items = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const toast = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const selectedAuditType = ref('treasury');

onMounted(() => {
  if (hasRole('asst_cashier_manager') || hasRole('area_cashier_supervisor')) {
    selectedAuditType.value = 'cashier';
  } else {
    selectedAuditType.value = 'treasury';
  }
  loadItems();
});

const canSwitchType = computed(() => {
  return isAdmin.value || user.value?.role?.name === 'manager';
});

const canManageCurrentType = computed(() => {
  if (isAdmin.value) return true;
  if (selectedAuditType.value === 'treasury' && hasRole('asst_treasury_manager')) return true;
  if (selectedAuditType.value === 'cashier' && hasRole('asst_cashier_manager')) return true;
  return false;
});

const currentDepartmentLabel = computed(() => {
  return selectedAuditType.value === 'treasury' ? 'Treasury Audit' : 'Cashier Audit';
});

const form = ref({
  category: '',
  activity: '',
  sub_activity: '',
  weight: 5,
  sort_order: 0,
  is_active: true,
});

async function loadItems() {
  isLoading.value = true;
  try {
    const res = await api.get(`/checklists?audit_type=${selectedAuditType.value}`);
    items.value = res.data || [];
  } catch (err) {
    console.error('Failed to load checklists:', err);
  } finally {
    isLoading.value = false;
  }
}

function switchAuditType(type) {
  selectedAuditType.value = type;
  loadItems();
}

const totalWeight = computed(() => {
  return items.value.reduce((acc, it) => acc + Number(it.weight || 0), 0);
});

const activeItemsCount = computed(() => {
  return items.value.filter(it => it.is_active).length;
});

const groupedCategories = computed(() => {
  const groups = {};
  for (const it of items.value) {
    const cat = it.category || 'General';
    if (!groups[cat]) {
      groups[cat] = {
        name: cat,
        items: [],
        subtotalWeight: 0,
      };
    }
    groups[cat].items.push(it);
    groups[cat].subtotalWeight += Number(it.weight || 0);
  }
  return Object.values(groups);
});

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  form.value = {
    category: '',
    activity: '',
    sub_activity: '',
    weight: 5,
    sort_order: items.value.length + 1,
    is_active: true,
  };
  showModal.value = true;
}

function openEditModal(it) {
  isEditing.value = true;
  editingId.value = it.id;
  form.value = {
    category: it.category,
    activity: it.activity,
    sub_activity: it.sub_activity || '',
    weight: Number(it.weight),
    sort_order: it.sort_order,
    is_active: it.is_active,
  };
  showModal.value = true;
}

async function submitForm() {
  isSubmitting.value = true;
  try {
    if (isEditing.value) {
      await api.put(`/checklists/${editingId.value}`, form.value);
      toast.value = 'Checklist item updated successfully.';
    } else {
      await api.post('/checklists', {
        ...form.value,
        audit_type: selectedAuditType.value,
      });
      toast.value = 'Checklist item created successfully.';
    }
    showModal.value = false;
    await loadItems();
  } catch (err) {
    toast.value = err.message || 'Operation failed.';
  } finally {
    isSubmitting.value = false;
  }
}

async function deleteItem(it) {
  if (!confirm(`Delete item: "${it.activity}"?`)) return;
  try {
    await api.delete(`/checklists/${it.id}`);
    toast.value = 'Checklist item removed.';
    await loadItems();
  } catch (err) {
    toast.value = err.message || 'Failed to delete item.';
  }
}
</script>


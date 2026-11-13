<template>
  <div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-[11px] font-mono uppercase tracking-wider text-neutral-400">
            Audit Templates &bull; {{ currentDepartmentLabel }}
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight mt-1">
            Checklist Item Templates
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5">
            Configure audit categories, activities, sub-activities, and weight allocations.
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Audit Type Switcher (If Admin or Cross-dept) -->
          <div v-if="canSwitchType" class="inline-flex rounded-md border border-neutral-200 bg-white p-0.5 text-xs">
            <button
              type="button"
              @click="switchAuditType('treasury')"
              :class="[
                'px-2.5 py-1 rounded font-medium transition-colors',
                selectedAuditType === 'treasury' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Treasury
            </button>
            <button
              type="button"
              @click="switchAuditType('cashier')"
              :class="[
                'px-2.5 py-1 rounded font-medium transition-colors',
                selectedAuditType === 'cashier' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
              ]"
            >
              Cashier
            </button>
          </div>

          <button
            v-if="canManageCurrentType"
            type="button"
            @click="openAddModal"
            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-medium transition-colors shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Item</span>
          </button>
        </div>
      </div>

      <!-- Stats Bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-lg border border-neutral-200">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Total Items</span>
          <div class="text-lg font-semibold text-neutral-900 mt-0.5">{{ items.length }}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-neutral-200">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Categories</span>
          <div class="text-lg font-semibold text-neutral-900 mt-0.5">{{ groupedCategories.length }}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-neutral-200">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Total Weight</span>
          <div class="text-lg font-semibold text-neutral-900 mt-0.5 font-mono">{{ totalWeight.toFixed(2) }} pts</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-neutral-200">
          <span class="text-[10px] font-mono uppercase text-neutral-400">Active Status</span>
          <div class="text-lg font-semibold text-neutral-900 mt-0.5 font-mono">
            {{ activeItemsCount }} / {{ items.length }} Active
          </div>
        </div>
      </div>

      <!-- Toast Alert -->
      <div v-if="toast" class="p-3 rounded-lg border text-xs bg-neutral-900 text-white flex items-center justify-between">
        <span>{{ toast }}</span>
        <button @click="toast = ''" class="text-neutral-400 hover:text-white">&times;</button>
      </div>

      <!-- Grouped Checklist Table -->
      <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden shadow-xs">
        <div v-if="isLoading" class="p-12 text-center text-xs text-neutral-400">
          Loading checklist template items...
        </div>
        <div v-else-if="items.length === 0" class="p-12 text-center text-xs text-neutral-400">
          No checklist items found for {{ currentDepartmentLabel }}. Click "Add Item" to begin.
        </div>
        <div v-else class="divide-y divide-neutral-200">
          <div v-for="cat in groupedCategories" :key="cat.name" class="p-4 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="w-1.5 h-3.5 bg-neutral-900 rounded-xs"></span>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900">{{ cat.name }}</h3>
                <span class="text-[11px] font-mono text-neutral-400">({{ cat.items.length }} items)</span>
              </div>
              <span class="text-xs font-mono text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded border border-neutral-200">
                Weight: {{ cat.subtotalWeight.toFixed(2) }} pts
              </span>
            </div>

            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-neutral-50 border-y border-neutral-100 text-[10px] uppercase font-mono text-neutral-400">
                  <th class="py-2 px-3 w-12 text-center">#</th>
                  <th class="py-2 px-3">Activity</th>
                  <th class="py-2 px-3">Sub-Activity / Details</th>
                  <th class="py-2 px-3 w-24 text-right">Weight</th>
                  <th class="py-2 px-3 w-20 text-center">Status</th>
                  <th v-if="canManageCurrentType" class="py-2 px-3 w-24 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-neutral-100">
                <tr v-for="(it, idx) in cat.items" :key="it.id" class="hover:bg-neutral-50/50">
                  <td class="py-2.5 px-3 text-center font-mono text-neutral-400">{{ idx + 1 }}</td>
                  <td class="py-2.5 px-3 font-medium text-neutral-900">{{ it.activity }}</td>
                  <td class="py-2.5 px-3 text-neutral-600">{{ it.sub_activity || '-' }}</td>
                  <td class="py-2.5 px-3 text-right font-mono font-medium text-neutral-900">{{ Number(it.weight).toFixed(2) }}</td>
                  <td class="py-2.5 px-3 text-center">
                    <span :class="['px-1.5 py-0.5 rounded text-[10px] font-mono', it.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-400']">
                      {{ it.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td v-if="canManageCurrentType" class="py-2.5 px-3 text-right space-x-1.5">
                    <button @click="openEditModal(it)" class="text-neutral-500 hover:text-neutral-900 text-xs">Edit</button>
                    <button @click="deleteItem(it)" class="text-neutral-400 hover:text-rose-600 text-xs">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD / EDIT MODAL -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-neutral-900/40 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-neutral-200 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
          <h3 class="text-sm font-semibold text-neutral-900">{{ isEditing ? 'Edit Checklist Item' : 'New Checklist Item' }}</h3>
          <button @click="showModal = false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-3 mt-3">
          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Category *</label>
            <input v-model.trim="form.category" required type="text" placeholder="e.g. Cash Management" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Activity / Audit Question *</label>
            <textarea v-model.trim="form.activity" required rows="2" placeholder="e.g. Verify safe vault dual combination compliance" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200"></textarea>
          </div>

          <div>
            <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Sub-Activity / Additional Detail</label>
            <textarea v-model.trim="form.sub_activity" rows="2" placeholder="Optional details or criteria" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Weight (Points) *</label>
              <input v-model.number="form.weight" required type="number" step="0.5" min="0" max="100" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
            </div>
            <div>
              <label class="block font-medium text-neutral-700 uppercase tracking-wider text-[10px] mb-1">Sort Order</label>
              <input v-model.number="form.sort_order" type="number" min="0" class="w-full px-2.5 py-1.5 rounded-md border border-neutral-200" />
            </div>
          </div>

          <div class="flex items-center space-x-2 pt-1">
            <input id="item_active" v-model="form.is_active" type="checkbox" class="rounded border-neutral-300" />
            <label for="item_active" class="text-neutral-700 font-medium">Item is active for audit execution</label>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-neutral-100">
            <button type="button" @click="showModal = false" class="px-3 py-1.5 text-neutral-600 hover:text-neutral-900">Cancel</button>
            <button type="submit" :disabled="isSubmitting" class="px-3.5 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-md font-medium disabled:opacity-50">
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


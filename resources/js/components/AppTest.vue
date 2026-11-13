<template>
  <div class="bg-white shadow rounded-lg p-6 max-w-xl mx-auto my-8 border border-gray-100">
    <div class="flex items-center space-x-3 mb-4">
      <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold">
        ✓
      </div>
      <div>
        <h2 class="text-xl font-bold text-gray-800">LCC Store Visit Audit Portal</h2>
        <p class="text-sm text-gray-500">Step 1: Project Setup Verification</p>
      </div>
    </div>
    <div class="space-y-2 text-sm text-gray-700">
      <div class="flex justify-between py-1 border-b border-gray-100">
        <span class="font-medium text-gray-600">Backend</span>
        <span class="text-emerald-700 font-semibold">Laravel 11 (PHP 8.3)</span>
      </div>
      <div class="flex justify-between py-1 border-b border-gray-100">
        <span class="font-medium text-gray-600">Frontend</span>
        <span class="text-emerald-700 font-semibold">Vue.js 3</span>
      </div>
      <div class="flex justify-between py-1 border-b border-gray-100">
        <span class="font-medium text-gray-600">CSS Framework</span>
        <span class="text-emerald-700 font-semibold">Tailwind CSS</span>
      </div>
      <div class="flex justify-between py-1 border-b border-gray-100">
        <span class="font-medium text-gray-600">Database</span>
        <span class="text-emerald-700 font-semibold">MySQL</span>
      </div>
    </div>
    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
      <button 
        @click="count++" 
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded shadow transition"
      >
        Test Reactive State: {{ count }}
      </button>
      <span class="text-xs text-gray-400">Environment Ready</span>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>


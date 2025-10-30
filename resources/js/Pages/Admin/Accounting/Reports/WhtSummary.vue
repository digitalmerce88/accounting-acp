<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">สรุปหัก ณ ที่จ่าย (WHT)</h1>
    <div class="mt-2 text-sm text-gray-600">ช่วง: {{ start }} ถึง {{ end }}</div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">WHT รับ</div>
        <div class="text-2xl font-semibold">{{ format(wht_received) }}</div>
      </div>
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">WHT จ่าย</div>
        <div class="text-2xl font-semibold">{{ format(wht_payable) }}</div>
      </div>
    </div>
    <div class="mt-4">
      <a href="/admin/accounting/reports/tax/wht-summary.csv" class="px-3 py-1 bg-blue-600 text-white rounded">ดาวน์โหลด CSV</a>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
const p = usePage().props
const start = computed(()=> p.start)
const end = computed(()=> p.end)
const wht_received = computed(()=> p.wht_received || 0)
const wht_payable = computed(()=> p.wht_payable || 0)
function format(n){
  return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n||0)
}
</script>

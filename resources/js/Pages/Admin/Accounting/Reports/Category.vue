<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">รายงานตามหมวด/บัญชี</h1>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border text-left">โค้ด</th>
            <th class="p-2 border text-left">ชื่อบัญชี</th>
            <th class="p-2 border text-left">ประเภท</th>
            <th class="p-2 border text-right">จำนวน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.code" class="border-b">
            <td class="p-2 border">{{ r.code }}</td>
            <td class="p-2 border">{{ r.name }}</td>
            <td class="p-2 border">{{ typeLabel(r.type) }}</td>
            <td class="p-2 border text-right">{{ format(r.amount) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-4">
      <a href="/admin/accounting/reports/by-category.csv" class="px-3 py-1 bg-blue-600 text-white rounded">ดาวน์โหลด CSV</a>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const p = usePage().props
const rows = computed(()=> p.rows || [])

function typeLabel(t){
  return t==='revenue' ? 'รายได้' : (t==='expense' ? 'ค่าใช้จ่าย' : t)
}
function format(n){
  return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n||0)
}
</script>

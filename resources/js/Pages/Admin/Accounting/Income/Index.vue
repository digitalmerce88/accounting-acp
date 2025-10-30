<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">รายรับ</h1>
      <a href="/admin/accounting/income/create" class="px-3 py-1 bg-green-700 text-white rounded">+ รายรับ</a>
    </div>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border">บันทึก</th>
            <th class="p-2 border text-right">จำนวน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.date }}</td>
            <td class="p-2 border">{{ r.memo }}</td>
            <td class="p-2 border text-right">{{ fmt(r.amount) }}</td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="3" class="p-3 text-center text-gray-500">ยังไม่มีรายการ</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
</script>

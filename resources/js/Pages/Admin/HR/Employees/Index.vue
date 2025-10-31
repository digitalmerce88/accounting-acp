<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">พนักงาน</h1>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">รหัส</th>
            <th class="p-2 border">ชื่อ</th>
            <th class="p-2 border">ตำแหน่ง</th>
            <th class="p-2 border text-right">เงินเดือน</th>
            <th class="p-2 border">สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in rows.data" :key="e.id">
            <td class="p-2 border">{{ e.emp_code || '-' }}</td>
            <td class="p-2 border">{{ e.name }}</td>
            <td class="p-2 border">{{ e.position || '-' }}</td>
            <td class="p-2 border text-right">{{ fmt(e.base_salary_decimal) }}</td>
            <td class="p-2 border">
              <span :class="e.active ? 'text-green-700' : 'text-gray-400'">{{ e.active ? 'ใช้งาน' : 'ปิด' }}</span>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="5" class="p-3 text-center text-gray-500">ยังไม่มีพนักงาน</td>
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

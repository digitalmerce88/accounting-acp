<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">พนักงาน</h1>
      <a href="/admin/hr/employees/create" class="px-3 py-1 bg-green-700 text-white rounded">+ เพิ่มพนักงาน</a>
    </div>
    <div class="flex gap-2 mb-3">
      <a href="/admin/hr/employees?status=active" :class="tabClass('active')">กำลังใช้งาน</a>
      <a href="/admin/hr/employees?status=inactive" :class="tabClass('inactive')">ปิดใช้งาน</a>
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
            <th class="p-2 border w-48">การทำงาน</th>
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
            <td class="p-2 border">
              <div class="flex gap-2">
                <a :href="`/admin/hr/employees/${e.id}`" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</a>
                <a :href="`/admin/hr/employees/${e.id}/edit`" class="px-2 py-0.5 text-xs bg-blue-700 text-white rounded">แก้ไข</a>
                <button v-if="e.active" @click="deactivate(e.id)" class="px-2 py-0.5 text-xs bg-red-700 text-white rounded">ปิด</button>
                <button v-else @click="restore(e.id)" class="px-2 py-0.5 text-xs bg-green-700 text-white rounded">เปิด</button>
              </div>
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
import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
const status = computed(()=> usePage().props.status || 'active')
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function deactivate(id){ if(confirm('ปิดใช้งานพนักงานนี้?')) router.delete(`/admin/hr/employees/${id}`) }
function restore(id){ if(confirm('เปิดใช้งานพนักงานนี้?')) router.post(`/admin/hr/employees/${id}/restore`) }
function tabClass(s){ return 'px-3 py-1 rounded ' + (status.value===s ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700') }
</script>

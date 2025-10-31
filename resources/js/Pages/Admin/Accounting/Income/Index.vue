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
            <th class="p-2 border w-40">การทำงาน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.date }}</td>
            <td class="p-2 border">{{ r.memo }}</td>
            <td class="p-2 border text-right">{{ fmt(r.amount) }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <a :href="`/admin/accounting/income/${r.id}`" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</a>
                <a :href="`/admin/accounting/income/${r.id}/edit`" class="px-2 py-0.5 text-xs bg-blue-600 text-white rounded">แก้ไข</a>
                <button @click="del(r.id)" class="px-2 py-0.5 text-xs bg-red-600 text-white rounded">ลบ</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="4" class="p-3 text-center text-gray-500">ยังไม่มีรายการ</td>
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
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function del(id){
  if (!confirm('ยืนยันลบรายการนี้?')) return
  router.delete(`/admin/accounting/income/${id}`)
}
</script>

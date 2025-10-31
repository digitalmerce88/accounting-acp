<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-3">ใบวางบิล/ใบค่าใช้จ่าย</h1>
    <div class="overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">เลขที่</th>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border text-right">รวมสุทธิ</th>
            <th class="p-2 border">สถานะ</th>
            <th class="p-2 border w-40">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.number || r.id }}</td>
            <td class="p-2 border">{{ r.bill_date }}</td>
            <td class="p-2 border text-right">{{ fmt(r.total) }}</td>
            <td class="p-2 border">{{ r.status || '-' }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <a :href="`/admin/documents/bills/${r.id}`" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</a>
                <button v-if="r.status!=='paid'" @click="pay(r.id)" class="px-2 py-0.5 text-xs bg-green-700 text-white rounded">จ่าย</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0"><td colspan="5" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
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
function pay(id){ if(confirm('จ่ายบิลนี้?')) router.post(`/admin/documents/bills/${id}/pay`, { date: new Date().toISOString().slice(0,10) }) }
</script>

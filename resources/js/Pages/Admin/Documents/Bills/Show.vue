<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Bill {{ item.number || item.id }}</h1>
      <div class="flex gap-2">
  <a :href="`/admin/documents/bills/${item.id}/pdf`" target="_blank" rel="noopener" class="px-3 py-1 border rounded">ดูตัวอย่าง PDF</a>
  <a :href="`/admin/documents/bills/${item.id}/pdf?dl=1`" class="px-3 py-1 border rounded">ดาวน์โหลด</a>
        <a v-if="item.status!=='paid'" :href="`/admin/documents/bills/${item.id}/edit`" class="px-3 py-1 border rounded">แก้ไข</a>
        <button v-if="item.status!=='paid'" @click="onDelete" class="px-3 py-1 bg-red-600 text-white rounded">ลบ</button>
        <button v-if="item.status!=='paid'" @click="pay" class="px-3 py-1 bg-green-700 text-white rounded">จ่าย</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-sm">
      <div>
        <div class="text-gray-500">วันที่</div>
        <div class="font-medium">{{ item.bill_date }}</div>
      </div>
      <div>
        <div class="text-gray-500">ครบกำหนด</div>
        <div class="font-medium">{{ item.due_date || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">รวมสุทธิ</div>
        <div class="font-medium">{{ fmt(item.total) }}</div>
      </div>
      <div>
        <div class="text-gray-500">สถานะ</div>
        <div class="font-medium">{{ item.status || '-' }}</div>
      </div>
    </div>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'
const item = computed(()=> usePage().props.item)
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function pay(){ if(confirm('จ่ายบิลนี้?')) router.post(`/admin/documents/bills/${item.value.id}/pay`, { date: new Date().toISOString().slice(0,10) }) }
function onDelete(){ if(confirm('ลบเอกสารนี้?')) router.delete(`/admin/documents/bills/${item.value.id}`) }
</script>

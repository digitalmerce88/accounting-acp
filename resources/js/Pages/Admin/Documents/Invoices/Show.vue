<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Invoice {{ item.number || item.id }}</h1>
    <div class="flex gap-2">
  <a :href="`/admin/documents/invoices/${item.id}/pdf`" target="_blank" rel="noopener" class="px-3 py-1 border rounded">ดูตัวอย่างใบแจ้งหนี้</a>
  <a :href="`/admin/documents/invoices/${item.id}/pdf?dl=1`" class="px-3 py-1 border rounded">ดาวน์โหลดใบแจ้งหนี้</a>
  <a :href="`/admin/documents/invoices/${item.id}/receipt.pdf`" target="_blank" rel="noopener" class="px-3 py-1 border rounded">ดูใบเสร็จ</a>
  <a :href="`/admin/documents/invoices/${item.id}/receipt.pdf?dl=1`" class="px-3 py-1 border rounded">ดาวน์โหลดใบเสร็จ</a>
        <a v-if="item.status!=='paid'" :href="`/admin/documents/invoices/${item.id}/edit`" class="px-3 py-1 border rounded">แก้ไข</a>
        <button v-if="item.status!=='paid'" @click="onDelete" class="px-3 py-1 bg-red-600 text-white rounded">ลบ</button>
        <button v-if="item.status!=='paid'" @click="pay" class="px-3 py-1 bg-blue-700 text-white rounded">รับชำระ</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-sm">
      <div>
        <div class="text-gray-500">วันที่</div>
        <div class="font-medium">{{ fmtDMY(item.issue_date) }}</div>
      </div>
      <div>
        <div class="text-gray-500">ครบกำหนด</div>
        <div class="font-medium">{{ fmtDMY(item.due_date) }}</div>
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
import { fmtDMY } from '@/utils/format'
import { confirmDialog } from '@/utils/swal'
const item = computed(()=> usePage().props.item)
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
async function pay(){ if(await confirmDialog('รับชำระใบแจ้งหนี้นี้?')) router.post(`/admin/documents/invoices/${item.value.id}/pay`, { date: new Date().toISOString().slice(0,10) }) }
async function onDelete(){ if(await confirmDialog('ลบเอกสารนี้?')) router.delete(`/admin/documents/invoices/${item.value.id}`) }
</script>

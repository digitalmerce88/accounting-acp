<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Invoice {{ item.number || item.id }}</h1>
      <button v-if="item.status!=='paid'" @click="pay" class="px-3 py-1 bg-blue-700 text-white rounded">รับชำระ</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-sm">
      <div>
        <div class="text-gray-500">วันที่</div>
        <div class="font-medium">{{ item.issue_date }}</div>
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
function pay(){ if(confirm('รับชำระใบแจ้งหนี้นี้?')) router.post(`/admin/documents/invoices/${item.value.id}/pay`, { date: new Date().toISOString().slice(0,10) }) }
</script>

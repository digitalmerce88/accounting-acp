<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">กำไรขาดทุน (P&L)</h1>
    <div class="mt-2 text-sm text-gray-600">ช่วง: {{ from }} ถึง {{ to }}</div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">รายได้</div>
        <div class="text-2xl font-semibold text-green-700">{{ format(revenue) }}</div>
      </div>
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">ค่าใช้จ่าย</div>
        <div class="text-2xl font-semibold text-red-700">{{ format(expense) }}</div>
      </div>
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">กำไรสุทธิ</div>
        <div :class="net >= 0 ? 'text-green-700' : 'text-red-700'" class="text-2xl font-semibold">{{ format(net) }}</div>
      </div>
    </div>
    <div class="mt-4 flex items-center gap-3">
      <a href="/admin/accounting/reports/profit-and-loss.csv" class="px-3 py-1 bg-blue-600 text-white rounded">ดาวน์โหลด CSV</a>
      <button class="px-3 py-1 bg-gray-800 text-white rounded" @click="closeMonth" :disabled="busy">
        {{ busy ? 'กำลังปิดงวด...' : 'ปิดงวดเดือนนี้' }}
      </button>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const p = usePage().props
const from = computed(()=> p.from)
const to = computed(()=> p.to)
const revenue = computed(()=> p.revenue || 0)
const expense = computed(()=> p.expense || 0)
const net = computed(()=> p.net || 0)
function format(n){
  return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n||0)
}

const busy = ref(false)
function closeMonth(){
  if(!confirm('ยืนยันการปิดงวดเดือนนี้?')) return
  busy.value = true
  const today = new Date()
  const payload = { year: today.getFullYear(), month: today.getMonth()+1 }
  router.post('/admin/accounting/close/month', payload, {
    preserveScroll: true,
    onFinish: () => { busy.value = false },
  })
}
</script>

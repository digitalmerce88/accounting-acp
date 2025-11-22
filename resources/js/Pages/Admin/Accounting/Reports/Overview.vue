<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">สรุปภาพรวม</h1>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">รายรับ</div>
        <div class="text-2xl font-semibold text-green-700">{{ format(income) }}</div>
        <div class="text-xs text-gray-400 mt-1">ฐานสกุลเงิน ({{ baseCurrency }}): {{ format(income_base) }}</div>
      </div>
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">รายจ่าย</div>
        <div class="text-2xl font-semibold text-red-700">{{ format(expense) }}</div>
        <div class="text-xs text-gray-400 mt-1">ฐานสกุลเงิน ({{ baseCurrency }}): {{ format(expense_base) }}</div>
      </div>
      <div class="p-4 bg-white border rounded">
        <div class="text-xs text-gray-500">กำไรสุทธิ</div>
        <div :class="net >= 0 ? 'text-green-700' : 'text-red-700'" class="text-2xl font-semibold">{{ format(net) }}</div>
        <div class="text-xs text-gray-400 mt-1" :class="net_base >= 0 ? 'text-green-600' : 'text-red-600'">ฐานสกุลเงิน ({{ baseCurrency }}): {{ format(net_base) }}</div>
      </div>
    </div>

    <div class="mt-4">
      <a :href="csvUrl" class="px-3 py-1 bg-blue-600 text-white rounded">ดาวน์โหลด CSV</a>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const p = usePage().props
const income = computed(()=> p.income || 0)
const expense = computed(()=> p.expense || 0)
const net = computed(()=> p.net || 0)
const income_base = computed(()=> p.income_base || 0)
const expense_base = computed(()=> p.expense_base || 0)
const net_base = computed(()=> p.net_base || 0)
// base currency code exposed via shared `app.base_currency`
const baseCurrency = computed(() => (p.app && p.app.base_currency) ? p.app.base_currency : 'THB')
const csvUrl = '/admin/accounting/reports/overview.csv'

function format(n){
  return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n||0)
}
</script>

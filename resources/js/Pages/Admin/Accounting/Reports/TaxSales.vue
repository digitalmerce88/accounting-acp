<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">รายงานภาษีขาย</h1>
    <div class="mt-2 text-sm text-gray-600">ช่วง: {{ start }} ถึง {{ end }}</div>
    <div class="mt-4 p-4 bg-white border rounded text-2xl">{{ format(amount) }}</div>
    <div class="mt-4">
      <a href="/admin/accounting/reports/tax/sales-vat.csv" class="px-3 py-1 bg-blue-600 text-white rounded">ดาวน์โหลด CSV</a>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const p = usePage().props
const start = computed(()=> p.start)
const end = computed(()=> p.end)
const amount = computed(()=> p.amount || 0)
function format(n){
  return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n||0)
}
</script>

<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">รอบเงินเดือน {{ run.period_year }}-{{ String(run.period_month).padStart(2,'0') }}</h1>
      <div class="flex gap-2">
        <button v-if="run.status==='draft'" @click="lock()" class="px-3 py-1 bg-yellow-600 text-white rounded">ล็อก</button>
        <button v-if="run.status==='locked'" @click="pay()" class="px-3 py-1 bg-blue-700 text-white rounded">จ่าย</button>
      </div>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">พนักงาน</th>
            <th class="p-2 border text-right">เงินเดือน</th>
            <th class="p-2 border text-right">ประกันสังคม(ล)</th>
            <th class="p-2 border text-right">ประกันสังคม(น)</th>
            <th class="p-2 border text-right">ภาษีหัก ณ ที่จ่าย</th>
            <th class="p-2 border text-right">สุทธิ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="it in items" :key="it.id">
            <td class="p-2 border">{{ it.employee?.name || '-' }}</td>
            <td class="p-2 border text-right">{{ fmt(it.earning_basic_decimal + (it.earning_other_decimal||0)) }}</td>
            <td class="p-2 border text-right">{{ fmt(it.sso_employee_decimal) }}</td>
            <td class="p-2 border text-right">{{ fmt(it.sso_employer_decimal) }}</td>
            <td class="p-2 border text-right">{{ fmt(it.wht_decimal) }}</td>
            <td class="p-2 border text-right">{{ fmt(it.net_pay_decimal) }}</td>
          </tr>
          <tr v-if="!items || items.length===0">
            <td colspan="6" class="p-3 text-center text-gray-500">ไม่มีรายการ</td>
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
const run = computed(()=> usePage().props.run)
const items = computed(()=> usePage().props.items || [])
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function lock(){ if(confirm('ล็อกรอบนี้?')) router.post(`/admin/hr/payroll/${run.value.id}/lock`) }
function pay(){ if(confirm('จ่ายเงินเดือนรอบนี้?')) router.post(`/admin/hr/payroll/${run.value.id}/pay`, { date: new Date().toISOString().slice(0,10) }) }
</script>

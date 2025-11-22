<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">ทะเบียนทรัพย์สิน</h1>
      <div class="flex gap-2">
        <a href="/admin/assets/assets/create" class="px-3 py-1 bg-blue-700 text-white rounded text-sm">+ เพิ่มทรัพย์สิน</a>
        <a href="/admin/assets/categories" class="px-3 py-1 border rounded text-sm">หมวดหมู่</a>
      </div>
    </div>
    <div class="overflow-x-auto text-sm">
      <table class="min-w-full border">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border text-left">รหัส</th>
            <th class="p-2 border text-left">ชื่อ</th>
            <th class="p-2 border text-left">หมวดหมู่</th>
            <th class="p-2 border text-right">มูลค่าซื้อ</th>
            <th class="p-2 border text-right">ค่าเสื่อม/เดือน</th>
            <th class="p-2 border text-center">สถานะ</th>
            <th class="p-2 border w-32"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="border p-2">{{ r.asset_code }}</td>
            <td class="border p-2">{{ r.name }}</td>
            <td class="border p-2">{{ r.category?.name || '-' }}</td>
            <td class="border p-2 text-right">{{ fmt(r.purchase_cost_decimal) }}</td>
            <td class="border p-2 text-right">{{ fmt(monthly(r)) }}</td>
            <td class="border p-2 text-center uppercase" :class="r.status==='disposed' ? 'text-red-600' : 'text-green-700'">{{ r.status }}</td>
            <td class="border p-2 text-center">
              <a :href="`/admin/assets/assets/${r.id}`" class="text-blue-700">ดู</a>
            </td>
          </tr>
          <tr v-if="rows.data.length===0"><td colspan="7" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const rows = page.props.rows
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function monthly(r){
  const cost = Number(r.purchase_cost_decimal||0)
  const salvage = Number(r.salvage_value_decimal||0)
  const life = Number(r.useful_life_months||0)
  if(life<=0) return 0
  const base = Math.max(cost - salvage,0)
  return Math.round((base / life)*100)/100
}
</script>

<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">ทรัพย์สิน {{ item.asset_code }}</h1>
      <div class="flex gap-2">
        <a v-if="item.status!=='disposed'" :href="`/admin/assets/assets/${item.id}/edit`" class="px-3 py-1 border rounded text-sm">แก้ไข</a>
        <button v-if="item.status!=='disposed'" @click="openDispose" class="px-3 py-1 bg-red-700 text-white rounded text-sm">จำหน่าย</button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
      <div>
        <div class="text-gray-500">ชื่อ</div>
        <div class="font-medium">{{ item.name }}</div>
      </div>
      <div>
        <div class="text-gray-500">หมวดหมู่</div>
        <div class="font-medium">{{ item.category?.name || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">มูลค่าซื้อ</div>
        <div class="font-medium">{{ fmt(item.purchase_cost_decimal) }}</div>
      </div>
      <div>
        <div class="text-gray-500">มูลค่าเศษ</div>
        <div class="font-medium">{{ fmt(item.salvage_value_decimal) }}</div>
      </div>
      <div>
        <div class="text-gray-500">อายุการใช้งาน (เดือน)</div>
        <div class="font-medium">{{ item.useful_life_months }}</div>
      </div>
      <div>
        <div class="text-gray-500">สถานะ</div>
        <div class="font-medium" :class="item.status==='disposed' ? 'text-red-600' : 'text-green-700'">{{ statusLabel(item.status) }}</div>
      </div>
      <div>
        <div class="text-gray-500">ค่าเสื่อม / เดือน (SLM)</div>
        <div class="font-medium">{{ fmt(monthlyAmount) }}</div>
      </div>
      <div>
        <div class="text-gray-500">เริ่มคิดค่าเสื่อม</div>
        <div class="font-medium">{{ item.start_depreciation_date }}</div>
      </div>
    </div>
    <h2 class="text-lg font-semibold mb-2">รายการค่าเสื่อม</h2>
    <div class="overflow-x-auto text-sm mb-6">
      <table class="min-w-full border">
        <thead class="bg-gray-50">
          <tr>
            <th class="border p-2 text-left">ปี-เดือน</th>
            <th class="border p-2 text-right">จำนวนเงิน</th>
            <th class="border p-2 text-center">สถานะโพสต์</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in item.depreciation_entries || []" :key="d.id">
            <td class="border p-2">{{ d.period_year }}-{{ String(d.period_month).padStart(2,'0') }}</td>
            <td class="border p-2 text-right">{{ fmt(d.amount_decimal) }}</td>
            <td class="border p-2 text-center">{{ d.posted_journal_entry_id ? statusLabel('posted') : '-' }}</td>
          </tr>
          <tr v-if="!item.depreciation_entries || item.depreciation_entries.length===0"><td colspan="3" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
        </tbody>
      </table>
    </div>

    <div v-if="showDispose" class="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div class="bg-white rounded shadow p-4 w-full max-w-sm space-y-3 text-sm">
        <h3 class="font-semibold">จำหน่ายทรัพย์สิน</h3>
        <div>
          <label class="block text-gray-600 mb-1">มูลค่าที่ขายได้ (Proceeds)</label>
          <input v-model.number="proceed" type="number" min="0" step="0.01" class="w-full border rounded p-2 text-right" />
        </div>
        <div class="flex gap-2">
          <button @click="doDispose" class="px-3 py-1 bg-red-700 text-white rounded" :disabled="disposing">ยืนยันจำหน่าย</button>
          <button @click="showDispose=false" class="px-3 py-1 border rounded">ยกเลิก</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { statusLabel } from '@/utils/statusLabels'
const page = usePage()
const item = page.props.item
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
const monthlyAmount = computed(()=>{
  const cost = Number(item.purchase_cost_decimal||0)
  const salvage = Number(item.salvage_value_decimal||0)
  const life = Number(item.useful_life_months||0)
  if(life<=0) return 0
  const base = Math.max(cost - salvage,0)
  return Math.round((base / life)*100)/100
})
const showDispose = ref(false)
const disposing = ref(false)
const proceed = ref(0)
function openDispose(){ showDispose.value=true }
function doDispose(){
  if(!confirm('ยืนยันจำหน่ายทรัพย์สินนี้?')) return
  disposing.value=true
  router.post(`/admin/assets/assets/${item.id}/dispose`, { proceed_amount_decimal: proceed.value }, { onFinish(){ disposing.value=false; showDispose.value=false } })
}
</script>

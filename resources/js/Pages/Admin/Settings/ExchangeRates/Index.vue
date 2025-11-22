<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">อัตราแลกเปลี่ยน</h1>
      <a href="/admin/settings/exchange-rates/create" class="px-3 py-1 bg-blue-700 text-white rounded text-sm">+ เพิ่มอัตรา</a>
    </div>
    <div class="overflow-x-auto text-sm">
      <table class="min-w-full border">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border text-left">วันที่</th>
            <th class="p-2 border text-left">สกุลเงิน</th>
            <th class="p-2 border text-right">อัตรา (ต่อ THB)</th>
            <th class="p-2 border w-32"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="border p-2">{{ r.date || r.rate_date }}</td>
            <td class="border p-2 uppercase">{{ (r.currency_code || r.base_currency || r.base) }}</td>
            <td class="border p-2 text-right">{{ fmt(r.rate_decimal || r.rate) }}</td>
            <td class="border p-2">
              <div class="flex gap-2">
                <a :href="`/admin/settings/exchange-rates/${r.id}/edit`" class="px-2 py-0.5 text-xs bg-blue-700 text-white rounded">แก้ไข</a>
                <button @click="remove(r.id)" class="px-2 py-0.5 text-xs bg-red-600 text-white rounded">ลบ</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="4" class="p-3 text-center text-gray-500">ยังไม่มีข้อมูล</td>
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
import { confirmDialog } from '@/utils/swal'
const rows = computed(() => usePage().props.rows || { data: [] })
function fmt(n) { return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 6 }) }
async function remove(id) {
  if (await confirmDialog('ลบอัตราแลกเปลี่ยนนี้?')) {
    router.delete(`/admin/settings/exchange-rates/${id}`)
  }
}
</script>

<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">ใบเสนอราคา</h1>
      <a href="/admin/documents/quotes/create" class="px-3 py-1 bg-blue-700 text-white rounded text-sm">+ สร้างใหม่</a>
    </div>
  <div class="overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">เลขที่</th>
            <th class="p-2 border">ลูกค้า</th>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border text-right">รวมสุทธิ</th>
            <th class="p-2 border">สถานะ</th>
            <th class="p-2 border w-32">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.number || r.id }}</td>
            <td class="p-2 border">{{ r.customer?.name || '-' }}</td>
            <td class="p-2 border">{{ fmtDMY(r.issue_date) }}</td>
            <td class="p-2 border text-right">{{ fmt(r.total) }}</td>
            <td class="p-2 border">{{ r.status || '-' }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <button @click="openView(r.id)" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</button>
                <button v-if="r.status==='draft'" @click="remove(r.id)" class="px-2 py-0.5 text-xs bg-red-600 text-white rounded">ลบ</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0"><td colspan="6" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
  <!-- View Modal -->
  <Modal :show="showModal" @close="closeModal">
    <div class="p-4 text-sm min-w-[320px]">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">รายละเอียดใบเสนอราคา</h3>
        <button class="text-gray-500" @click="closeModal">✕</button>
      </div>
      <div v-if="item" class="flex flex-wrap gap-2 mb-3">
        <a :href="`/admin/documents/quotes/${item.id}/pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์ใบเสนอราคา</a>
        <a :href="`/admin/documents/quotes/${item.id}/edit`" class="px-2 py-0.5 border rounded">แก้ไข</a>
      </div>
      <div v-if="loading" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
      <div v-else-if="item">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div><div class="text-gray-500">เลขที่</div><div class="font-medium">{{ item.number || item.id }}</div></div>
          <div><div class="text-gray-500">วันที่</div><div class="font-medium">{{ fmtDMY(item.issue_date) }}</div></div>
          <div><div class="text-gray-500">หัวข้อ</div><div class="font-medium">{{ item.subject || '-' }}</div></div>
          <div><div class="text-gray-500">รวมสุทธิ</div><div class="font-semibold">{{ fmt(item.total) }}</div></div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full border text-xs">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 border text-left">รายการ</th>
                <th class="p-2 border w-16 text-right">จำนวน</th>
                <th class="p-2 border w-24 text-right">ราคา</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in item.items || []" :key="it.id">
                <td class="p-2 border">{{ it.name }}</td>
                <td class="p-2 border text-right">{{ it.qty_decimal }}</td>
                <td class="p-2 border text-right">{{ fmt(it.unit_price_decimal) }}</td>
              </tr>
              <tr v-if="!item.items || item.items.length===0"><td colspan="3" class="p-3 text-center text-gray-500">-</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Modal>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { confirmDialog } from '@/utils/swal'
import { computed, ref } from 'vue'
import { fmtDMY } from '@/utils/format'
import Modal from '@/Components/Modal.vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }

// modal state
const showModal = ref(false)
const loading = ref(false)
const item = ref(null)
function closeModal(){ showModal.value = false; item.value = null }
async function openView(id){
  showModal.value = true
  loading.value = true
  try{
    const res = await fetch(`/admin/documents/quotes/${id}`, { headers: { 'Accept':'application/json' } })
    const data = await res.json()
    item.value = data.item
  } finally {
    loading.value = false
  }
}
async function remove(id){ if(await confirmDialog('ลบเอกสารนี้?')) router.delete(`/admin/documents/quotes/${id}`) }
</script>

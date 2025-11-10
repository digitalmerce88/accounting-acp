<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">ใบแจ้งหนี้/ใบกำกับภาษี</h1>
      <a href="/admin/documents/invoices/create" class="px-3 py-1 bg-blue-700 text-white rounded text-sm">+ สร้างใหม่</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">เลขที่</th>
            <th class="p-2 border">คู่ค้า</th>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border text-right">รวมสุทธิ</th>
            <th class="p-2 border">สถานะ</th>
            <th class="p-2 border w-40">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.number || r.id }}</td>
            <td class="p-2 border">{{ r.customer?.name || '-' }}</td>
            <td class="p-2 border">{{ fmtDMY(r.issue_date) }}</td>
            <td class="p-2 border text-right">{{ fmt((r.total || 0) - (r.deposit_amount_decimal || 0)) }}</td>
            <td class="p-2 border">{{ r.status || '-' }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <button @click="openView(r.id)" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</button>
                <button v-if="r.status!=='paid'" @click="pay(r.id)" class="px-2 py-0.5 text-xs bg-blue-700 text-white rounded">รับชำระ</button>
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
        <h3 class="text-lg font-semibold">รายละเอียดใบแจ้งหนี้</h3>
        <button class="text-gray-500" @click="closeModal">✕</button>
      </div>
          <div v-if="item" class="flex flex-wrap gap-2 mb-3">
        <a :href="`/admin/documents/invoices/${item.id}/pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์ใบกำกับภาษี</a>
        <a v-if="item.status==='paid'" :href="`/admin/documents/invoices/${item.id}/receipt.pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์ใบเสร็จ</a>
        <a v-if="item.status!=='paid'" :href="`/admin/documents/invoices/${item.id}/edit`" class="px-2 py-0.5 border rounded">แก้ไข</a>
      </div>
      <div v-if="loading" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
      <div v-else-if="item">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div><div class="text-gray-500">เลขที่</div><div class="font-medium">{{ item.number || item.id }}</div></div>
          <div><div class="text-gray-500">วันที่ออก</div><div class="font-medium">{{ fmtDMY(item.issue_date) }}</div></div>
          <div><div class="text-gray-500">ครบกำหนด</div><div class="font-medium">{{ fmtDMY(item.due_date) }}</div></div>
          <div><div class="text-gray-500">สถานะ</div><div class="font-medium">{{ item.status || '-' }}</div></div>
          <div><div class="text-gray-500">ยอดสุทธิ (ก่อนมัดจำ)</div><div class="font-semibold">{{ fmt(item.total) }}</div></div>
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
        <div class="mt-3">
          <table class="min-w-full text-sm">
            <tbody>
              <tr>
                <td class="text-gray-600">Subtotal</td>
                <td class="text-right font-medium">{{ fmt(item.subtotal ?? 0) }}</td>
              </tr>
              <tr v-if="item.discount_type && item.discount_type !== 'none'">
                <td class="text-gray-600">ส่วนลด @if(item.discount_type==='percent') ({{ numberFormat(item.discount_value_decimal) }}%) @endif</td>
                <td class="text-right text-red-600">-{{ fmt(item.discount_amount_decimal ?? 0) }}</td>
              </tr>
              <tr>
                <td class="text-gray-600">VAT</td>
                <td class="text-right">{{ fmt(item.vat_decimal ?? 0) }}</td>
              </tr>
              <tr>
                <td class="text-gray-600 font-semibold">Total</td>
                <td class="text-right font-semibold">{{ fmt(item.total ?? 0) }}</td>
              </tr>
              <tr v-if="item.deposit_type && item.deposit_type !== 'none'">
                <td class="text-gray-600">มัดจำ @if(item.deposit_type==='percent') ({{ numberFormat(item.deposit_value_decimal) }}%) @endif</td>
                <td class="text-right text-red-600">-{{ fmt(item.deposit_amount_decimal ?? 0) }}</td>
              </tr>
              <tr>
                <td class="text-gray-600 font-semibold">ยอดคงเหลือต้องชำระ</td>
                <td class="text-right font-semibold">{{ fmt( (item.total ?? 0) - (item.deposit_amount_decimal ?? 0) ) }}</td>
              </tr>
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
import { computed, ref } from 'vue'
import { fmtDMY } from '@/utils/format'
import { confirmDialog } from '@/utils/swal'
import Modal from '@/Components/Modal.vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function numberFormat(n){
  const v = Number(n || 0)
  // show integer values without decimals, otherwise show up to 2 decimals
  if (Number.isInteger(v)) return v.toString()
  return v.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2})
}
async function pay(id){ if(await confirmDialog('รับชำระสำหรับใบนี้?')) router.post(`/admin/documents/invoices/${id}/pay`, { date: new Date().toISOString().slice(0,10) }) }

async function remove(id){ if(await confirmDialog('ลบเอกสารนี้?')) router.delete(`/admin/documents/invoices/${id}`) }

// modal state
const showModal = ref(false)
const loading = ref(false)
const item = ref(null)
function closeModal(){ showModal.value = false; item.value = null }
async function openView(id){
  showModal.value = true
  loading.value = true
  try{
    const res = await fetch(`/admin/documents/invoices/${id}`, { headers: { 'Accept':'application/json' } })
    const data = await res.json()
    item.value = data.item
  } finally {
    loading.value = false
  }
}
</script>

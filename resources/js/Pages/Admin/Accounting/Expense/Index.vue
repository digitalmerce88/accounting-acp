<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">รายจ่าย</h1>
      <a href="/admin/accounting/expense/create" class="px-3 py-1 bg-red-700 text-white rounded">+ รายจ่าย</a>
    </div>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border">บันทึก</th>
            <th class="p-2 border text-right">จำนวน</th>
            <th class="p-2 border w-40">การทำงาน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ fmtDMY(r.date) }}</td>
            <td class="p-2 border">{{ r.memo }}</td>
            <td class="p-2 border text-right">{{ fmt(r.amount) }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                  <button @click="openView(r.id)" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</button>
                <a :href="`/admin/accounting/expense/${r.id}/edit`" class="px-2 py-0.5 text-xs bg-blue-600 text-white rounded">แก้ไข</a>
                <button @click="del(r.id)" class="px-2 py-0.5 text-xs bg-red-600 text-white rounded">ลบ</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="4" class="p-3 text-center text-gray-500">ยังไม่มีรายการ</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
    <!-- View Modal -->
    <Modal :show="showModal" @close="closeModal">
      <div class="p-4 text-sm min-w-[320px]">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-semibold">รายละเอียดรายจ่าย</h3>
          <button class="text-gray-500" @click="closeModal">✕</button>
        </div>
        <div v-if="item" class="flex flex-wrap gap-2 mb-3">
          <a :href="`/admin/accounting/expense/${item.id}/edit`" class="px-2 py-0.5 border rounded">แก้ไข</a>
        </div>
        <div v-if="loading" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
    <div v-else-if="item">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div><div class="text-gray-500">วันที่</div><div class="font-medium">{{ fmtDMY(item.date) }}</div></div>
            <div><div class="text-gray-500">จำนวน</div><div class="font-semibold">{{ fmt(item.amount) }}</div></div>
            <div><div class="text-gray-500">บันทึก</div><div class="font-medium">{{ item.memo || '-' }}</div></div>
            <div><div class="text-gray-500">วิธีจ่าย</div><div class="font-medium">{{ item.payment_method }}</div></div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full border text-xs">
              <thead class="bg-gray-50">
                <tr>
                  <th class="p-2 border text-left">บัญชี</th>
                  <th class="p-2 border text-right">เดบิต</th>
                  <th class="p-2 border text-right">เครดิต</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ln in lines" :key="ln.id">
                  <td class="p-2 border">{{ ln.account_name || ln.account_id }}</td>
                  <td class="p-2 border text-right">{{ fmt(ln.debit) }}</td>
                  <td class="p-2 border text-right">{{ fmt(ln.credit) }}</td>
                </tr>
                <tr v-if="!lines || lines.length===0"><td colspan="3" class="p-3 text-center text-gray-500">-</td></tr>
              </tbody>
            </table>
          </div>
          <div class="mt-6">
            <h3 class="font-semibold">ไฟล์แนบ</h3>
            <ul class="list-disc pl-5 text-sm">
              <li v-for="(a,idx) in attachmentsList" :key="a.id || idx">
                <a :href="`/storage/${a.path}`" target="_blank" class="text-blue-700 underline">{{ a.name || a.path }}</a>
              </li>
              <li v-if="!attachmentsList || attachmentsList.length===0" class="text-gray-500">-</li>
            </ul>
          </div>
        </div>
      </div>
    </Modal>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { fmtDMY } from '@/utils/format'
import { computed, ref } from 'vue'
import { confirmDialog } from '@/utils/swal'
  import Modal from '@/Components/Modal.vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
async function del(id){
  if (!(await confirmDialog('ยืนยันลบรายการนี้?'))) return
  router.delete(`/admin/accounting/expense/${id}`)
}

  // modal state
  const showModal = ref(false)
  const loading = ref(false)
  const item = ref(null)
  const lines = ref([])
  const attachmentsList = ref([])
  function closeModal(){ showModal.value = false; item.value = null; lines.value = [] }
  async function openView(id){
    showModal.value = true
    loading.value = true
    try{
      const res = await fetch(`/admin/accounting/expense/${id}`, { headers: { 'Accept':'application/json' } })
      const data = await res.json()
      item.value = data.item
      lines.value = data.lines || []
      attachmentsList.value = (data.attachments_json && data.attachments_json.length>0) ? data.attachments_json : (data.attachments || [])
    } finally {
      loading.value = false
    }
  }
</script>

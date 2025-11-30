<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">ใบวางบิล/ใบค่าใช้จ่าย</h1>
      <a href="/admin/documents/bills/create" class="px-3 py-1 bg-green-700 text-white rounded text-sm">+ สร้างใหม่</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">เลขที่</th>
            <th class="p-2 border">ผู้ขาย</th>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border text-right">รวมสุทธิ</th>
            <th class="p-2 border">สถานะ</th>
            <th class="p-2 border w-40">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.number || r.id }}</td>
            <td class="p-2 border">{{ r.vendor?.name || '-' }}</td>
            <td class="p-2 border">{{ fmtDMY(r.bill_date) }}</td>
            <td class="p-2 border text-right">{{ fmt(r.total) }}</td>
            <td class="p-2 border">
              <div class="flex items-center gap-2">

                <span v-if="r.approval_status" class="text-xs px-2 py-0.5 rounded border" :class="badgeClass(r.approval_status)">{{ approvalLabel(r.approval_status) }}</span>
              </div>
            </td>
            <td class="p-2 border">
              <div class="flex gap-2 items-center">
                <button @click="openView(r.id)" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</button>
                <button v-if="r.status!=='paid'" @click="pay(r.id)" class="px-2 py-0.5 text-xs bg-green-700 text-white rounded">จ่าย</button>
                <button v-if="r.status==='draft'" @click="remove(r.id)" class="px-2 py-0.5 text-xs bg-red-600 text-white rounded">ลบ</button>

                <div class="flex items-center gap-2">
                  <template v-if="r.approval_status==='draft'">
                    <button @click.prevent="openApproval('submit', r.id)" class="px-2 py-0.5 text-xs bg-yellow-400 text-white rounded">ส่งอนุมัติ</button>
                  </template>
                  <template v-else-if="r.approval_status==='submitted'">
                    <button v-if="auth.is_admin" @click.prevent="openApproval('approve', r.id)" class="px-2 py-0.5 text-xs bg-green-600 text-white rounded">อนุมัติ</button>
                    <span v-else class="text-xs px-2 py-0.5 border rounded text-gray-600">รออนุมัติ</span>
                  </template>
                  <template v-else-if="r.approval_status==='approved'">
                    <button v-if="auth.is_admin" @click.prevent="openApproval('lock', r.id)" class="px-2 py-0.5 text-xs bg-gray-800 text-white rounded">ล็อก</button>
                    <span v-else class="text-xs px-2 py-0.5 border rounded text-gray-600">อนุมัติ</span>
                  </template>
                  <template v-else-if="r.approval_status==='locked'">
                    <button v-if="auth.is_admin" @click.prevent="openApproval('unlock', r.id)" class="px-2 py-0.5 text-xs bg-indigo-600 text-white rounded">ปลดล็อก</button>
                    <span v-else class="text-xs px-2 py-0.5 border rounded text-gray-600">ล็อก</span>
                  </template>
                </div>
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
        <h3 class="text-lg font-semibold">รายละเอียดบิล</h3>
        <button class="text-gray-500" @click="closeModal">✕</button>
      </div>
      <div v-if="item" class="flex flex-wrap gap-2 mb-3">
        <a :href="`/admin/documents/bills/${item.id}/pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์ใบแจ้งหนี้</a>
        <a :href="`/admin/documents/bills/${item.id}/wht.pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์ใบหัก ณ ที่จ่าย</a>
        <a v-if="item.status!=='paid'" :href="`/admin/documents/bills/${item.id}/edit`" class="px-2 py-0.5 border rounded">แก้ไข</a>
      </div>
      <div v-if="loading" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
      <div v-else-if="item">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div><div class="text-gray-500">เลขที่</div><div class="font-medium">{{ item.number || item.id }}</div></div>
          <div><div class="text-gray-500">วันที่</div><div class="font-medium">{{ fmtDMY(item.bill_date) }}</div></div>
          <div><div class="text-gray-500">ครบกำหนด</div><div class="font-medium">{{ fmtDMY(item.due_date) }}</div></div>
          <div><div class="text-gray-500">สถานะ</div><div class="font-medium">{{ item.approval_status ? approvalLabel(item.approval_status) : '-' }}</div></div>
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
  <ApprovalCommentModal
    :show="approvalShow"
    :title="approvalTitle"
    v-model="approvalComment"
    :submitting="approvalSubmitting"
    @submit="doApproval"
    @cancel="closeApproval"
  />
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { approvalLabel } from '@/utils/statusLabels'
import { fmtDMY } from '@/utils/format'
import { confirmDialog } from '@/utils/swal'
import Modal from '@/Components/Modal.vue'
import ApprovalCommentModal from '@/Components/ApprovalCommentModal.vue'
// auth
const auth = computed(()=> usePage().props.auth || { is_admin: false })
const rows = computed(()=> usePage().props.rows || {data:[]})
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function badgeClass(s){
  return s==='approved' ? 'border-green-600 text-green-700' :
         s==='submitted' ? 'border-yellow-600 text-yellow-700' :
         s==='locked' ? 'border-gray-700 text-gray-700' : 'border-gray-400 text-gray-500'
}
async function pay(id){ if(await confirmDialog('จ่ายบิลนี้?')) router.post(`/admin/documents/bills/${id}/pay`, { date: new Date().toISOString().slice(0,10) }) }

// modal state
async function remove(id){ if(await confirmDialog('ลบเอกสารนี้?')) router.delete(`/admin/documents/bills/${id}`) }
const showModal = ref(false)
const loading = ref(false)
const item = ref(null)
function closeModal(){ showModal.value = false; item.value = null }
async function openView(id){
  showModal.value = true
  loading.value = true
  try{
    const res = await fetch(`/admin/documents/bills/${id}`, { headers: { 'Accept':'application/json' } })
    const data = await res.json()
    item.value = data.item
  } finally {
    loading.value = false
  }
}

// Approval modal for quick actions from index
const approvalShow = ref(false)
const approvalAction = ref('submit')
const approvalTarget = ref(null)
const approvalComment = ref('')
const approvalSubmitting = ref(false)
const approvalTitle = computed(()=> approvalAction.value==='submit' ? 'ส่งอนุมัติเอกสาร' : approvalAction.value==='approve' ? 'อนุมัติเอกสาร' : approvalAction.value==='lock' ? 'ล็อกเอกสาร' : 'ปลดล็อกเอกสาร')
function openApproval(act, id){ approvalAction.value = act; approvalTarget.value = id; approvalComment.value=''; approvalShow.value = true }
function closeApproval(){ approvalShow.value = false; approvalTarget.value = null; approvalComment.value = '' }
async function doApproval(commentArg){
  approvalSubmitting.value = true
  const id = approvalTarget.value
  const payload = { comment: commentArg }
  const url = approvalAction.value==='submit' ? `/admin/documents/bills/${id}/submit` :
              approvalAction.value==='approve' ? `/admin/documents/bills/${id}/approve` :
              approvalAction.value==='lock' ? `/admin/documents/bills/${id}/lock` :
              `/admin/documents/bills/${id}/unlock`
  router.post(url, payload, { onFinish: () => { approvalSubmitting.value=false; approvalShow.value=false; router.reload() } })
}
</script>

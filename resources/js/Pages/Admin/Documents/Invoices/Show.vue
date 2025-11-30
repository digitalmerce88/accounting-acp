<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Invoice {{ item.number || item.id }}</h1>
    <div class="flex gap-2">
  <a :href="`/admin/documents/invoices/${item.id}/history`" class="px-3 py-1 border rounded">ประวัติ</a>
  <a :href="`/admin/documents/invoices/${item.id}/pdf`" target="_blank" rel="noopener" class="px-3 py-1 border rounded">ดูตัวอย่างใบแจ้งหนี้</a>
  <a :href="`/admin/documents/invoices/${item.id}/pdf?dl=1`" class="px-3 py-1 border rounded">ดาวน์โหลดใบแจ้งหนี้</a>
  <a :href="`/admin/documents/invoices/${item.id}/receipt.pdf`" target="_blank" rel="noopener" class="px-3 py-1 border rounded">ดูใบเสร็จ</a>
  <a :href="`/admin/documents/invoices/${item.id}/receipt.pdf?dl=1`" class="px-3 py-1 border rounded">ดาวน์โหลดใบเสร็จ</a>
        <a v-if="item.status!=='paid' && !['approved','locked'].includes(item.approval_status)" :href="`/admin/documents/invoices/${item.id}/edit`" class="px-3 py-1 border rounded">แก้ไข</a>
        <button v-if="item.status!=='paid' && !['approved','locked'].includes(item.approval_status)" @click="onDelete" class="px-3 py-1 bg-red-600 text-white rounded">ลบ</button>
        <button v-if="item.status!=='paid'" @click="pay" class="px-3 py-1 bg-blue-700 text-white rounded">รับชำระ</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-sm">
      <div>
        <div class="text-gray-500">วันที่</div>
        <div class="font-medium">{{ fmtDMY(item.issue_date) }}</div>
      </div>
      <div>
        <div class="text-gray-500">ครบกำหนด</div>
        <div class="font-medium">{{ fmtDMY(item.due_date) }}</div>
      </div>
      <div>
        <div class="text-gray-500">รวมสุทธิ ({{ item.currency_code || 'THB' }})</div>
        <div class="font-medium">{{ fmt(item.total) }}</div>
      </div>
      <div v-if="item.base_total_decimal">
        <div class="text-gray-500">มูลค่าเทียบสกุลฐาน</div>
        <div class="font-medium">{{ fmt(item.base_total_decimal) }}</div>
      </div>
      <div>
          <div class="text-gray-500">สถานะ</div>
                <div class="font-medium flex items-center gap-2">
                <span>{{ item.approval_status ? approvalLabel(item.approval_status) : '-' }}</span>
                  <span v-if="item.approval_status" class="text-xs px-2 py-0.5 rounded border"
                  :class="badgeClass(item.approval_status)">
                  {{ approvalLabel(item.approval_status) }}
                </span>
              </div>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
      <button v-if="item.approval_status==='draft'" @click="openModal('submit')" class="px-3 py-1 bg-yellow-600 text-white rounded">ส่งอนุมัติ</button>
      <button v-if="auth.is_admin && item.approval_status==='submitted'" @click="openModal('approve')" class="px-3 py-1 bg-green-700 text-white rounded">อนุมัติ</button>
      <button v-if="auth.is_admin && item.approval_status==='approved'" @click="openModal('lock')" class="px-3 py-1 bg-gray-800 text-white rounded">ล็อก</button>
      <button v-if="auth.is_admin && item.approval_status==='locked'" @click="openModal('unlock')" class="px-3 py-1 bg-gray-500 text-white rounded">ปลดล็อก</button>
    </div>

    <ApprovalCommentModal
      :show="showModal"
      :title="modalTitle"
      v-model="comment"
      :submitting="submitting"
      @submit="doAction"
      @cancel="closeModal"
    />
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { statusLabel, approvalLabel } from '@/utils/statusLabels'
import { fmtDMY } from '@/utils/format'
import ApprovalCommentModal from '@/Components/ApprovalCommentModal.vue'
const page = usePage()
const item = computed(()=> page.props.item)
const auth = computed(()=> page.props.auth || { is_admin: false })
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
async function pay(){ if(confirm('รับชำระใบแจ้งหนี้นี้?')) router.post(`/admin/documents/invoices/${item.value.id}/pay`, { date: new Date().toISOString().slice(0,10) }) }
async function onDelete(){ if(confirm('ลบเอกสารนี้?')) router.delete(`/admin/documents/invoices/${item.value.id}`) }

function badgeClass(s){
  return s==='approved' ? 'border-green-600 text-green-700' :
         s==='submitted' ? 'border-yellow-600 text-yellow-700' :
         s==='locked' ? 'border-gray-700 text-gray-700' : 'border-gray-400 text-gray-500'
}
const showModal = ref(false)
const action = ref('submit')
const comment = ref('')
const submitting = ref(false)
const modalTitle = computed(()=> action.value==='submit' ? 'ส่งอนุมัติเอกสาร' : action.value==='approve' ? 'อนุมัติเอกสาร' : action.value==='lock' ? 'ล็อกเอกสาร' : 'ปลดล็อกเอกสาร')
function openModal(act){ action.value = act; comment.value=''; showModal.value=true }
function closeModal(){ showModal.value=false }
async function doAction(){
  submitting.value = true
  const id = item.value.id
  const payload = { comment: comment.value }
  const url = action.value==='submit' ? `/admin/documents/invoices/${id}/submit` :
              action.value==='approve' ? `/admin/documents/invoices/${id}/approve` :
              action.value==='lock' ? `/admin/documents/invoices/${id}/lock` :
              `/admin/documents/invoices/${id}/unlock`
  router.post(url, payload, {
    onFinish: () => { submitting.value=false; showModal.value=false }
  })
}
</script>

<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
  <h1 class="text-xl font-semibold">บันทึกรายการ</h1>
  <Link href="/admin/accounting/journals/create" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">สร้าง</Link>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-2 border">วันที่</th>
            <th class="text-left p-2 border">บันทึก</th>
            <th class="text-left p-2 border">การทำงาน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="j in journals" :key="j.id" class="border-b hover:bg-gray-50">
            <td class="p-2 border">{{ fmtDMY(j.date) }}</td>
            <td class="p-2 border">{{ j.memo }}</td>
            <td class="p-2 border">
              <div class="flex items-center gap-3">
                <button @click="openView(j.id)" class="text-blue-700 hover:underline">ดู</button>
                <a :href="`/admin/accounting/journals/${j.id}/edit`" class="text-gray-700 hover:underline">แก้ไข</a>
                <button class="text-red-600 hover:underline" @click="del(j)">ลบ</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && journals.length===0">
            <td colspan="3" class="p-4 text-center text-gray-500">ไม่มีข้อมูล</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center justify-between text-sm">
  <div class="text-gray-600">หน้า {{ meta.current_page }} จาก {{ meta.last_page }}</div>
      <div class="space-x-2">
  <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="meta.current_page<=1" @click="load(meta.current_page-1)">ก่อนหน้า</button>
  <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="meta.current_page>=meta.last_page" @click="load(meta.current_page+1)">ถัดไป</button>
      </div>
    </div>
  </AdminLayout>
  <!-- View Modal -->
  <Modal :show="showModal" @close="closeModal">
    <div class="p-4 text-sm min-w-[320px]">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">รายละเอียดรายการบันทึก</h3>
        <div class="flex items-center gap-2">
          <a v-if="viewEntry" :href="`/admin/accounting/journals/${viewEntry.id}/edit`" class="text-sm px-2 py-0.5 border rounded">แก้ไข</a>
          <button class="text-gray-500" @click="closeModal">✕</button>
        </div>
      </div>
      <div v-if="loadingView" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
      <div v-else-if="viewEntry">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div><div class="text-gray-500">วันที่</div><div class="font-medium">{{ fmtDMY(viewEntry.date) }}</div></div>
          <div class="md:col-span-2"><div class="text-gray-500">บันทึก</div><div class="font-medium">{{ viewEntry.memo || '-' }}</div></div>
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
              <tr v-for="ln in viewLines" :key="ln.id">
                <td class="p-2 border">{{ ln.account_name || ln.account_id }}</td>
                <td class="p-2 border text-right">{{ Number(ln.debit||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
                <td class="p-2 border text-right">{{ Number(ln.credit||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
              </tr>
              <tr v-if="!viewLines || viewLines.length===0"><td colspan="3" class="p-3 text-center text-gray-500">-</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { fmtDMY } from '@/utils/format'
import axios from 'axios'
import Modal from '@/Components/Modal.vue'
import { confirmDialog } from '@/utils/swal'
const journals = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const loading = ref(false)
const load = async (page = 1)=>{
  loading.value = true
  try {
    const res = await axios.get('/admin/accounting/journals', { params: { page }, headers: { Accept: 'application/json' } })
    journals.value = res.data.data || []
    meta.value = res.data.meta || { current_page: 1, last_page: 1 }
  } finally { loading.value = false }
}
async function del(j) {
  if (!(await confirmDialog('ลบรายการนี้หรือไม่?'))) return
  await axios.delete(`/admin/accounting/journals/${j.id}`)
  load(meta.value.current_page)
}

// modal state
const showModal = ref(false)
const loadingView = ref(false)
const viewEntry = ref(null)
const viewLines = ref([])
function closeModal(){ showModal.value = false; viewEntry.value = null; viewLines.value = [] }
async function openView(id){
  showModal.value = true
  loadingView.value = true
  try{
    const res = await axios.get(`/admin/accounting/journals/${id}`, { headers: { Accept: 'application/json' } })
    viewEntry.value = res.data.entry
    viewLines.value = res.data.lines || []
  } finally {
    loadingView.value = false
  }
}
onMounted(() => load(1))
</script>

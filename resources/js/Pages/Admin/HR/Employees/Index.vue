<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">พนักงาน</h1>
      <a href="/admin/hr/employees/create" class="px-3 py-1 bg-green-700 text-white rounded">+ เพิ่มพนักงาน</a>
    </div>
    <div class="flex gap-2 mb-3">
      <a href="/admin/hr/employees?status=active" :class="tabClass('active')">กำลังใช้งาน</a>
      <a href="/admin/hr/employees?status=inactive" :class="tabClass('inactive')">ปิดใช้งาน</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">รหัส</th>
            <th class="p-2 border">ชื่อ</th>
            <th class="p-2 border">ตำแหน่ง</th>
            <th class="p-2 border text-right">เงินเดือน</th>
            <th class="p-2 border">สถานะ</th>
            <th class="p-2 border w-48">การทำงาน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in rows.data" :key="e.id">
            <td class="p-2 border">{{ e.emp_code || '-' }}</td>
            <td class="p-2 border">{{ e.name }}</td>
            <td class="p-2 border">{{ e.position || '-' }}</td>
            <td class="p-2 border text-right">{{ fmt(e.base_salary_decimal) }}</td>
            <td class="p-2 border">
              <span :class="e.active ? 'text-green-700' : 'text-gray-400'">{{ e.active ? 'ใช้งาน' : 'ปิด' }}</span>
            </td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <button @click="openView(e.id)" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</button>
                <a :href="`/admin/hr/employees/${e.id}/edit`" class="px-2 py-0.5 text-xs bg-blue-700 text-white rounded">แก้ไข</a>
                <button v-if="e.active" @click="deactivate(e.id)" class="px-2 py-0.5 text-xs bg-red-700 text-white rounded">ปิด</button>
                <button v-else @click="restore(e.id)" class="px-2 py-0.5 text-xs bg-green-700 text-white rounded">เปิด</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="6" class="p-3 text-center text-gray-500">ยังไม่มีพนักงาน</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
  <!-- View Modal -->
  <Modal :show="showModal" @close="closeModal">
    <div class="p-4 text-sm min-w-[320px]">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">รายละเอียดพนักงาน</h3>
        <button class="text-gray-500" @click="closeModal">✕</button>
      </div>
      <div v-if="item" class="flex flex-wrap gap-2 mb-3">
        <a :href="`/admin/hr/employees/${item.id}/edit`" class="px-2 py-0.5 border rounded">แก้ไข </a>
      </div>
      <div v-if="loading" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
      <div v-else-if="item">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div>
            <div class="text-gray-500">รหัส</div>
            <div class="font-medium">{{ item.emp_code || '-' }}</div>
          </div>
          <div>
            <div class="text-gray-500">ชื่อ</div>
            <div class="font-medium">{{ item.name }}</div>
          </div>
          <div>
            <div class="text-gray-500">ตำแหน่ง</div>
            <div class="font-medium">{{ item.position || '-' }}</div>
          </div>
          <div>
            <div class="text-gray-500">เริ่มงาน</div>
            <div class="font-medium">{{ fmtDMY(item.start_date) }}</div>
          </div>
          <div>
            <div class="text-gray-500">เงินเดือน</div>
            <div class="font-semibold">{{ fmt(item.base_salary_decimal) }}</div>
          </div>
          <div>
            <div class="text-gray-500">สถานะ</div>
            <div class="font-medium">{{ item.active ? 'ใช้งาน' : 'ปิด' }}</div>
          </div>
          <div>
            <div class="text-gray-500">เบอร์</div>
            <div class="font-medium">{{ item.phone || '-' }}</div>
          </div>
          <div>
            <div class="text-gray-500">อีเมล</div>
            <div class="font-medium">{{ item.email || '-' }}</div>
          </div>
          <div>
            <div class="text-gray-500">เลขประชาชน</div>
            <div class="font-medium">{{ item.citizen_id || '-' }}</div>
          </div>
          <div>
            <div class="text-gray-500">ประกันสังคม</div>
            <div class="font-medium">{{ item.sso_enabled ? 'ลงทะเบียน' : 'ไม่ลงทะเบียน' }}</div>
          </div>
        </div>
        <div class="mb-2">
          <div class="text-gray-500 mb-1">บัญชีธนาคาร</div>
          <div v-if="item.bank_account_json" class="rounded border p-2 bg-gray-50">
            <div class="flex gap-2"><span class="w-20 text-gray-500">ธนาคาร</span><span class="font-medium">{{ item.bank_account_json.name || item.bank_account_json.code || '-' }}</span></div>
            <div class="flex gap-2"><span class="w-20 text-gray-500">เลขที่</span><span class="font-medium">{{ item.bank_account_json.number || '-' }}</span></div>
          </div>
          <div v-else class="text-gray-500">-</div>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { confirmDialog } from '@/utils/swal'
import { fmtDMY } from '@/utils/format'
import Modal from '@/Components/Modal.vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
const status = computed(()=> usePage().props.status || 'active')
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
async function deactivate(id){ if(await confirmDialog('ปิดใช้งานพนักงานนี้?')) router.delete(`/admin/hr/employees/${id}`) }
async function restore(id){ if(await confirmDialog('เปิดใช้งานพนักงานนี้?')) router.post(`/admin/hr/employees/${id}/restore`) }
function tabClass(s){ return 'px-3 py-1 rounded ' + (status.value===s ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700') }

// modal state
const showModal = ref(false)
const loading = ref(false)
const item = ref(null)
function closeModal(){ showModal.value = false; item.value = null }
async function openView(id){
  showModal.value = true
  loading.value = true
  try {
    const res = await fetch(`/admin/hr/employees/${id}`, { headers: { 'Accept': 'application/json' } })
    const data = await res.json()
    item.value = data.item
  } finally {
    loading.value = false
  }
}
</script>
